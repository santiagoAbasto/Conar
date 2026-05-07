<?php

namespace App\DTOs\Scraping\Conar;

final readonly class ConarDownloadData
{
    public function __construct(
        public string $sourceId,
        public string $label,
        public string $url,
        public ?string $type = null,
        public ?string $size = null,
        public ?string $order = null,
    ) {
    }
}
