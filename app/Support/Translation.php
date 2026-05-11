<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class Translation
{
    public const LOCALES = ['es', 'pt', 'en'];

    public static function locale(): string
    {
        $locale = App::getLocale();

        return in_array($locale, self::LOCALES, true) ? $locale : 'es';
    }

    public static function decode(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public static function text(mixed $value, ?string $locale = null): ?string
    {
        $value = self::decode($value);

        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return (string) $value;
        }

        $locale ??= self::locale();

        return Arr::get($value, $locale)
            ?? Arr::get($value, 'es')
            ?? Arr::first(array_filter($value, fn ($item) => filled($item)))
            ?? null;
    }

    public static function payload(mixed $value): array
    {
        $value = self::decode($value);

        if (is_array($value)) {
            $es = self::text($value, 'es') ?? '';

            return [
                'es' => $value['es'] ?? $es,
                'pt' => $value['pt'] ?? $es,
                'en' => $value['en'] ?? $es,
            ];
        }

        $value = $value ? (string) $value : '';

        return [
            'es' => $value,
            'pt' => $value,
            'en' => $value,
        ];
    }

    public static function input(mixed $value): array
    {
        $payload = self::payload($value);

        return [
            'es' => trim((string) ($payload['es'] ?? '')),
            'pt' => trim((string) ($payload['pt'] ?? '')),
            'en' => trim((string) ($payload['en'] ?? '')),
        ];
    }

    public static function currentUrlFor(string $locale): string
    {
        $segments = request()->segments();

        if ($segments && in_array($segments[0], self::LOCALES, true)) {
            array_shift($segments);
        }

        return url(implode('/', $segments));
    }
}
