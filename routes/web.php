<?php

use Illuminate\Support\Facades\Route;
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
| HOME
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');


/*
|--------------------------------------------------------------------------
| BUSCADOR
|--------------------------------------------------------------------------
*/

Route::get('/buscador', [SearchController::class, 'index'])->name('search');


/*
|--------------------------------------------------------------------------
| NOSOTROS
|--------------------------------------------------------------------------
*/

Route::get('/nosotros', [AboutController::class, 'index'])
    ->name('nosotros');


/*
|--------------------------------------------------------------------------
| PRODUCTOS
|--------------------------------------------------------------------------
*/

Route::get('/productos', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/productos/{categoria}', [ProductController::class, 'category'])
    ->name('products.category');

Route::get('/productos/{categoria}/{subcategoria}', [ProductController::class, 'subcategory'])
    ->name('products.subcategory');

Route::get('/producto/{slug}', [ProductController::class, 'show'])
    ->name('products.show');


/*
|--------------------------------------------------------------------------
| INDUSTRIAS
|--------------------------------------------------------------------------
*/

Route::get('/industrias', [IndustryController::class, 'index'])
    ->name('industries.index');

Route::get('/industria/{slug}', [IndustryController::class, 'show'])
    ->name('industries.show');


/*
|--------------------------------------------------------------------------
| NOVEDADES (BLOG)
|--------------------------------------------------------------------------
*/

Route::get('/novedades', [PostController::class, 'index'])
    ->name('posts.index');

Route::get('/novedades/{slug}', [PostController::class, 'show'])
    ->name('posts.show');


/*
|--------------------------------------------------------------------------
| CONTACTO
|--------------------------------------------------------------------------
*/

Route::get('/contacto', [ContactController::class, 'index'])
    ->name('contact.index');

Route::post('/contacto', [ContactController::class, 'store'])
    ->name('contact.store');


/*
|--------------------------------------------------------------------------
| COTIZACION / PRESUPUESTO
|--------------------------------------------------------------------------
*/

Route::post('/presupuesto', [QuoteController::class, 'store'])
    ->name('quote.store');


/*
|--------------------------------------------------------------------------
| NEWSLETTER
|--------------------------------------------------------------------------
*/

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->name('newsletter.store');


/*
|--------------------------------------------------------------------------
| PRESUPUESTO (PAGE)
|--------------------------------------------------------------------------
*/

Route::get('/presupuesto', [QuoteController::class, 'index'])
    ->name('presupuesto');

Route::post('/presupuesto', [QuoteController::class, 'store'])
    ->name('quote.store');



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
