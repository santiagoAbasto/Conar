<?php

namespace App\Services\Importers;

use App\Models\ImportRun;
use App\Services\Scraping\Conar\ConarCategoryCrawler;

class ConarCategoryImportOrchestrator
{
    public function __construct(
        protected ConarCategoryCrawler $crawler,
        protected ConarCatalogImporter $importer,
        protected ImportRunRecorder $recorder,
    ) {
    }

    public function run(
        ImportRun $run,
        string $url,
        ?string $familyName = null,
        bool $downloadAssets = true,
        bool $importDownloads = true
    ): array
    {
        try {
            $this->recorder->log($run, 'info', 'Iniciando scraping de categoría', [
                'url' => $url,
                'family' => $familyName,
                'import_downloads' => $importDownloads,
            ]);

            $data = $this->crawler->crawl($url, $familyName);

            $this->recorder->log($run, 'info', 'Categoría parseada correctamente', [
                'products' => count($data->products),
                'subcategory' => $data->subcategoryName,
            ]);

            if ($run->dry_run) {
                $counters = [
                    'products_detected' => count($data->products),
                ];
                $this->recorder->markCompleted($run, $counters);

                return $counters;
            }

            $counters = $this->importer->import($data, $run, $downloadAssets, $importDownloads);
            $this->recorder->markCompleted($run, $counters);

            return $counters;
        } catch (\Throwable $exception) {
            $this->recorder->markFailed($run, $exception);

            throw $exception;
        }
    }
}
