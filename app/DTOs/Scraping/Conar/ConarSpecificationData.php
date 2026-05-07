<?php

namespace App\DTOs\Scraping\Conar;

final readonly class ConarSpecificationData
{
    public function __construct(
        public string $sourceId,
        public string $name,
        public string $value,
        public ?string $order = null,
    ) {
    }
}
