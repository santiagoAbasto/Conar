<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RemoteAssetDownloader
{
    public function __construct(
        protected ScraperHttpClient $http
    ) {
    }

    public function downloadToPublicDisk(string $url, string $directory, string $basename): string
    {
        return $this->downloadWithMeta($url, $directory, $basename)['path'];
    }

    /**
     * @return array{path:string,created:bool}
     */
    public function downloadWithMeta(string $url, string $directory, string $basename): array
    {
        $payload = $this->http->downloadBinary($url);
        $extension = $this->detectExtension($url, $payload['content_type']);
        $filename = Str::slug($basename) . '-' . substr(sha1($url), 0, 12) . '.' . $extension;
        $path = trim($directory, '/') . '/' . $filename;
        $created = false;

        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $payload['body']);
            $created = true;
        }

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function detectExtension(string $url, ?string $contentType): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return $extension;
        }

        return match (true) {
            str_contains((string) $contentType, 'pdf') => 'pdf',
            str_contains((string) $contentType, 'png') => 'png',
            str_contains((string) $contentType, 'webp') => 'webp',
            str_contains((string) $contentType, 'gif') => 'gif',
            default => 'jpg',
        };
    }
}
