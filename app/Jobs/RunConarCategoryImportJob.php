<?php

namespace App\Jobs;

use App\Models\ImportRun;
use App\Services\Importers\ConarCategoryImportOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunConarCategoryImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $runId,
        public string $url,
        public ?string $familyName = null,
        public bool $downloadAssets = true,
        public bool $importDownloads = true,
    ) {
    }

    public function handle(ConarCategoryImportOrchestrator $orchestrator): void
    {
        $run = ImportRun::query()->findOrFail($this->runId);

        $orchestrator->run(
            run: $run,
            url: $this->url,
            familyName: $this->familyName,
            downloadAssets: $this->downloadAssets,
            importDownloads: $this->importDownloads,
        );
    }
}
