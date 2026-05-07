<?php

namespace App\DTOs\Scraping\Conar;

final readonly class ConarImageData
{
    public function __construct(
        public string $sourceId,
        public string $url,
        public ?string $alt = null,
        public ?string $order = null,
    ) {
    }
}
