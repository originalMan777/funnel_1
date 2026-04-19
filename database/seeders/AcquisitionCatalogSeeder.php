<?php

namespace Database\Seeders;

use App\Models\Acquisition;
use App\Models\AcquisitionPath;
use App\Models\Service;
use Illuminate\Database\Seeder;

class AcquisitionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $seller = Acquisition::query()->updateOrCreate(
            ['slug' => 'seller-acquisition'],
            [
                'name' => 'Seller Acquisition',
                'description' => 'Seller-side intake and acquisition context.',
                'is_active' => true,
            ],
        );

        $buyer = Acquisition::query()->updateOrCreate(
            ['slug' => 'buyer-acquisition'],
            [
                'name' => 'Buyer Acquisition',
                'description' => 'Buyer-side intake and acquisition context.',
                'is_active' => true,
            ],
        );

        $general = Acquisition::query()->updateOrCreate(
            ['slug' => 'general-inquiry-acquisition'],
            [
                'name' => 'General Inquiry Acquisition',
                'description' => 'General inquiry and low-intent fallback context.',
                'is_active' => true,
            ],
        );

        $sellerValuation = Service::query()->updateOrCreate(
            ['acquisition_id' => $seller->id, 'slug' => 'home-valuation'],
            [
                'name' => 'Home Valuation',
                'description' => 'Seller valuation requests and market guidance.',
                'is_active' => true,
            ],
        );

        $sellerListing = Service::query()->updateOrCreate(
            ['acquisition_id' => $seller->id, 'slug' => 'listing-consultation'],
            [
                'name' => 'Listing Consultation',
                'description' => 'Seller listing consultation requests.',
                'is_active' => true,
            ],
        );

        $buyerConsultation = Service::query()->updateOrCreate(
            ['acquisition_id' => $buyer->id, 'slug' => 'buyer-consultation'],
            [
                'name' => 'Buyer Consultation',
                'description' => 'Buyer consultation requests.',
                'is_active' => true,
            ],
        );

        $buyerMatch = Service::query()->updateOrCreate(
            ['acquisition_id' => $buyer->id, 'slug' => 'property-match-request'],
            [
                'name' => 'Property Match Request',
                'description' => 'Buyer property match and intake requests.',
                'is_active' => true,
            ],
        );

        $generalContact = Service::query()->updateOrCreate(
            ['acquisition_id' => $general->id, 'slug' => 'general-contact'],
            [
                'name' => 'General Contact',
                'description' => 'General inquiry contact requests.',
                'is_active' => true,
            ],
        );

        $callbackRequest = Service::query()->updateOrCreate(
            ['acquisition_id' => $general->id, 'slug' => 'callback-request'],
            [
                'name' => 'Callback Request',
                'description' => 'General callback and consultation fallback requests.',
                'is_active' => true,
            ],
        );

        AcquisitionPath::query()->updateOrCreate(
            ['path_key' => 'seller.home-valuation.blog-inline'],
            [
                'acquisition_id' => $seller->id,
                'service_id' => $sellerValuation->id,
                'name' => 'Seller Home Valuation Blog Inline',
                'slug' => 'seller-home-valuation-blog-inline',
                'entry_type' => 'lead_slot',
                'source_context' => 'blog_inline',
                'is_active' => true,
            ],
        );

        AcquisitionPath::query()->updateOrCreate(
            ['path_key' => 'seller.listing-consult.popup-home'],
            [
                'acquisition_id' => $seller->id,
                'service_id' => $sellerListing->id,
                'name' => 'Seller Listing Consult Popup Home',
                'slug' => 'seller-listing-consult-popup-home',
                'entry_type' => 'popup',
                'source_context' => 'popup_home',
                'is_active' => true,
            ],
        );

        AcquisitionPath::query()->updateOrCreate(
            ['path_key' => 'buyer.consultation.blog-inline'],
            [
                'acquisition_id' => $buyer->id,
                'service_id' => $buyerConsultation->id,
                'name' => 'Buyer Consultation Blog Inline',
                'slug' => 'buyer-consultation-blog-inline',
                'entry_type' => 'lead_slot',
                'source_context' => 'blog_inline',
                'is_active' => true,
            ],
        );

        AcquisitionPath::query()->updateOrCreate(
            ['path_key' => 'general.contact.home-popup'],
            [
                'acquisition_id' => $general->id,
                'service_id' => $generalContact->id,
                'name' => 'General Contact Home Popup',
                'slug' => 'general-contact-home-popup',
                'entry_type' => 'popup',
                'source_context' => 'home_popup',
                'is_active' => true,
            ],
        );
    }
}
