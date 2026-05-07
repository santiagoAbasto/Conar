<?php

namespace App\Services\Scraping\Conar;

use App\DTOs\Scraping\Conar\ConarCategoryData;
use App\DTOs\Scraping\Conar\ConarDownloadData;
use App\DTOs\Scraping\Conar\ConarImageData;
use App\DTOs\Scraping\Conar\ConarProductData;
use App\DTOs\Scraping\Conar\ConarSpecificationData;
use Illuminate\Support\Collection;

class ConarCategoryNormalizer
{
    public function normalize(ConarCategoryData $data): ConarCategoryData
    {
        return new ConarCategoryData(
            sourceId: trim($data->sourceId),
            sourceUrl: trim($data->sourceUrl),
            familyName: $this->clean($data->familyName) ?? 'Caudal',
            subcategoryName: $this->clean($data->subcategoryName) ?? 'Categoría importada',
            subcategoryImageUrl: $data->subcategoryImageUrl ? trim($data->subcategoryImageUrl) : null,
            products: collect($data->products)
                ->map(fn (ConarProductData $product) => $this->normalizeProduct($product))
                ->filter()
                ->values()
                ->all(),
            rawPayload: $data->rawPayload,
        );
    }

    protected function normalizeProduct(ConarProductData $product): ?ConarProductData
    {
        $name = $this->clean($product->name);

        if (!$name) {
            return null;
        }

        return new ConarProductData(
            sourceId: trim($product->sourceId),
            sourceUrl: trim($product->sourceUrl),
            code: $this->clean($product->code),
            name: $name,
            subtitle: $this->clean($product->subtitle),
            shortDescription: $this->clean($product->shortDescription),
            description: $this->cleanMultiline($product->description),
            mainImageUrl: $product->mainImageUrl ? trim($product->mainImageUrl) : null,
            specifications: $this->normalizeSpecifications($product->specifications),
            downloads: $this->normalizeDownloads($product->downloads),
            images: $this->normalizeImages($product->images),
            rawPayload: $product->rawPayload,
        );
    }

    /**
     * @param  ConarSpecificationData[]  $items
     * @return ConarSpecificationData[]
     */
    protected function normalizeSpecifications(array $items): array
    {
        return collect($items)
            ->map(function (ConarSpecificationData $item) {
                $value = $this->clean($item->value);

                if (!$value) {
                    return null;
                }

                return new ConarSpecificationData(
                    sourceId: trim($item->sourceId),
                    name: $this->clean($item->name) ?? 'Detalle',
                    value: $value,
                    order: $item->order,
                );
            })
            ->filter()
            ->unique(fn (ConarSpecificationData $item) => $item->sourceId)
            ->values()
            ->all();
    }

    /**
     * @param  ConarDownloadData[]  $items
     * @return ConarDownloadData[]
     */
    protected function normalizeDownloads(array $items): array
    {
        return collect($items)
            ->map(function (ConarDownloadData $item) {
                if ($item->url === '') {
                    return null;
                }

                return new ConarDownloadData(
                    sourceId: trim($item->sourceId),
                    label: $this->clean($item->label) ?? 'Descarga técnica',
                    url: trim($item->url),
                    type: $this->clean($item->type),
                    size: $this->clean($item->size),
                    order: $item->order,
                );
            })
            ->filter()
            ->unique(fn (ConarDownloadData $item) => $item->sourceId)
            ->values()
            ->all();
    }

    /**
     * @param  ConarImageData[]  $items
     * @return ConarImageData[]
     */
    protected function normalizeImages(array $items): array
    {
        return collect($items)
            ->map(function (ConarImageData $item) {
                if ($item->url === '') {
                    return null;
                }

                return new ConarImageData(
                    sourceId: trim($item->sourceId),
                    url: trim($item->url),
                    alt: $this->clean($item->alt),
                    order: $item->order,
                );
            })
            ->filter()
            ->unique(fn (ConarImageData $item) => $item->sourceId)
            ->values()
            ->all();
    }

    protected function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    }

    protected function cleanMultiline(?string $value): ?string
    {
        $clean = $this->clean($value);

        if ($clean === null) {
            return null;
        }

        return Collection::make(preg_split('/\s*\|\s*|\n+/u', $clean) ?: [])
            ->map(fn (string $line) => $this->clean($line))
            ->filter()
            ->unique()
            ->implode("\n");
    }
}
