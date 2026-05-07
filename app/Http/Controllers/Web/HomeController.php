<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductSubcategory;
use App\Models\Post;
use App\Models\Setting;
use App\Models\HeroSlide;

class HomeController extends Controller
{
    public function index()
    {
        $page = Page::where('slug', 'home')
            ->with(['sections.images'])
            ->firstOrFail();

        /*
        |----------------------------------------------------------------------
        | PRODUCTOS DESTACADOS EN HOME
        | Combina subcategorías Y productos individuales con show_in_home=true
        | Normalizado a un array común: type, name, image_url, url, category
        |----------------------------------------------------------------------
        */

        // Subcategorías marcadas para home
        $subcats = ProductSubcategory::with('category')
            ->where('is_active', true)
            ->where('show_in_home', true)
            ->orderBy('order')
            ->get()
            ->map(fn($s) => [
                'type'           => 'subcategory',
                'name'           => $s->name,
                'image'          => Product::mediaUrl($s->image),
                'url'            => $s->category
                                    ? route('products.subcategory', [$s->category->slug, $s->slug])
                                    : route('products.index'),
                'category'       => $s->category?->name,
                'category_color' => $s->category?->color ?? '#28597D',
                'order'          => $s->order ?? 'ZZ',
            ]);

        // Productos individuales marcados para home
        $products = Product::with(['category', 'images'])
            ->where('is_active', true)
            ->where('show_in_home', true)
            ->orderBy('order')
            ->get()
            ->map(function ($p) {
                $image = $p->image_url;

                if (!$image && $p->images->count()) {
                    $image = $p->images->sortBy('order')->first()?->image_url;
                }

                return [
                    'type'           => 'product',
                    'name'           => $p->name,
                    'image'          => $image,
                    'url'            => route('products.show', $p->slug),
                    'category'       => $p->category?->name,
                    'category_color' => $p->category?->color ?? '#28597D',
                    'order'          => $p->order ?? 'ZZ',
                ];
            });

        // Unificar, ordenar por campo "order" alfabético y limitar a 8
        $featuredProducts = $subcats->concat($products)
            ->sortBy('order')
            ->take(8)
            ->values();

        // Solo posts marcados para mostrar en home, ordenados alfanuméricamente
        $posts = Post::where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('order')
            ->orderByDesc('published_at')
            ->take(9)
            ->get();

        $settings = Setting::pluck('value', 'key');

        $slides = HeroSlide::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('web.home.home', [
            'page'             => $page,
            'featuredProducts' => $featuredProducts,
            'posts'            => $posts,
            'settings'         => $settings,
            'slides'           => $slides,
        ]);
    }
}
