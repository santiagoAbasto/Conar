<?php

namespace App\Services\Importers;

use App\Models\ImportRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportRollbackService
{
    public function __construct(
        protected ImportRunRecorder $recorder,
    ) {
    }

    public function rollback(ImportRun $run): array
    {
        $counters = [
            'deleted_created_records' => 0,
            'restored_updated_records' => 0,
            'restored_deleted_records' => 0,
            'deleted_files' => 0,
        ];

        DB::transaction(function () use ($run, &$counters) {
            $items = $run->items()->orderByDesc('id')->get();

            foreach ($items as $item) {
                $modelClass = $item->entity_type;

                if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                    continue;
                }

                /** @var Model|null $model */
                $model = $item->entity_id ? $modelClass::query()->find($item->entity_id) : null;

                if ($item->action === 'create') {
                    if ($model) {
                        $model->delete();
                        $counters['deleted_created_records']++;
                    }

                    $counters['deleted_files'] += $this->deleteFiles($item->meta['downloaded_files'] ?? []);
                    continue;
                }

                if ($item->action === 'update' && $model) {
                    $beforeState = $item->before_state ?? [];

                    if ($beforeState !== []) {
                        $model->fill($beforeState);
                        $model->save();
                        $counters['restored_updated_records']++;
                    }

                    $counters['deleted_files'] += $this->deleteFiles($item->meta['downloaded_files'] ?? []);
                }

                if ($item->action === 'delete') {
                    $beforeState = $item->before_state ?? [];

                    if ($beforeState === []) {
                        continue;
                    }

                    $restored = new $modelClass();
                    $restored->fill($beforeState);
                    $restored->save();
                    $counters['restored_deleted_records']++;
                }
            }
        });

        $this->recorder->log($run, 'warning', 'Rollback ejecutado', $counters);
        $this->recorder->markRolledBack($run, $counters);

        return $counters;
    }

    protected function deleteFiles(array $paths): int
    {
        $deleted = 0;

        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deleted++;
            }
        }

        return $deleted;
    }
}
