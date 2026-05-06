<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminIndustryController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductSubcategoryController;
use App\Http\Controllers\Admin\AdminPostController;
use App\Http\Controllers\Admin\AdminDownloadController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\AdminContactMessageController;
use App\Http\Controllers\Admin\AdminNewsletterController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\HrSectorController;
use App\Http\Controllers\Admin\HrPositionController;
use App\Http\Controllers\Admin\HrApplicationController;
use App\Http\Controllers\Admin\AdminCmsController;
use App\Http\Controllers\Admin\ProductSpecController;
use App\Http\Controllers\Admin\ProductDownloadController;
use App\Http\Controllers\Admin\AdminSeoMetaController;
use App\Http\Controllers\Admin\AdminUserController;

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| HERO / SLIDER
|--------------------------------------------------------------------------
*/

Route::resource('hero', HeroSlideController::class)->except(['show']);

/*
|--------------------------------------------------------------------------
| CONTENIDO WEB - CMS
|--------------------------------------------------------------------------
*/

Route::get('/home-about',     [AdminCmsController::class, 'homeAbout'])->name('home-about.index');
Route::get('/about',          [AdminCmsController::class, 'about'])->name('about.index');
Route::get('/cms/calidad',    [AdminCmsController::class, 'calidad'])->name('cms.calidad');
Route::get('/contacto',       [AdminCmsController::class, 'contacto'])->name('contacto.index');
Route::post('/contacto/info', [AdminCmsController::class, 'updateContactInfo'])->name('contacto.updateInfo');

Route::post('/pages/{page}/sections',          [AdminCmsController::class, 'storeSection'])->name('pages.sections.store');
Route::put('/sections/{section}',              [AdminCmsController::class, 'updateSection'])->name('sections.update');
Route::delete('/sections/{section}',           [AdminCmsController::class, 'destroySection'])->name('sections.destroy');

/*
|--------------------------------------------------------------------------
| PRODUCTOS
|--------------------------------------------------------------------------
*/

Route::resource('products', AdminProductController::class)->except(['show']);
Route::resource('categories', ProductCategoryController::class)->except(['show']);
Route::resource('subcategories', ProductSubcategoryController::class)->except(['show']);

// Specs por producto
Route::post('/products/{product}/specs',              [ProductSpecController::class, 'store'])->name('products.specs.store');
Route::put('/products/{product}/specs/{spec}',        [ProductSpecController::class, 'update'])->name('products.specs.update');
Route::delete('/products/{product}/specs/{spec}',     [ProductSpecController::class, 'destroy'])->name('products.specs.destroy');

// Descargas por producto
Route::post('/products/{product}/downloads',                               [ProductDownloadController::class, 'store'])->name('products.downloads.store');
Route::patch('/products/{product}/downloads/{download}/toggle-principal',  [ProductDownloadController::class, 'togglePrincipal'])->name('products.downloads.toggle-principal');
Route::delete('/products/{product}/downloads/{download}',                  [ProductDownloadController::class, 'destroy'])->name('products.downloads.destroy');

/*
|--------------------------------------------------------------------------
| SECTORES (INDUSTRIAS)
|--------------------------------------------------------------------------
*/

Route::resource('sectors', AdminIndustryController::class)
    ->except(['show'])
    ->parameters(['sectors' => 'sector']);

/*
|--------------------------------------------------------------------------
| DESCARGAS
|--------------------------------------------------------------------------
*/

Route::resource('downloads', AdminDownloadController::class)->except(['show']);

/*
|--------------------------------------------------------------------------
| NOVEDADES
|--------------------------------------------------------------------------
*/

Route::resource('posts', AdminPostController::class)->except(['show']);
Route::patch('/posts/{post}/toggle-home',  [AdminPostController::class, 'toggleHome'])->name('posts.toggle-home');
Route::patch('/posts/{post}/update-order', [AdminPostController::class, 'updateOrder'])->name('posts.update-order');

/*
|--------------------------------------------------------------------------
| COTIZACIONES
|--------------------------------------------------------------------------
*/

Route::get('/comercial/quotations',         [QuotationController::class, 'index'])->name('quotations.index');
Route::get('/comercial/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
Route::delete('/comercial/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy');

/*
|--------------------------------------------------------------------------
| CLIENTES
|--------------------------------------------------------------------------
*/

Route::resource('clients', AdminClientController::class)->except(['show']);

/*
|--------------------------------------------------------------------------
| MENSAJES DE CONTACTO
|--------------------------------------------------------------------------
*/

Route::get('/contact-messages',              [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
Route::get('/contact-messages/{contactMessage}', [AdminContactMessageController::class, 'show'])->name('contact-messages.show');
Route::delete('/contact-messages/{contactMessage}', [AdminContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

/*
|--------------------------------------------------------------------------
| NEWSLETTER
|--------------------------------------------------------------------------
*/

Route::get('/newsletter',                 [AdminNewsletterController::class, 'index'])->name('newsletter.index');
Route::post('/newsletter/send',           [AdminNewsletterController::class, 'send'])->name('newsletter.send');
Route::delete('/newsletter/{newsletter}', [AdminNewsletterController::class, 'destroy'])->name('newsletter.destroy');

/*
|--------------------------------------------------------------------------
| RRHH
|--------------------------------------------------------------------------
*/

Route::prefix('hr')->name('hr.')->group(function () {

    Route::resource('sectors', HrSectorController::class)
        ->except(['show'])
        ->parameters(['sectors' => 'hrSector']);

    Route::resource('positions', HrPositionController::class)
        ->except(['show'])
        ->parameters(['positions' => 'hrPosition']);

    Route::get('/applications',              [HrApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{hrApplication}', [HrApplicationController::class, 'show'])->name('applications.show');
    Route::delete('/applications/{hrApplication}', [HrApplicationController::class, 'destroy'])->name('applications.destroy');

});

/*
|--------------------------------------------------------------------------
| SEO METADATOS
|--------------------------------------------------------------------------
*/

Route::get('/seo',    [AdminSeoMetaController::class, 'index'])->name('seo.index');
Route::post('/seo',   [AdminSeoMetaController::class, 'upsert'])->name('seo.upsert');

/*
|--------------------------------------------------------------------------
| USUARIOS ADMIN
|--------------------------------------------------------------------------
*/

Route::get('/users',          [AdminUserController::class, 'index'])->name('users.index');
Route::post('/users',         [AdminUserController::class, 'store'])->name('users.store');
Route::put('/users/{user}',   [AdminUserController::class, 'update'])->name('users.update');
Route::delete('/users/{user}',[AdminUserController::class, 'destroy'])->name('users.destroy');

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    // Inertia::location() fuerza un hard redirect del browser
    // evitando que Inertia inyecte el HTML del sitio web dentro del admin SPA
    return Inertia::location(url('/'));
})->name('logout');
