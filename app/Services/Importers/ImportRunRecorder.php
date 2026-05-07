<?php

namespace App\Services\Importers;

use App\Models\ImportRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ImportRunRecorder
{
    public function start(
        string $provider,
        string $sourceType,
        ?string $scope,
        ?string $sourceUrl,
        array $options = [],
        bool $dryRun = false
    ): ImportRun {
        return ImportRun::create([
            'source_provider' => $provider,
            'source_type' => $sourceType,
            'scope' => $scope,
            'source_url' => $sourceUrl,
            'options' => $options,
            'status' => 'running',
            'dry_run' => $dryRun,
            'started_at' => now(),
        ]);
    }

    public function log(ImportRun $run, string $level, string $message, array $context = []): void
    {
        $run->logs()->create([
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    public function recordCreate(
        ImportRun $run,
        Model $model,
        array $afterState,
        array $meta = []
    ): void {
        $run->items()->create([
            'entity_type' => $model::class,
            'entity_id' => $model->getKey(),
            'action' => 'create',
            'source_key' => $afterState['source_id'] ?? null,
            'entity_label' => $afterState['name'] ?? $afterState['title'] ?? null,
            'before_state' => null,
            'after_state' => $afterState,
            'meta' => $meta,
        ]);
    }

    public function recordUpdate(
        ImportRun $run,
        Model $model,
        array $beforeState,
        array $afterState,
        array $meta = []
    ): void {
        $run->items()->create([
            'entity_type' => $model::class,
            'entity_id' => $model->getKey(),
            'action' => 'update',
            'source_key' => $afterState['source_id'] ?? $beforeState['source_id'] ?? null,
            'entity_label' => $afterState['name'] ?? $beforeState['name'] ?? null,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'meta' => $meta,
        ]);
    }

    public function recordDelete(
        ImportRun $run,
        Model $model,
        array $beforeState,
        array $meta = []
    ): void {
        $run->items()->create([
            'entity_type' => $model::class,
            'entity_id' => $model->getKey(),
            'action' => 'delete',
            'source_key' => $beforeState['source_id'] ?? null,
            'entity_label' => $beforeState['name'] ?? $beforeState['title'] ?? null,
            'before_state' => $beforeState,
            'after_state' => null,
            'meta' => $meta,
        ]);
    }

    public function markCompleted(ImportRun $run, array $counters = []): void
    {
        $run->forceFill([
            'status' => 'completed',
            'counters' => $counters,
            'finished_at' => Carbon::now(),
            'error_message' => null,
        ])->save();
    }

    public function markFailed(ImportRun $run, \Throwable $exception): void
    {
        $this->log($run, 'error', $exception->getMessage(), [
            'exception' => $exception::class,
        ]);

        $run->forceFill([
            'status' => 'failed',
            'finished_at' => Carbon::now(),
            'error_message' => $exception->getMessage(),
        ])->save();
    }

    public function markRolledBack(ImportRun $run, array $counters = []): void
    {
        $run->forceFill([
            'status' => 'rolled_back',
            'counters' => $counters,
            'finished_at' => Carbon::now(),
        ])->save();
    }
}
