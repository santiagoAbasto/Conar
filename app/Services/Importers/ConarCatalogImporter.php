<?php

namespace App\Services\Importers;

use App\DTOs\Scraping\Conar\ConarCategoryData;
use App\DTOs\Scraping\Conar\ConarDownloadData;
use App\DTOs\Scraping\Conar\ConarImageData;
use App\DTOs\Scraping\Conar\ConarProductData;
use App\DTOs\Scraping\Conar\ConarSpecificationData;
use App\Models\ImportRun;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDownload;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductSubcategory;
use App\Services\Scraping\RemoteAssetDownloader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConarCatalogImporter
{
    protected string $provider = 'conar';

    public function __construct(
        protected RemoteAssetDownloader $downloader,
        protected ImportRunRecorder $recorder,
    ) {
    }

    public function import(
        ConarCategoryData $data,
        ImportRun $run,
        bool $downloadAssets = true,
        bool $importDownloads = true
    ): array
    {
        $counters = [
            'categories_created' => 0,
            'categories_updated' => 0,
            'subcategories_created' => 0,
            'subcategories_updated' => 0,
            'products_created' => 0,
            'products_updated' => 0,
            'specifications_created' => 0,
            'specifications_updated' => 0,
            'downloads_created' => 0,
            'downloads_updated' => 0,
            'images_created' => 0,
            'images_updated' => 0,
        ];

        DB::transaction(function () use ($data, $run, $downloadAssets, $importDownloads, &$counters) {
            $category = $this->upsertCategory($data, $run, $counters);
            $subcategory = $this->upsertSubcategory($data, $category, $run, $counters, $downloadAssets);
            $keptProductIds = [];

            foreach ($data->products as $productData) {
                $product = $this->upsertProduct($productData, $category, $subcategory, $run, $counters, $downloadAssets);
                $this->syncSpecifications($product, $productData, $run, $counters);
                if ($importDownloads) {
                    $this->syncDownloads($product, $productData, $run, $counters, $downloadAssets);
                } else {
                    $this->purgeDownloads($product, $run);
                }
                $this->syncImages($product, $productData, $run, $counters, $downloadAssets);
                $keptProductIds[] = 'product:' . $productData->sourceId;
            }

            if ($keptProductIds !== []) {
                $this->pruneMissingProducts($subcategory, $run, $keptProductIds);
            }
        });

        return $counters;
    }

    protected function upsertCategory(ConarCategoryData $data, ImportRun $run, array &$counters): ProductCategory
    {
        $category = ProductCategory::query()
            ->where('source_provider', $this->provider)
            ->where('source_id', 'family:' . Str::slug($data->familyName))
            ->first();

        if (!$category) {
            $category = ProductCategory::query()
                ->where('slug', Str::slug($data->familyName))
                ->first();
        }

        $payload = [
            'source_provider' => $this->provider,
            'source_id' => 'family:' . Str::slug($data->familyName),
            'source_url' => $data->sourceUrl,
            'last_import_run_id' => $run->id,
            'name' => $data->familyName,
            'slug' => Str::slug($data->familyName),
            'order' => 'A',
            'is_active' => true,
        ];

        return $this->persistModel(
            model: $category ?? new ProductCategory(),
            payload: $payload,
            run: $run,
            counters: $counters,
            createKey: 'categories_created',
            updateKey: 'categories_updated',
            fields: ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'name', 'slug', 'order', 'description', 'image', 'is_active'],
        );
    }

    protected function upsertSubcategory(
        ConarCategoryData $data,
        ProductCategory $category,
        ImportRun $run,
        array &$counters,
        bool $downloadAssets
    ): ProductSubcategory {
        $subcategory = ProductSubcategory::query()
            ->where('source_provider', $this->provider)
            ->where('source_id', 'subcategory:' . $data->sourceId)
            ->first();

        if (!$subcategory) {
            $subcategory = ProductSubcategory::query()
                ->where('category_id', $category->id)
                ->where('slug', Str::slug($data->subcategoryName))
                ->first();
        }

        $downloadedFiles = [];
        $imagePath = $subcategory?->image;

        if ($downloadAssets && $data->subcategoryImageUrl) {
            $download = $this->downloader->downloadWithMeta(
                $data->subcategoryImageUrl,
                'subcategories',
                $data->subcategoryName
            );
            $imagePath = $download['path'];
            if ($download['created']) {
                $downloadedFiles[] = $imagePath;
            }
        }

        $payload = [
            'source_provider' => $this->provider,
            'source_id' => 'subcategory:' . $data->sourceId,
            'source_url' => $data->sourceUrl,
            'last_import_run_id' => $run->id,
            'category_id' => $category->id,
            'name' => $data->subcategoryName,
            'slug' => Str::slug($data->subcategoryName),
            'image' => $imagePath,
            'order' => 'A',
            'is_active' => true,
            'show_in_home' => false,
        ];

        return $this->persistModel(
            model: $subcategory ?? new ProductSubcategory(),
            payload: $payload,
            run: $run,
            counters: $counters,
            createKey: 'subcategories_created',
            updateKey: 'subcategories_updated',
            fields: ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'category_id', 'name', 'slug', 'image', 'order', 'is_active', 'show_in_home'],
            meta: ['downloaded_files' => $downloadedFiles],
        );
    }

    protected function upsertProduct(
        ConarProductData $productData,
        ProductCategory $category,
        ProductSubcategory $subcategory,
        ImportRun $run,
        array &$counters,
        bool $downloadAssets
    ): Product {
        $product = Product::query()
            ->where('source_provider', $this->provider)
            ->where('source_id', 'product:' . $productData->sourceId)
            ->first();

        if (!$product) {
            $product = Product::query()
                ->where('subcategory_id', $subcategory->id)
                ->where('slug', Str::slug($productData->name))
                ->first();
        }

        $downloadedFiles = [];
        $imagePath = $product?->image;

        if ($downloadAssets && $productData->mainImageUrl) {
            $download = $this->downloader->downloadWithMeta(
                $productData->mainImageUrl,
                'products',
                $productData->code ?: $productData->name
            );
            $imagePath = $download['path'];
            if ($download['created']) {
                $downloadedFiles[] = $imagePath;
            }
        }

        $payload = [
            'source_provider' => $this->provider,
            'source_id' => 'product:' . $productData->sourceId,
            'source_url' => $productData->sourceUrl,
            'last_import_run_id' => $run->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => $productData->name,
            'slug' => Str::slug($productData->name),
            'subtitle' => $productData->subtitle,
            'short_description' => $productData->shortDescription,
            'description' => $productData->description,
            'image' => $imagePath,
            'order' => 'A',
            'is_active' => true,
            'is_featured' => false,
            'show_in_home' => false,
        ];

        return $this->persistModel(
            model: $product ?? new Product(),
            payload: $payload,
            run: $run,
            counters: $counters,
            createKey: 'products_created',
            updateKey: 'products_updated',
            fields: ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'category_id', 'subcategory_id', 'name', 'slug', 'subtitle', 'brand', 'brand_logo', 'short_description', 'description', 'image', 'video', 'video_url', 'order', 'is_active', 'is_featured', 'show_in_home'],
            meta: ['downloaded_files' => $downloadedFiles],
        );
    }

    protected function syncSpecifications(Product $product, ConarProductData $productData, ImportRun $run, array &$counters): void
    {
        $keptSpecIds = [];

        foreach ($productData->specifications as $specData) {
            $spec = ProductSpecification::query()
                ->where('product_id', $product->id)
                ->where('source_provider', $this->provider)
                ->where('source_id', 'spec:' . $specData->sourceId)
                ->first();

            if (!$spec) {
                $spec = ProductSpecification::query()
                    ->where('product_id', $product->id)
                    ->where('name', $specData->name)
                    ->where('value', $specData->value)
                    ->first();
            }

            $payload = [
                'source_provider' => $this->provider,
                'source_id' => 'spec:' . $specData->sourceId,
                'source_url' => $productData->sourceUrl,
                'last_import_run_id' => $run->id,
                'product_id' => $product->id,
                'name' => $specData->name,
                'value' => $specData->value,
                'order' => $specData->order ?: 'A',
                'is_active' => true,
            ];

            $this->persistModel(
                model: $spec ?? new ProductSpecification(),
                payload: $payload,
                run: $run,
                counters: $counters,
                createKey: 'specifications_created',
                updateKey: 'specifications_updated',
                fields: ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'name', 'value', 'order', 'is_active'],
            );

            $keptSpecIds[] = $payload['source_id'];
        }

        if ($keptSpecIds !== []) {
            $this->pruneMissingChildren(
                ProductSpecification::class,
                $product,
                $run,
                $keptSpecIds,
                ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'name', 'value', 'order', 'is_active'],
            );
        }
    }

    protected function syncDownloads(
        Product $product,
        ConarProductData $productData,
        ImportRun $run,
        array &$counters,
        bool $downloadAssets
    ): void {
        $keptDownloadIds = [];

        foreach ($productData->downloads as $index => $downloadData) {
            $download = ProductDownload::query()
                ->where('product_id', $product->id)
                ->where('source_provider', $this->provider)
                ->where('source_id', 'download:' . $downloadData->sourceId)
                ->first();

            $downloadedFiles = [];
            $filePath = $download?->file;

            if ($downloadAssets) {
                $downloaded = $this->downloader->downloadWithMeta(
                    $downloadData->url,
                    'product-downloads',
                    ($productData->code ?: $productData->name) . '-' . $downloadData->label
                );
                $filePath = $downloaded['path'];
                if ($downloaded['created']) {
                    $downloadedFiles[] = $filePath;
                }
            }

            $payload = [
                'source_provider' => $this->provider,
                'source_id' => 'download:' . $downloadData->sourceId,
                'source_url' => $downloadData->url,
                'last_import_run_id' => $run->id,
                'product_id' => $product->id,
                'name' => $downloadData->label,
                'file' => $filePath,
                'type' => $downloadData->type ?: 'PDF',
                'size' => $downloadData->size,
                'order' => $downloadData->order ?: 'A',
                'is_active' => true,
                'is_principal' => $download?->is_principal ?? ($index === 0),
            ];

            $this->persistModel(
                model: $download ?? new ProductDownload(),
                payload: $payload,
                run: $run,
                counters: $counters,
                createKey: 'downloads_created',
                updateKey: 'downloads_updated',
                fields: ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'name', 'file', 'type', 'size', 'order', 'is_active', 'is_principal'],
                meta: ['downloaded_files' => $downloadedFiles],
            );

            $keptDownloadIds[] = $payload['source_id'];
        }

        if ($keptDownloadIds !== []) {
            $this->pruneMissingChildren(
                ProductDownload::class,
                $product,
                $run,
                $keptDownloadIds,
                ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'name', 'file', 'type', 'size', 'order', 'is_active', 'is_principal'],
            );
        }
    }

    protected function purgeDownloads(Product $product, ImportRun $run): void
    {
        $this->pruneMissingChildren(
            ProductDownload::class,
            $product,
            $run,
            [],
            ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'name', 'file', 'type', 'size', 'order', 'is_active', 'is_principal'],
        );
    }

    protected function syncImages(
        Product $product,
        ConarProductData $productData,
        ImportRun $run,
        array &$counters,
        bool $downloadAssets
    ): void {
        $keptImageIds = [];

        foreach ($productData->images as $imageData) {
            $image = ProductImage::query()
                ->where('product_id', $product->id)
                ->where('source_provider', $this->provider)
                ->where('source_id', 'image:' . $imageData->sourceId)
                ->first();

            $downloadedFiles = [];
            $imagePath = $image?->image;

            if ($downloadAssets) {
                $downloaded = $this->downloader->downloadWithMeta(
                    $imageData->url,
                    'images/products',
                    ($productData->code ?: $productData->name) . '-' . ($imageData->alt ?: 'gallery')
                );
                $imagePath = $downloaded['path'];
                if ($downloaded['created']) {
                    $downloadedFiles[] = $imagePath;
                }
            }

            $payload = [
                'source_provider' => $this->provider,
                'source_id' => 'image:' . $imageData->sourceId,
                'source_url' => $imageData->url,
                'last_import_run_id' => $run->id,
                'product_id' => $product->id,
                'image' => $imagePath,
                'order' => $imageData->order ?: 'A',
            ];

            $this->persistModel(
                model: $image ?? new ProductImage(),
                payload: $payload,
                run: $run,
                counters: $counters,
                createKey: 'images_created',
                updateKey: 'images_updated',
                fields: ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'image', 'video_url', 'order'],
                meta: ['downloaded_files' => $downloadedFiles],
            );

            $keptImageIds[] = $payload['source_id'];
        }

        if ($keptImageIds !== []) {
            $this->pruneMissingChildren(
                ProductImage::class,
                $product,
                $run,
                $keptImageIds,
                ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'image', 'video_url', 'order'],
            );
        }
    }

    protected function persistModel(
        Model $model,
        array $payload,
        ImportRun $run,
        array &$counters,
        string $createKey,
        string $updateKey,
        array $fields,
        array $meta = []
    ): Model {
        $exists = $model->exists;
        $beforeState = $exists ? Arr::only($model->getAttributes(), $fields) : [];

        $model->fill($payload);
        $model->save();

        $afterState = Arr::only($model->getAttributes(), $fields);

        if ($exists) {
            $counters[$updateKey]++;
            $this->recorder->recordUpdate($run, $model, $beforeState, $afterState, $meta);
        } else {
            $counters[$createKey]++;
            $this->recorder->recordCreate($run, $model, $afterState, $meta);
        }

        return $model;
    }

    /**
     * @param  string[]  $keptSourceIds
     */
    protected function pruneMissingProducts(
        ProductSubcategory $subcategory,
        ImportRun $run,
        array $keptSourceIds
    ): void {
        $query = Product::query()
            ->where('subcategory_id', $subcategory->id)
            ->where('source_provider', $this->provider);

        if ($keptSourceIds !== []) {
            $query->whereNotIn('source_id', $keptSourceIds);
        }

        $staleProducts = $query->get();

        foreach ($staleProducts as $staleProduct) {
            $this->pruneMissingChildren(
                ProductSpecification::class,
                $staleProduct,
                $run,
                [],
                ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'name', 'value', 'order', 'is_active'],
            );
            $this->pruneMissingChildren(
                ProductDownload::class,
                $staleProduct,
                $run,
                [],
                ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'name', 'file', 'type', 'size', 'order', 'is_active', 'is_principal'],
            );
            $this->pruneMissingChildren(
                ProductImage::class,
                $staleProduct,
                $run,
                [],
                ['source_provider', 'source_id', 'source_url', 'last_import_run_id', 'product_id', 'image', 'video_url', 'order'],
            );

            $beforeState = Arr::only($staleProduct->getAttributes(), [
                'source_provider',
                'source_id',
                'source_url',
                'last_import_run_id',
                'category_id',
                'subcategory_id',
                'name',
                'slug',
                'subtitle',
                'brand',
                'brand_logo',
                'short_description',
                'description',
                'image',
                'video',
                'video_url',
                'order',
                'is_active',
                'is_featured',
                'show_in_home',
            ]);

            $this->recorder->recordDelete($run, $staleProduct, $beforeState);
            $staleProduct->delete();
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  string[]  $keptSourceIds
     * @param  string[]  $fields
     */
    protected function pruneMissingChildren(
        string $modelClass,
        Product $product,
        ImportRun $run,
        array $keptSourceIds,
        array $fields
    ): void {
        $query = $modelClass::query()
            ->where('product_id', $product->id)
            ->where('source_provider', $this->provider);

        if ($keptSourceIds !== []) {
            $query->whereNotIn('source_id', $keptSourceIds);
        }

        $staleRecords = $query->get();

        foreach ($staleRecords as $staleRecord) {
            $beforeState = Arr::only($staleRecord->getAttributes(), $fields);

            $this->recorder->recordDelete($run, $staleRecord, $beforeState);
            $staleRecord->delete();
        }
    }
}
