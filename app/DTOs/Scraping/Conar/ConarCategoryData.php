<?php

namespace App\DTOs\Scraping\Conar;

final readonly class ConarCategoryData
{
    /**
     * @param  ConarProductData[]  $products
     */
    public function __construct(
        public string $sourceId,
        public string $sourceUrl,
        public string $familyName,
        public string $subcategoryName,
        public ?string $subcategoryImageUrl,
        public array $products,
        public array $rawPayload = [],
    ) {
    }
}
