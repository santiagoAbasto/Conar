<?php

use App\Support\Translation;

if (!function_exists('tr_text')) {
    function tr_text(mixed $value, ?string $locale = null): ?string
    {
        return Translation::text($value, $locale);
    }
}

if (!function_exists('tr_payload')) {
    function tr_payload(mixed $value): array
    {
        return Translation::payload($value);
    }
}

if (!function_exists('localized_url')) {
    function localized_url(string $locale): string
    {
        return Translation::currentUrlFor($locale);
    }
}
