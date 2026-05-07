<?php

namespace App\Console\Commands;

use App\Jobs\RunConarCategoryImportJob;
use App\Services\Importers\ConarCategoryImportOrchestrator;
use App\Services\Importers\ImportRunRecorder;
use Illuminate\Console\Command;

class ImportConarCategoryCommand extends Command
{
    protected $signature = 'catalog:import-conar-category
        {url : URL de la categoría de CONAR a importar}
        {--family=Caudal : Nombre de la familia principal local}
        {--without-assets : No descargar imágenes ni PDFs}
        {--without-downloads : No guardar descargas del producto}
        {--dry-run : Analiza y registra la corrida sin persistir catálogo}
        {--queue : Encola la corrida en background}';

    protected $description = 'Hace scraping controlado de una categoría de CONAR e importa datos al catálogo local.';

    public function handle(
        ImportRunRecorder $recorder,
        ConarCategoryImportOrchestrator $orchestrator
    ): int {
        $url = (string) $this->argument('url');
        $family = (string) $this->option('family');
        $dryRun = (bool) $this->option('dry-run');
        $downloadAssets = !$this->option('without-assets');
        $importDownloads = !$this->option('without-downloads');

        $run = $recorder->start(
            provider: 'conar',
            sourceType: 'category',
            scope: $family,
            sourceUrl: $url,
            options: [
                'family' => $family,
                'download_assets' => $downloadAssets,
                'import_downloads' => $importDownloads,
            ],
            dryRun: $dryRun,
        );

        $this->line("Import run #{$run->id} creada.");

        if ($this->option('queue')) {
            RunConarCategoryImportJob::dispatch($run->id, $url, $family, $downloadAssets, $importDownloads);
            $this->info('La importación fue enviada a la cola.');

            return self::SUCCESS;
        }

        try {
            $counters = $orchestrator->run($run, $url, $family, $downloadAssets, $importDownloads);
        } catch (\Throwable $exception) {
            $this->error("Falló la importación #{$run->id}: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Importación #{$run->id} completada.");
        foreach ($counters as $key => $value) {
            $this->line("- {$key}: {$value}");
        }

        return self::SUCCESS;
    }
}
