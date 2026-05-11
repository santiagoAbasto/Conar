<?php

namespace App\View\Composers;

use App\Models\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Factory as ViewFactory;

class SeoMetaComposer
{
    private const SITE_NAME = 'CONAR';
    private const THEME_COLOR = '#136CBA';

    private const DEFAULT_TITLE = 'CONAR - Instrumentación Industrial';
    private const DEFAULT_DESCRIPTION = 'CONAR - Equipos de instrumentación, medición y control industrial.';

    private const DYNAMIC_DETAIL_ROUTES = [
        'products.category',
        'products.subcategory',
        'products.show',
        'industries.show',
        'posts.show',
    ];

    public function __construct(private readonly ViewFactory $views)
    {
    }

    public function compose(View $view): void
    {
        $routeName = Route::currentRouteName() ?? '';
        $pageKey = $this->resolvePageKey($routeName);
        $seo = $pageKey ? SeoMeta::query()->where('page', $pageKey)->first() : null;
        $viewData = $view->getData();

        $pageTitle = $this->section('title');
        $adminTitle = $seo?->title;
        $metaTitle = $this->resolveTitle($routeName, $pageTitle, $adminTitle);

        $metaDescription = $this->section('meta_description')
            ?: $seo?->description
            ?: __(self::DEFAULT_DESCRIPTION);

        $ogImage = $seo?->og_image_url ?: asset('favicon/web-app-manifest-512x512.png');

        $view->with([
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaKeywords' => $seo?->keywords,
            'metaCanonical' => url()->current(),
            'metaRobots' => $viewData['metaRobots'] ?? 'index, follow',
            'metaOgType' => $viewData['metaOgType'] ?? 'website',
            'metaSiteName' => self::SITE_NAME,
            'metaLocale' => $this->resolveOgLocale(app()->getLocale()),
            'metaOgImage' => $ogImage,
            'metaOgImageAlt' => $viewData['metaOgImageAlt'] ?? self::SITE_NAME,
            'metaOgImageWidth' => $seo?->og_image_url ? 1200 : 512,
            'metaOgImageHeight' => $seo?->og_image_url ? 630 : 512,
            'metaTwitterCard' => 'summary_large_image',
            'metaThemeColor' => self::THEME_COLOR,
            'metaApplicationName' => self::SITE_NAME,
            'metaStructuredData' => $this->structuredDataJson($viewData['structuredData'] ?? null),
        ]);
    }

    private function resolvePageKey(string $routeName): ?string
    {
        return match (true) {
            $routeName === 'home' => 'home',
            $routeName === 'nosotros' => 'nosotros',
            $routeName === 'products.index' => 'products',
            $routeName === 'products.category' => 'categories',
            $routeName === 'products.subcategory' => 'subcategories',
            str_starts_with($routeName, 'industries') => 'industries',
            str_starts_with($routeName, 'posts') => 'novedades',
            str_starts_with($routeName, 'contact') => 'contacto',
            $routeName === 'presupuesto' => 'presupuesto',
            $routeName === 'search' => 'buscador',
            default => null,
        };
    }

    private function resolveTitle(string $routeName, ?string $pageTitle, ?string $adminTitle): string
    {
        if (! in_array($routeName, self::DYNAMIC_DETAIL_ROUTES, true) && filled($adminTitle)) {
            return $adminTitle;
        }

        if (filled($pageTitle)) {
            return "{$pageTitle} - " . self::SITE_NAME;
        }

        return $adminTitle ?: __(self::DEFAULT_TITLE);
    }

    private function section(string $name): ?string
    {
        if (! $this->views->hasSection($name)) {
            return null;
        }

        $content = trim($this->views->yieldContent($name));

        return $content !== '' ? $content : null;
    }

    private function resolveOgLocale(string $locale): string
    {
        return match ($locale) {
            'pt' => 'pt_BR',
            'en' => 'en_US',
            default => 'es_BO',
        };
    }

    private function structuredDataJson(mixed $structuredData): ?string
    {
        if (blank($structuredData)) {
            return null;
        }

        if (is_string($structuredData)) {
            return $structuredData;
        }

        return json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
