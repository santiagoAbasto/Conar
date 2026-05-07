<?php

namespace App\Services\Scraping;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ScraperHttpClient
{
    public function getHtml(string $url): string
    {
        $response = Http::retry(3, 500)
            ->connectTimeout(10)
            ->timeout(20)
            ->accept('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
            ->withUserAgent($this->userAgent())
            ->get($url);

        $response->throw();

        return (string) $response->body();
    }

    /**
     * @return array{body:string,content_type:?string}
     *
     * @throws ConnectionException|RequestException
     */
    public function downloadBinary(string $url): array
    {
        $response = Http::retry(3, 500)
            ->connectTimeout(10)
            ->timeout(30)
            ->withUserAgent($this->userAgent())
            ->get($url);

        $response->throw();

        return [
            'body' => (string) $response->body(),
            'content_type' => $response->header('Content-Type'),
        ];
    }

    protected function userAgent(): string
    {
        return 'CONAR Catalog Importer/1.0 (+https://localhost)';
    }
}
