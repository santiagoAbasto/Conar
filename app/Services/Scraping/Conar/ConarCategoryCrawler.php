<?php

namespace App\Services\Scraping\Conar;

use App\DTOs\Scraping\Conar\ConarCategoryData;
use App\Parsers\ConarCategoryParser;
use App\Services\Scraping\ScraperHttpClient;

class ConarCategoryCrawler
{
    public function __construct(
        protected ScraperHttpClient $http,
        protected ConarCategoryParser $parser,
        protected ConarCategoryNormalizer $normalizer,
    ) {
    }

    public function crawl(string $url, ?string $familyName = null): ConarCategoryData
    {
        $html = $this->http->getHtml($url);
        $parsed = $this->parser->parse($html, $url, $familyName);

        return $this->normalizer->normalize($parsed);
    }
}
