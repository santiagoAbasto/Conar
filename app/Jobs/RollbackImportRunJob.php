<?php

namespace App\Jobs;

use App\Models\ImportRun;
use App\Services\Importers\ImportRollbackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RollbackImportRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $runId,
    ) {
    }

    public function handle(ImportRollbackService $rollbackService): void
    {
        $run = ImportRun::query()->findOrFail($this->runId);

        $rollbackService->rollback($run);
    }
}
