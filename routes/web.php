<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| WEB PUBLICA
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\IndustryController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\QuoteController;
use App\Http\Controllers\Web\NewsletterController;
use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\SearchController;

/*
|--------------------------------------------------------------------------
| WEB PUBLICA MULTIDIOMA
|--------------------------------------------------------------------------
*/

Route::post('/idioma/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, ['es', 'pt', 'en'], true), 404);

    $request->session()->put('locale', $locale);

    return redirect()->back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->name('language.switch');

Route::get('/{locale}/{path?}', function (Request $request, string $locale, ?string $path = null) {
    $request->session()->put('locale', $locale);
    $target = url($path ? '/' . ltrim($path, '/') : '/');

    return redirect($target)->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->whereIn('locale', ['es', 'pt', 'en'])->where('path', '.*');

Route::middleware('locale')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/buscador', [SearchController::class, 'index'])->name('search');

    Route::get('/nosotros', [AboutController::class, 'index'])
        ->name('nosotros');

    Route::get('/productos', [ProductController::class, 'index'])
        ->name('products.index');

    Route::get('/productos/{categoria}', [ProductController::class, 'category'])
        ->name('products.category');

    Route::get('/productos/{categoria}/{subcategoria}', [ProductController::class, 'subcategory'])
        ->name('products.subcategory');

    Route::get('/producto/{slug}', [ProductController::class, 'show'])
        ->name('products.show');

    Route::get('/industrias', [IndustryController::class, 'index'])
        ->name('industries.index');

    Route::get('/industria/{slug}', [IndustryController::class, 'show'])
        ->name('industries.show');

    Route::get('/novedades', [PostController::class, 'index'])
        ->name('posts.index');

    Route::get('/novedades/{slug}', [PostController::class, 'show'])
        ->name('posts.show');

    Route::get('/contacto', [ContactController::class, 'index'])
        ->name('contact.index');

    Route::post('/contacto', [ContactController::class, 'store'])
        ->name('contact.store');

    Route::get('/presupuesto', [QuoteController::class, 'index'])
        ->name('presupuesto');

    Route::post('/presupuesto', [QuoteController::class, 'store'])
        ->name('quote.store');

    Route::post('/newsletter', [NewsletterController::class, 'store'])
        ->name('newsletter.store');
});



/*
|--------------------------------------------------------------------------
| ADMIN PANEL (INERTIA)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__ . '/admin.php';
});


/*
|--------------------------------------------------------------------------
| PERFIL USUARIO (BREEZE)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});


/*
|--------------------------------------------------------------------------
| AUTH (LOGIN / REGISTER / LOGOUT)
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
