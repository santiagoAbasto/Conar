<?php

namespace App\DTOs\Scraping\Conar;

final readonly class ConarProductData
{
    /**
     * @param  ConarSpecificationData[]  $specifications
     * @param  ConarDownloadData[]  $downloads
     * @param  ConarImageData[]  $images
     */
    public function __construct(
        public string $sourceId,
        public string $sourceUrl,
        public ?string $code,
        public string $name,
        public ?string $subtitle,
        public ?string $shortDescription,
        public ?string $description,
        public ?string $mainImageUrl,
        public array $specifications,
        public array $downloads,
        public array $images,
        public array $rawPayload = [],
    ) {
    }
}
