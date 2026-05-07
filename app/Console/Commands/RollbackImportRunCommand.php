<?php

namespace App\Console\Commands;

use App\Jobs\RollbackImportRunJob;
use App\Models\ImportRun;
use App\Services\Importers\ImportRollbackService;
use Illuminate\Console\Command;

class RollbackImportRunCommand extends Command
{
    protected $signature = 'catalog:rollback-import
        {runId : ID de la corrida a revertir}
        {--queue : Encola el rollback en background}';

    protected $description = 'Revierte una corrida de importación registrada en import_runs.';

    public function handle(ImportRollbackService $rollbackService): int
    {
        $run = ImportRun::query()->find($this->argument('runId'));

        if (!$run) {
            $this->error('No encontré esa corrida.');

            return self::FAILURE;
        }

        if ($this->option('queue')) {
            RollbackImportRunJob::dispatch($run->id);
            $this->info("Rollback de la corrida #{$run->id} enviado a la cola.");

            return self::SUCCESS;
        }

        try {
            $counters = $rollbackService->rollback($run);
        } catch (\Throwable $exception) {
            $this->error("Falló el rollback #{$run->id}: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Rollback #{$run->id} completado.");
        foreach ($counters as $key => $value) {
            $this->line("- {$key}: {$value}");
        }

        return self::SUCCESS;
    }
}
