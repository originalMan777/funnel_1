<?php

use App\Http\Controllers\Admin\AiPostImporterController;
use App\Http\Controllers\Admin\AcquisitionContactController;
use App\Http\Controllers\Admin\BlogIndexSectionController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CampaignEnrollmentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommunicationDeliveryController;
use App\Http\Controllers\Admin\CommunicationEventController;
use App\Http\Controllers\Admin\CommunicationComposerController;
use App\Http\Controllers\Admin\CommunicationOverviewController;
use App\Http\Controllers\Admin\CommunicationSettingsController;
use App\Http\Controllers\Admin\CommunicationTemplateController;
use App\Http\Controllers\Admin\CommunicationTemplatePreviewController;
use App\Http\Controllers\Admin\CommunicationTemplateTestSendController;
use App\Http\Controllers\Admin\CommunicationTemplateVersionController;
use App\Http\Controllers\Admin\LeadBoxController;
use App\Http\Controllers\Admin\LeadSlotController;
use App\Http\Controllers\Admin\MarketingSyncController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\OfferLeadBoxController;
use App\Http\Controllers\Admin\PopupController as AdminPopupController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ResourceLeadBoxController;
use App\Http\Controllers\Admin\ServiceLeadBoxController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\ContentFormula\ContentFormulaController;
use App\Http\Controllers\Public\PopupLeadController;
use App\Http\Controllers\Public\LeadController;
use App\Http\Controllers\Public\PostController as PublicPostController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

use Inertia\Inertia;

Route::get('/buyers-strategy', function () {
    return Inertia::render('BuyersStrategy');
})->name('buyers.strategy');

Route::get('/sellers-strategy', function () {
    return Inertia::render('SellersStrategy');
})->name('sellers.strategy');

Route::get('/consultation', function () {
    return Inertia::render('Consultation');
})->name('consultation');

Route::get('/consultation/request', function () {
    return Inertia::render('ConsultationRequest');
})->name('consultation.request');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::get('/blog-test-page', function () {
    return Inertia::render('Blog/Index');
})->name('blog.test.page');

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/consultation/request', [LeadController::class, 'storeConsultation'])
        ->name('consultation.request.store');

    Route::post('/contact', [LeadController::class, 'storeContact'])
        ->name('contact.store');

    Route::post('/popup-leads', [PopupLeadController::class, 'store'])
        ->name('popup-leads.store');

    Route::post('/leads', [LeadController::class, 'store'])
        ->name('leads.store');
});

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

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/content-formula', [ContentFormulaController::class, 'index'])
            ->name('content-formula.index');

        Route::post('/content-formula/generate', [ContentFormulaController::class, 'generate'])
            ->name('content-formula.generate');

        Route::get('/content-formula/config', [ContentFormulaController::class, 'config'])
            ->name('content-formula.config');

        Route::get('/posts/archived', [AdminPostController::class, 'archived'])
        ->name('posts.archived');

        Route::patch('/posts/{post}/archive', [AdminPostController::class, 'archive'])
        ->name('posts.archive');
    });

Route::get('/', HomeController::class)->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/services', function () {
    return Inertia::render('Services');
})->name('services');

Route::get('/resources', function () {
    return Inertia::render('Resources');
})->name('resources');

Route::get('/blog', [PublicPostController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [PublicPostController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [PublicPostController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [PublicPostController::class, 'show'])->name('blog.show');

Route::middleware(['auth', 'admin'])
    ->get('/dashboard', function () {
        return to_route('admin.index');
    })->name('dashboard');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('index');

        Route::get('/acquisition/contacts', [AcquisitionContactController::class, 'index'])
            ->name('acquisition.contacts.index');

        Route::post('/acquisition/contacts/{contact}/touches', [AcquisitionContactController::class, 'storeTouch'])
            ->name('acquisition.contacts.touches.store');

        Route::patch('/acquisition/contacts/{contact}/state', [AcquisitionContactController::class, 'updateState'])
            ->name('acquisition.contacts.update-state');

        Route::get('/acquisition/contacts/{contact}', [AcquisitionContactController::class, 'show'])
            ->name('acquisition.contacts.show');

        Route::get('/communications', [CommunicationOverviewController::class, 'index'])
            ->name('communications.index');
        Route::get('/communications/events', [CommunicationEventController::class, 'index'])
            ->name('communications.events.index');
        Route::get('/communications/events/{communicationEvent}', [CommunicationEventController::class, 'show'])
            ->name('communications.events.show');
        Route::post('/communications/events/{communicationEvent}/requeue', [CommunicationEventController::class, 'requeue'])
            ->name('communications.events.requeue');
        Route::get('/communications/deliveries', [CommunicationDeliveryController::class, 'index'])
            ->name('communications.deliveries.index');
        Route::get('/communications/deliveries/{communicationDelivery}', [CommunicationDeliveryController::class, 'show'])
            ->name('communications.deliveries.show');
        Route::get('/communications/syncs', [MarketingSyncController::class, 'index'])
            ->name('communications.syncs.index');
        Route::get('/communications/syncs/{marketingContactSync}', [MarketingSyncController::class, 'show'])
            ->name('communications.syncs.show');
        Route::get('/communications/settings', [CommunicationSettingsController::class, 'index'])
            ->name('communications.settings.index');
        Route::put('/communications/settings', [CommunicationSettingsController::class, 'update'])
            ->name('communications.settings.update');
        Route::get('/communications/composer', [CommunicationComposerController::class, 'index'])
            ->name('communications.composer.index');
        Route::post('/communications/composer/preview', [CommunicationComposerController::class, 'preview'])
            ->name('communications.composer.preview');
        Route::post('/communications/composer/send', [CommunicationComposerController::class, 'send'])
            ->name('communications.composer.send');
        Route::get('/communications/templates', [CommunicationTemplateController::class, 'index'])
            ->name('communications.templates.index');
        Route::get('/communications/templates/create', [CommunicationTemplateController::class, 'create'])
            ->name('communications.templates.create');
        Route::post('/communications/templates', [CommunicationTemplateController::class, 'store'])
            ->name('communications.templates.store');
        Route::get('/communications/templates/{template}', [CommunicationTemplateController::class, 'show'])
            ->name('communications.templates.show');
        Route::get('/communications/templates/{template}/edit', [CommunicationTemplateController::class, 'edit'])
            ->name('communications.templates.edit');
        Route::put('/communications/templates/{template}', [CommunicationTemplateController::class, 'update'])
            ->name('communications.templates.update');
        Route::post('/communications/templates/{template}/versions', [CommunicationTemplateVersionController::class, 'store'])
            ->name('communications.templates.versions.store');
        Route::post('/communications/templates/{template}/versions/{version}/publish', [CommunicationTemplateVersionController::class, 'publish'])
            ->name('communications.templates.versions.publish');
        Route::post('/communications/templates/{template}/preview', [CommunicationTemplatePreviewController::class, 'store'])
            ->name('communications.templates.preview');
        Route::post('/communications/templates/{template}/test-send', [CommunicationTemplateTestSendController::class, 'store'])
            ->name('communications.templates.test-send');

        Route::get('/campaigns', [CampaignController::class, 'index'])
            ->name('campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])
            ->name('campaigns.create');
        Route::post('/campaigns', [CampaignController::class, 'store'])
            ->name('campaigns.store');
        Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])
            ->name('campaigns.edit');
        Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])
            ->name('campaigns.update');

        Route::get('/campaign-enrollments', [CampaignEnrollmentController::class, 'index'])
            ->name('campaign-enrollments.index');
        Route::get('/campaign-enrollments/{campaignEnrollment}', [CampaignEnrollmentController::class, 'show'])
            ->name('campaign-enrollments.show');
        Route::post('/campaign-enrollments/{campaignEnrollment}/pause', [CampaignEnrollmentController::class, 'pause'])
            ->name('campaign-enrollments.pause');
        Route::post('/campaign-enrollments/{campaignEnrollment}/resume', [CampaignEnrollmentController::class, 'resume'])
            ->name('campaign-enrollments.resume');
        Route::post('/campaign-enrollments/{campaignEnrollment}/exit', [CampaignEnrollmentController::class, 'exit'])
            ->name('campaign-enrollments.exit');

        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [AdminPostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}', [AdminPostController::class, 'show'])->name('posts.show');
        Route::get('/posts/{post}/edit', [AdminPostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [AdminPostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/publish', [AdminPostController::class, 'publish'])->name('posts.publish');
        Route::post('/posts/{post}/unpublish', [AdminPostController::class, 'unpublish'])->name('posts.unpublish');

        Route::get('/blog-index-sections', [BlogIndexSectionController::class, 'index'])->name('blog-index-sections.index');
        Route::put('/blog-index-sections', [BlogIndexSectionController::class, 'update'])->name('blog-index-sections.update');

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

        Route::get('/lead-boxes/resource/create', [ResourceLeadBoxController::class, 'create'])->name('lead-boxes.resource.create');
        Route::get('/lead-boxes/resource/{leadBox}/edit', [ResourceLeadBoxController::class, 'edit'])->name('lead-boxes.resource.edit');
        Route::post('/lead-boxes/resource', [ResourceLeadBoxController::class, 'store'])->name('lead-boxes.resource.store');
        Route::put('/lead-boxes/resource/{leadBox}', [ResourceLeadBoxController::class, 'update'])->name('lead-boxes.resource.update');

        Route::get('/lead-boxes/service/create', [ServiceLeadBoxController::class, 'create'])->name('lead-boxes.service.create');
        Route::get('/lead-boxes/service/{leadBox}/edit', [ServiceLeadBoxController::class, 'edit'])->name('lead-boxes.service.edit');
        Route::post('/lead-boxes/service', [ServiceLeadBoxController::class, 'store'])->name('lead-boxes.service.store');
        Route::put('/lead-boxes/service/{leadBox}', [ServiceLeadBoxController::class, 'update'])->name('lead-boxes.service.update');

        Route::get('/lead-boxes/offer/create', [OfferLeadBoxController::class, 'create'])->name('lead-boxes.offer.create');
        Route::get('/lead-boxes/offer/{leadBox}/edit', [OfferLeadBoxController::class, 'edit'])->name('lead-boxes.offer.edit');
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

        Route::get('/post-importer', [AiPostImporterController::class, 'index'])->name('post-importer.index');
        Route::post('/post-importer', [AiPostImporterController::class, 'store'])
            ->name('post-importer.store')
            ->middleware('throttle:5,1');

        Route::get('/coming-soon', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('coming-soon');
    });

require __DIR__.'/settings.php';
