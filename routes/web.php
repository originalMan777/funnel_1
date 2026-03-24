<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\PopupController as AdminPopupController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\LeadBoxController;
use App\Http\Controllers\Admin\LeadSlotController;
use App\Http\Controllers\Admin\ResourceLeadBoxController;
use App\Http\Controllers\Admin\ServiceLeadBoxController;
use App\Http\Controllers\Admin\OfferLeadBoxController;
use App\Http\Controllers\Public\LeadController;
use App\Http\Controllers\Public\PostController as PublicPostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ContentFormula\ContentFormulaController;

/*
|--------------------------------------------------------------------------
| Existing routes...
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Admin Content Formula Tool
|--------------------------------------------------------------------------
|
| Admin-only tool for building weighted article idea pools and generating
| structured content directions.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/content-formula', [ContentFormulaController::class, 'index'])
            ->name('content-formula.index');

        Route::post('/content-formula/generate', [ContentFormulaController::class, 'generate'])
            ->name('content-formula.generate');

        Route::get('/content-formula/config', [ContentFormulaController::class, 'config'])
            ->name('content-formula.config');
    });

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/services', function () {
    return Inertia::render('Services');
})->name('services');

Route::get('/consultation', function () {
    return Inertia::render('Consultation');
})->name('consultation');

Route::get('/resources', function () {
    return Inertia::render('Resources');
})->name('resources');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::get('/buyers', function () {
    return Inertia::render('Buyers');
})->name('buyers');

Route::get('/sellers', function () {
    return Inertia::render('Sellers');
})->name('sellers');

Route::get('/blog', [PublicPostController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [PublicPostController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [PublicPostController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [PublicPostController::class, 'show'])->name('blog.show');

Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');

Route::middleware(['auth'])->group(function () {
    Route::redirect('/profile', '/settings/profile')->name('profile');

    Route::get('/dashboard', function () {
        return redirect('/profile');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('index');

        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [AdminPostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}', [AdminPostController::class, 'show'])->name('posts.show');
        Route::get('/posts/{post}/edit', [AdminPostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [AdminPostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/publish', [AdminPostController::class, 'publish'])->name('posts.publish');
        Route::post('/posts/{post}/unpublish', [AdminPostController::class, 'unpublish'])->name('posts.unpublish');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

        Route::get('/media', [MediaLibraryController::class, 'index'])->name('media.index');
        Route::get('/media/browser', [MediaLibraryController::class, 'browser'])->name('media.browser');
        Route::get('/media/feed', [MediaLibraryController::class, 'feed'])->name('media.feed');
        Route::post('/media', [MediaLibraryController::class, 'store'])->name('media.store');
        Route::delete('/media', [MediaLibraryController::class, 'destroy'])->name('media.destroy');

        Route::get('/lead-boxes', [LeadBoxController::class, 'index'])->name('lead-boxes.index');
        Route::get('/lead-boxes/create', [LeadBoxController::class, 'create'])->name('lead-boxes.create');
        Route::get('/lead-boxes/{leadBox}/edit', [LeadBoxController::class, 'edit'])->name('lead-boxes.edit');

        Route::post('/lead-boxes/resource', [ResourceLeadBoxController::class, 'store'])->name('lead-boxes.resource.store');
        Route::put('/lead-boxes/resource/{leadBox}', [ResourceLeadBoxController::class, 'update'])->name('lead-boxes.resource.update');

        Route::post('/lead-boxes/service', [ServiceLeadBoxController::class, 'store'])->name('lead-boxes.service.store');
        Route::put('/lead-boxes/service/{leadBox}', [ServiceLeadBoxController::class, 'update'])->name('lead-boxes.service.update');

        Route::post('/lead-boxes/offer', [OfferLeadBoxController::class, 'store'])->name('lead-boxes.offer.store');
        Route::put('/lead-boxes/offer/{leadBox}', [OfferLeadBoxController::class, 'update'])->name('lead-boxes.offer.update');
Route::get('/lead-slots', [LeadSlotController::class, 'index'])->name('lead-slots.index');
        Route::put('/lead-slots/{leadSlot}', [LeadSlotController::class, 'update'])->name('lead-slots.update');

        Route::get('/popups', [AdminPopupController::class, 'index'])->name('popups.index');
        Route::get('/popups/create', [AdminPopupController::class, 'create'])->name('popups.create');
        Route::post('/popups', [AdminPopupController::class, 'store'])->name('popups.store');
        Route::get('/popups/{popup}/edit', [AdminPopupController::class, 'edit'])->name('popups.edit');
        Route::put('/popups/{popup}', [AdminPopupController::class, 'update'])->name('popups.update');
        Route::delete('/popups/{popup}', [AdminPopupController::class, 'destroy'])->name('popups.destroy');

        Route::get('/coming-soon', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('coming-soon');
    });

require __DIR__.'/settings.php';
