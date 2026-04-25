<?php

namespace Database\Seeders;

use App\Models\Analytics\AttributionTouch;
use App\Models\Analytics\Conversion;
use App\Models\Analytics\ConversionAttribution;
use App\Models\Analytics\Cta;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\SessionScenario;
use App\Models\Analytics\Surface;
use App\Models\Analytics\Visitor;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Popup;
use App\Services\Analytics\AnalyticsAttributionService;
use App\Services\Analytics\AnalyticsCatalogService;
use App\Services\Analytics\AnalyticsScenarioService;
use App\Services\Analytics\RollupService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsDemoSeeder extends Seeder
{
    private const RANGE_FROM = '2026-03-26';

    private const RANGE_TO = '2026-04-24';

    /**
     * @var array<string, int>
     */
    private array $eventTypeIds = [];

    /**
     * @var array<string, Page>
     */
    private array $pages = [];

    /**
     * @var array<string, Cta>
     */
    private array $ctas = [];

    /**
     * @var array<string, LeadBox>
     */
    private array $leadBoxes = [];

    /**
     * @var array<string, LeadSlot>
     */
    private array $leadSlots = [];

    /**
     * @var array<string, Popup>
     */
    private array $popups = [];

    /**
     * @var array<string, Surface>
     */
    private array $surfaces = [];

    private AnalyticsCatalogService $catalogService;

    private AnalyticsScenarioService $scenarioService;

    private AnalyticsAttributionService $attributionService;

    private RollupService $rollupService;

    public function run(): void
    {
        $this->catalogService = app(AnalyticsCatalogService::class);
        $this->scenarioService = app(AnalyticsScenarioService::class);
        $this->attributionService = app(AnalyticsAttributionService::class);
        $this->rollupService = app(RollupService::class);

        $this->call([
            AnalyticsEventTypeSeeder::class,
            AnalyticsScenarioDefinitionSeeder::class,
        ]);

        $from = CarbonImmutable::parse(self::RANGE_FROM)->startOfDay();
        $to = CarbonImmutable::parse(self::RANGE_TO)->endOfDay();

        $this->resetAnalyticsTables();
        $this->seedCatalog();
        $this->seedJourneys($from, $to);
        $this->scenarioService->assignRange($from, $to);
        $this->attributionService->syncRange($from, $to);
        $this->rollupService->backfill($from, $to);

        $this->command?->info(sprintf(
            'Analytics demo data seeded for %s to %s. Sessions: %d, Events: %d, Conversions: %d, Rollups: %d.',
            $from->toDateString(),
            $to->toDateString(),
            Session::query()->count(),
            Event::query()->count(),
            Conversion::query()->count(),
            DailyRollup::query()->count(),
        ));
    }

    private function resetAnalyticsTables(): void
    {
        DB::transaction(function (): void {
            ConversionAttribution::query()->delete();
            SessionScenario::query()->delete();
            DailyRollup::query()->delete();
            Conversion::query()->delete();
            AttributionTouch::query()->delete();
            Event::query()->delete();
            Session::query()->delete();
            Visitor::query()->delete();
            Surface::query()->delete();
            Cta::query()->delete();
            Page::query()->delete();
        });
    }

    private function seedCatalog(): void
    {
        $pageDefinitions = [
            'home' => ['label' => 'Home', 'category' => 'public'],
            'sellers' => ['label' => 'Seller Strategy', 'category' => 'public'],
            'buyer_guide' => ['label' => 'Buyer Guide', 'category' => 'public'],
            'home_value_estimator' => ['label' => 'Home Value Estimator', 'category' => 'tool'],
            'contact' => ['label' => 'Contact', 'category' => 'public'],
        ];

        foreach ($pageDefinitions as $pageKey => $attributes) {
            $page = $this->catalogService->ensurePage($pageKey);
            $page->forceFill([
                'label' => $attributes['label'],
                'category' => $attributes['category'],
                'is_active' => true,
            ])->save();
            $this->pages[$pageKey] = $page->fresh();
        }

        $ctaDefinitions = [
            'get_home_value' => [
                'label' => 'Get Home Value',
                'cta_type_id' => 2,
                'intent_key' => 'home_value_request',
            ],
            'schedule_consultation' => [
                'label' => 'Schedule Consultation',
                'cta_type_id' => 2,
                'intent_key' => 'consultation_request',
            ],
            'download_seller_guide' => [
                'label' => 'Download Seller Guide',
                'cta_type_id' => 3,
                'intent_key' => 'seller_guide',
            ],
            'search_homes' => [
                'label' => 'Search Homes',
                'cta_type_id' => 1,
                'intent_key' => 'search_homes',
            ],
        ];

        foreach ($ctaDefinitions as $ctaKey => $attributes) {
            $cta = $this->catalogService->ensureCta($ctaKey);
            $cta->forceFill([
                'label' => $attributes['label'],
                'cta_type_id' => $attributes['cta_type_id'],
                'intent_key' => $attributes['intent_key'],
                'is_active' => true,
            ])->save();
            $this->ctas[$ctaKey] = $cta->fresh();
        }

        $leadSlotDefinitions = [
            'seller_sidebar' => true,
            'buyer_consultation_inline' => true,
            'home_value_inline' => true,
        ];

        foreach ($leadSlotDefinitions as $key => $isEnabled) {
            $this->leadSlots[$key] = LeadSlot::query()->updateOrCreate(
                ['key' => $key],
                ['is_enabled' => $isEnabled],
            );
        }

        $leadBoxDefinitions = [
            'seller_lead_box' => [
                'type' => LeadBox::TYPE_OFFER,
                'status' => LeadBox::STATUS_ACTIVE,
                'title' => 'Seller Lead Box',
                'short_text' => 'Capture motivated sellers looking for a valuation and sale plan.',
                'button_text' => 'Get Seller Plan',
                'icon_key' => 'home',
            ],
            'buyer_consultation_box' => [
                'type' => LeadBox::TYPE_SERVICE,
                'status' => LeadBox::STATUS_ACTIVE,
                'title' => 'Buyer Consultation Box',
                'short_text' => 'Help active buyers book a guided consultation.',
                'button_text' => 'Book Buyer Call',
                'icon_key' => 'search',
            ],
            'home_value_report_box' => [
                'type' => LeadBox::TYPE_RESOURCE,
                'status' => LeadBox::STATUS_ACTIVE,
                'title' => 'Home Value Report Box',
                'short_text' => 'Offer a fast home value estimate with a lead capture path.',
                'button_text' => 'Request Report',
                'icon_key' => 'bar-chart',
            ],
        ];

        foreach ($leadBoxDefinitions as $internalName => $attributes) {
            $this->leadBoxes[$internalName] = LeadBox::query()->updateOrCreate(
                ['internal_name' => $internalName],
                [
                    'type' => $attributes['type'],
                    'status' => $attributes['status'],
                    'title' => $attributes['title'],
                    'short_text' => $attributes['short_text'],
                    'button_text' => $attributes['button_text'],
                    'icon_key' => $attributes['icon_key'],
                    'content' => ['headline' => $attributes['title']],
                    'settings' => ['source' => 'analytics_demo'],
                ],
            );
        }

        $popupDefinitions = [
            'exit_intent_seller_popup' => [
                'name' => 'Exit Intent Seller Popup',
                'type' => 'seller',
                'headline' => 'Before you go, get a seller plan',
                'body' => 'Capture sellers when they hesitate with a concrete next step.',
                'cta_text' => 'Get Seller Plan',
                'trigger_type' => 'exit',
                'trigger_delay' => 12,
            ],
            'first_visit_guide_popup' => [
                'name' => 'First Visit Guide Popup',
                'type' => 'resource',
                'headline' => 'Want the buyer guide?',
                'body' => 'Offer a first-visit content capture prompt.',
                'cta_text' => 'Send Buyer Guide',
                'trigger_type' => 'time',
                'trigger_delay' => 8,
            ],
            'home_value_offer_popup' => [
                'name' => 'Home Value Offer Popup',
                'type' => 'seller',
                'headline' => 'See what your home could sell for',
                'body' => 'Use the valuation offer as a conversion assist on estimator traffic.',
                'cta_text' => 'Start Estimate',
                'trigger_type' => 'scroll',
                'trigger_scroll' => 55,
            ],
        ];

        foreach ($popupDefinitions as $slug => $attributes) {
            $this->popups[$slug] = Popup::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $attributes['name'],
                    'type' => $attributes['type'],
                    'is_active' => true,
                    'eyebrow' => 'Analytics Demo',
                    'headline' => $attributes['headline'],
                    'body' => $attributes['body'],
                    'cta_text' => $attributes['cta_text'],
                    'success_message' => 'Thanks, your request is on the way.',
                    'layout' => 'centered',
                    'trigger_type' => $attributes['trigger_type'],
                    'trigger_delay' => $attributes['trigger_delay'] ?? null,
                    'trigger_scroll' => $attributes['trigger_scroll'] ?? null,
                    'target_pages' => ['home', 'sellers', 'home_value_estimator'],
                    'device' => 'all',
                    'frequency' => 'once_session',
                    'form_fields' => ['name', 'email', 'phone'],
                    'lead_type' => 'seller',
                ],
            );
        }

        foreach (array_keys($this->leadBoxes) as $leadBoxKey) {
            $surface = $this->catalogService->ensureSurface("lead_box.{$leadBoxKey}");
            $surface->forceFill(['label' => Str::headline(str_replace('_', ' ', $leadBoxKey))])->save();
            $this->surfaces["lead_box.{$leadBoxKey}"] = $surface->fresh();
        }

        foreach (array_keys($this->popups) as $popupKey) {
            $surface = $this->catalogService->ensureSurface("popup.{$popupKey}");
            $surface->forceFill(['label' => Str::headline(str_replace('_', ' ', $popupKey))])->save();
            $this->surfaces["popup.{$popupKey}"] = $surface->fresh();
        }

        $this->eventTypeIds = EventType::query()
            ->pluck('id', 'event_key')
            ->all();
    }

    private function seedJourneys(CarbonImmutable $from, CarbonImmutable $to): void
    {
        $patterns = [
            'lead_box_assisted_conversion',
            'popup_assisted_conversion',
            'repeat_interaction_before_conversion',
            'research_then_conversion',
            'direct_cta_conversion',
            'popup_resistant_session',
            'high_engagement_no_conversion',
            'low_engagement_no_conversion',
            'direct_conversion_no_assist',
        ];

        $sessionNumber = 1;
        $journeyIndex = 0;

        foreach (CarbonPeriod::create($from, '1 day', $to) as $day) {
            $baseDay = CarbonImmutable::instance($day)->startOfDay();

            for ($slot = 0; $slot < 3; $slot++) {
                $patternKey = $patterns[$journeyIndex % count($patterns)];
                $startedAt = $baseDay
                    ->addHours(9 + ($slot * 3))
                    ->addMinutes((($sessionNumber * 7) + ($slot * 11)) % 43);

                $this->seedJourney($patternKey, $startedAt, $sessionNumber);
                $sessionNumber++;
                $journeyIndex++;
            }
        }
    }

    private function seedJourney(string $patternKey, CarbonImmutable $startedAt, int $sequence): void
    {
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) Str::uuid(),
            'first_seen_at' => $startedAt,
            'last_seen_at' => $startedAt,
            'first_user_agent_hash' => hash('sha256', "analytics-demo-agent-{$sequence}"),
            'latest_user_agent_hash' => hash('sha256', "analytics-demo-agent-{$sequence}"),
        ]);

        $pattern = $this->journeyDefinition($patternKey);
        $entryPage = $this->pages[$pattern['entry_page']];
        $source = $pattern['source'];

        $session = Session::query()->create([
            'session_key' => (string) Str::uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => $startedAt,
            'ended_at' => $startedAt->addMinutes(18),
            'entry_page_id' => $entryPage->id,
            'entry_url' => "https://demo.local/{$entryPage->page_key}",
            'entry_path' => "/{$entryPage->page_key}",
            'referrer_url' => $source['referrer_url'] ?? null,
            'referrer_host' => $source['referrer_host'] ?? null,
            'utm_source' => $source['utm_source'] ?? null,
            'utm_medium' => $source['utm_medium'] ?? null,
            'utm_campaign' => $source['utm_campaign'] ?? null,
            'utm_term' => $source['utm_term'] ?? null,
            'utm_content' => $source['utm_content'] ?? null,
            'device_type_id' => null,
        ]);

        $this->recordAttributionTouches($visitor, $session, $entryPage, $startedAt, $pattern);
        $this->recordJourneyEvents($patternKey, $visitor, $session, $startedAt, $sequence);

        $visitor->update([
            'last_seen_at' => $session->ended_at,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function journeyDefinition(string $patternKey): array
    {
        return match ($patternKey) {
            'lead_box_assisted_conversion' => [
                'entry_page' => 'home_value_estimator',
                'source' => [
                    'utm_source' => 'Organic Search',
                    'utm_medium' => 'search',
                    'utm_campaign' => 'spring-valuations',
                    'referrer_host' => 'google.com',
                    'referrer_url' => 'https://google.com/search?q=home+value',
                ],
                'secondary_touch' => null,
                'conversion_type_id' => 4,
            ],
            'popup_assisted_conversion' => [
                'entry_page' => 'sellers',
                'source' => [
                    'utm_source' => 'Facebook Ads',
                    'utm_medium' => 'paid_social',
                    'utm_campaign' => 'seller-retargeting',
                    'referrer_host' => 'facebook.com',
                    'referrer_url' => 'https://facebook.com/',
                ],
                'secondary_touch' => [
                    'utm_source' => 'Direct',
                    'utm_medium' => null,
                    'utm_campaign' => null,
                    'referrer_host' => null,
                    'referrer_url' => null,
                    'offset_minutes' => 4,
                ],
                'conversion_type_id' => 2,
            ],
            'repeat_interaction_before_conversion' => [
                'entry_page' => 'home',
                'source' => [
                    'utm_source' => 'Direct',
                    'utm_medium' => null,
                    'utm_campaign' => null,
                    'referrer_host' => null,
                    'referrer_url' => null,
                ],
                'secondary_touch' => null,
                'conversion_type_id' => 3,
            ],
            'research_then_conversion' => [
                'entry_page' => 'buyer_guide',
                'source' => [
                    'utm_source' => 'Referral',
                    'utm_medium' => 'partner',
                    'utm_campaign' => 'relocation-network',
                    'referrer_host' => 'besthomes.example',
                    'referrer_url' => 'https://besthomes.example/guide',
                ],
                'secondary_touch' => [
                    'utm_source' => 'Direct',
                    'utm_medium' => null,
                    'utm_campaign' => null,
                    'referrer_host' => null,
                    'referrer_url' => null,
                    'offset_minutes' => 6,
                ],
                'conversion_type_id' => 3,
            ],
            'direct_cta_conversion' => [
                'entry_page' => 'contact',
                'source' => [
                    'utm_source' => 'Direct',
                    'utm_medium' => null,
                    'utm_campaign' => null,
                    'referrer_host' => null,
                    'referrer_url' => null,
                ],
                'secondary_touch' => null,
                'conversion_type_id' => 1,
            ],
            'popup_resistant_session' => [
                'entry_page' => 'sellers',
                'source' => [
                    'utm_source' => 'Facebook Ads',
                    'utm_medium' => 'paid_social',
                    'utm_campaign' => 'popup-offer',
                    'referrer_host' => 'facebook.com',
                    'referrer_url' => 'https://facebook.com/',
                ],
                'secondary_touch' => null,
                'conversion_type_id' => null,
            ],
            'high_engagement_no_conversion' => [
                'entry_page' => 'buyer_guide',
                'source' => [
                    'utm_source' => 'Referral',
                    'utm_medium' => 'partner',
                    'utm_campaign' => 'buyer-resources',
                    'referrer_host' => 'agent-partner.example',
                    'referrer_url' => 'https://agent-partner.example/resources',
                ],
                'secondary_touch' => null,
                'conversion_type_id' => null,
            ],
            'low_engagement_no_conversion' => [
                'entry_page' => 'home',
                'source' => [
                    'utm_source' => 'Direct',
                    'utm_medium' => null,
                    'utm_campaign' => null,
                    'referrer_host' => null,
                    'referrer_url' => null,
                ],
                'secondary_touch' => null,
                'conversion_type_id' => null,
            ],
            'direct_conversion_no_assist' => [
                'entry_page' => 'contact',
                'source' => [
                    'utm_source' => 'Organic Search',
                    'utm_medium' => 'search',
                    'utm_campaign' => 'contact-intent',
                    'referrer_host' => 'google.com',
                    'referrer_url' => 'https://google.com/search?q=contact+agent',
                ],
                'secondary_touch' => [
                    'utm_source' => 'Direct',
                    'utm_medium' => null,
                    'utm_campaign' => null,
                    'referrer_host' => null,
                    'referrer_url' => null,
                    'offset_minutes' => 3,
                ],
                'conversion_type_id' => 3,
            ],
            default => throw new \InvalidArgumentException("Unsupported analytics demo journey [{$patternKey}]."),
        };
    }

    private function recordAttributionTouches(
        Visitor $visitor,
        Session $session,
        Page $entryPage,
        CarbonImmutable $startedAt,
        array $pattern,
    ): void {
        $source = $pattern['source'];

        AttributionTouch::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'landing_page_id' => $entryPage->id,
            'landing_url' => $session->entry_url,
            'referrer_url' => $source['referrer_url'] ?? null,
            'referrer_host' => $source['referrer_host'] ?? null,
            'utm_source' => $source['utm_source'] ?? null,
            'utm_medium' => $source['utm_medium'] ?? null,
            'utm_campaign' => $source['utm_campaign'] ?? null,
            'utm_term' => $source['utm_term'] ?? null,
            'utm_content' => $source['utm_content'] ?? null,
            'attribution_method' => 'landing_touch',
            'attribution_confidence' => 1,
            'occurred_at' => $startedAt->addSeconds(2),
        ]);

        if (($pattern['secondary_touch'] ?? null) === null) {
            return;
        }

        $secondary = $pattern['secondary_touch'];

        AttributionTouch::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'landing_page_id' => $entryPage->id,
            'landing_url' => $session->entry_url,
            'referrer_url' => $secondary['referrer_url'] ?? null,
            'referrer_host' => $secondary['referrer_host'] ?? null,
            'utm_source' => $secondary['utm_source'] ?? null,
            'utm_medium' => $secondary['utm_medium'] ?? null,
            'utm_campaign' => $secondary['utm_campaign'] ?? null,
            'utm_term' => $secondary['utm_term'] ?? null,
            'utm_content' => $secondary['utm_content'] ?? null,
            'attribution_method' => 'return_touch',
            'attribution_confidence' => 0.82,
            'occurred_at' => $startedAt->addMinutes((int) ($secondary['offset_minutes'] ?? 4)),
        ]);
    }

    private function recordJourneyEvents(
        string $patternKey,
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
        int $sequence,
    ): void {
        match ($patternKey) {
            'lead_box_assisted_conversion' => $this->seedLeadBoxAssistedJourney($visitor, $session, $startedAt, $sequence),
            'popup_assisted_conversion' => $this->seedPopupAssistedJourney($visitor, $session, $startedAt, $sequence),
            'repeat_interaction_before_conversion' => $this->seedRepeatInteractionJourney($visitor, $session, $startedAt, $sequence),
            'research_then_conversion' => $this->seedResearchJourney($visitor, $session, $startedAt, $sequence),
            'direct_cta_conversion' => $this->seedDirectCtaJourney($visitor, $session, $startedAt, $sequence),
            'popup_resistant_session' => $this->seedPopupResistantJourney($visitor, $session, $startedAt),
            'high_engagement_no_conversion' => $this->seedHighEngagementJourney($visitor, $session, $startedAt),
            'low_engagement_no_conversion' => $this->seedLowEngagementJourney($visitor, $session, $startedAt),
            'direct_conversion_no_assist' => $this->seedDirectConversionJourney($visitor, $session, $startedAt, $sequence),
            default => throw new \InvalidArgumentException("Unsupported analytics demo event journey [{$patternKey}]."),
        };
    }

    private function seedLeadBoxAssistedJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
        int $sequence,
    ): void {
        $page = $this->pages['home_value_estimator'];
        $supportingPage = $this->pages['sellers'];
        $cta = $this->ctas['get_home_value'];
        $leadBox = $this->leadBoxes['home_value_report_box'];
        $leadSlot = $this->leadSlots['home_value_inline'];
        $surface = $this->surfaces['lead_box.home_value_report_box'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(5), [
            'page_id' => $page->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addSeconds(16), [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.click', $startedAt->addSeconds(41), [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
        ]);
        $this->createEvent($visitor, $session, 'lead_box.impression', $startedAt->addSeconds(58), [
            'page_id' => $page->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'lead_box.click', $startedAt->addSeconds(74), [
            'page_id' => $page->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'lead_form.opened', $startedAt->addSeconds(90), [
            'page_id' => $page->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'page.view', $startedAt->addMinutes(2), [
            'page_id' => $supportingPage->id,
        ]);
        $submitTime = $startedAt->addSeconds(145);
        $this->createEvent($visitor, $session, 'lead_form.submitted', $submitTime, [
            'page_id' => $page->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
            'properties' => ['seed_sequence' => $sequence],
        ]);

        $conversion = $this->createConversion($visitor, $session, $submitTime->addSeconds(6), [
            'conversion_type_id' => 4,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'properties' => ['source' => 'analytics_demo', 'journey' => 'home_value_report'],
        ]);

        $this->recordConversionEvent($visitor, $session, $conversion->occurred_at, [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
        ]);
    }

    private function seedPopupAssistedJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
        int $sequence,
    ): void {
        $page = $this->pages['sellers'];
        $cta = $this->ctas['download_seller_guide'];
        $popup = $this->popups['exit_intent_seller_popup'];
        $surface = $this->surfaces['popup.exit_intent_seller_popup'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(4), [
            'page_id' => $page->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addSeconds(18), [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.click', $startedAt->addSeconds(32), [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
        ]);
        $this->createEvent($visitor, $session, 'popup.eligible', $startedAt->addMinutes(2), [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'popup.impression', $startedAt->addMinutes(2)->addSeconds(12), [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
        ]);
        $openTime = $startedAt->addMinutes(2)->addSeconds(30);
        $this->createEvent($visitor, $session, 'popup.opened', $openTime, [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
        ]);
        $submitTime = $openTime->addSeconds(64);
        $this->createEvent($visitor, $session, 'popup.submitted', $submitTime, [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
            'properties' => ['seed_sequence' => $sequence],
        ]);

        $conversion = $this->createConversion($visitor, $session, $submitTime->addSeconds(8), [
            'conversion_type_id' => 2,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'popup_id' => $popup->id,
            'properties' => ['source' => 'analytics_demo', 'journey' => 'popup_capture'],
        ]);

        $this->recordConversionEvent($visitor, $session, $conversion->occurred_at, [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'popup_id' => $popup->id,
        ]);
    }

    private function seedRepeatInteractionJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
        int $sequence,
    ): void {
        $home = $this->pages['home'];
        $contact = $this->pages['contact'];
        $searchHomes = $this->ctas['search_homes'];
        $schedule = $this->ctas['schedule_consultation'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(6), [
            'page_id' => $home->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addSeconds(14), [
            'page_id' => $home->id,
            'cta_id' => $searchHomes->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.click', $startedAt->addSeconds(27), [
            'page_id' => $home->id,
            'cta_id' => $searchHomes->id,
        ]);
        $this->createEvent($visitor, $session, 'page.view', $startedAt->addMinutes(2), [
            'page_id' => $contact->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addMinutes(2)->addSeconds(20), [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);
        $clickTime = $startedAt->addMinutes(2)->addSeconds(46);
        $this->createEvent($visitor, $session, 'cta.click', $clickTime, [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);

        $conversion = $this->createConversion($visitor, $session, $clickTime->addSeconds(42), [
            'conversion_type_id' => 3,
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
            'properties' => ['source' => 'analytics_demo', 'journey' => 'repeat_cta'],
        ]);

        $this->recordConversionEvent($visitor, $session, $conversion->occurred_at, [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
            'properties' => ['seed_sequence' => $sequence],
        ]);
    }

    private function seedResearchJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
        int $sequence,
    ): void {
        $buyerGuide = $this->pages['buyer_guide'];
        $home = $this->pages['home'];
        $contact = $this->pages['contact'];
        $searchHomes = $this->ctas['search_homes'];
        $schedule = $this->ctas['schedule_consultation'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(8), [
            'page_id' => $buyerGuide->id,
        ]);
        $this->createEvent($visitor, $session, 'page.view', $startedAt->addMinutes(1), [
            'page_id' => $home->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addMinutes(1)->addSeconds(18), [
            'page_id' => $home->id,
            'cta_id' => $searchHomes->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.click', $startedAt->addMinutes(1)->addSeconds(34), [
            'page_id' => $home->id,
            'cta_id' => $searchHomes->id,
        ]);
        $this->createEvent($visitor, $session, 'page.view', $startedAt->addMinutes(3), [
            'page_id' => $contact->id,
        ]);
        $clickTime = $startedAt->addMinutes(3)->addSeconds(36);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addMinutes(3)->addSeconds(20), [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.click', $clickTime, [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);

        $conversion = $this->createConversion($visitor, $session, $clickTime->addSeconds(58), [
            'conversion_type_id' => 3,
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
            'properties' => ['source' => 'analytics_demo', 'journey' => 'research_then_contact', 'seed_sequence' => $sequence],
        ]);

        $this->recordConversionEvent($visitor, $session, $conversion->occurred_at, [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);
    }

    private function seedDirectCtaJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
        int $sequence,
    ): void {
        $contact = $this->pages['contact'];
        $schedule = $this->ctas['schedule_consultation'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(5), [
            'page_id' => $contact->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addSeconds(12), [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);
        $clickTime = $startedAt->addSeconds(26);
        $this->createEvent($visitor, $session, 'cta.click', $clickTime, [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);

        $conversion = $this->createConversion($visitor, $session, $clickTime->addSeconds(35), [
            'conversion_type_id' => 1,
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
            'properties' => ['source' => 'analytics_demo', 'journey' => 'direct_cta_conversion', 'seed_sequence' => $sequence],
        ]);

        $this->recordConversionEvent($visitor, $session, $conversion->occurred_at, [
            'page_id' => $contact->id,
            'cta_id' => $schedule->id,
        ]);
    }

    private function seedPopupResistantJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
    ): void {
        $page = $this->pages['sellers'];
        $popup = $this->popups['first_visit_guide_popup'];
        $surface = $this->surfaces['popup.first_visit_guide_popup'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(6), [
            'page_id' => $page->id,
        ]);
        $this->createEvent($visitor, $session, 'popup.eligible', $startedAt->addMinutes(1), [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'popup.impression', $startedAt->addMinutes(1)->addSeconds(12), [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
        ]);
        $openTime = $startedAt->addMinutes(1)->addSeconds(28);
        $this->createEvent($visitor, $session, 'popup.opened', $openTime, [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'popup.dismissed', $openTime->addSeconds(38), [
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'surface_id' => $surface->id,
        ]);
    }

    private function seedHighEngagementJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
    ): void {
        $page = $this->pages['buyer_guide'];
        $supportingPage = $this->pages['sellers'];
        $cta = $this->ctas['download_seller_guide'];
        $leadBox = $this->leadBoxes['buyer_consultation_box'];
        $leadSlot = $this->leadSlots['buyer_consultation_inline'];
        $surface = $this->surfaces['lead_box.buyer_consultation_box'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(7), [
            'page_id' => $page->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.impression', $startedAt->addSeconds(15), [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
        ]);
        $this->createEvent($visitor, $session, 'cta.click', $startedAt->addSeconds(29), [
            'page_id' => $page->id,
            'cta_id' => $cta->id,
        ]);
        $this->createEvent($visitor, $session, 'page.view', $startedAt->addMinutes(1), [
            'page_id' => $supportingPage->id,
        ]);
        $this->createEvent($visitor, $session, 'lead_box.impression', $startedAt->addMinutes(1)->addSeconds(16), [
            'page_id' => $supportingPage->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'lead_box.click', $startedAt->addMinutes(1)->addSeconds(36), [
            'page_id' => $supportingPage->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'lead_form.opened', $startedAt->addMinutes(1)->addSeconds(55), [
            'page_id' => $supportingPage->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
        ]);
        $this->createEvent($visitor, $session, 'lead_form.failed', $startedAt->addMinutes(2)->addSeconds(21), [
            'page_id' => $supportingPage->id,
            'lead_box_id' => $leadBox->id,
            'lead_slot_id' => $leadSlot->id,
            'surface_id' => $surface->id,
        ]);
    }

    private function seedLowEngagementJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
    ): void {
        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(6), [
            'page_id' => $this->pages['home']->id,
        ]);
    }

    private function seedDirectConversionJourney(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $startedAt,
        int $sequence,
    ): void {
        $contact = $this->pages['contact'];

        $this->createEvent($visitor, $session, 'page.view', $startedAt->addSeconds(6), [
            'page_id' => $contact->id,
        ]);

        $conversion = $this->createConversion($visitor, $session, $startedAt->addMinutes(1)->addSeconds(10), [
            'conversion_type_id' => 3,
            'page_id' => $contact->id,
            'properties' => ['source' => 'analytics_demo', 'journey' => 'direct_conversion_no_assist', 'seed_sequence' => $sequence],
        ]);

        $this->recordConversionEvent($visitor, $session, $conversion->occurred_at, [
            'page_id' => $contact->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createEvent(
        Visitor $visitor,
        Session $session,
        string $eventKey,
        CarbonImmutable $occurredAt,
        array $attributes = [],
    ): Event {
        return Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $this->eventTypeIds[$eventKey],
            'page_id' => $attributes['page_id'] ?? null,
            'cta_id' => $attributes['cta_id'] ?? null,
            'lead_box_id' => $attributes['lead_box_id'] ?? null,
            'lead_slot_id' => $attributes['lead_slot_id'] ?? null,
            'popup_id' => $attributes['popup_id'] ?? null,
            'surface_id' => $attributes['surface_id'] ?? null,
            'subject_type' => $attributes['subject_type'] ?? null,
            'subject_id' => $attributes['subject_id'] ?? null,
            'occurred_at' => $occurredAt,
            'properties' => $attributes['properties'] ?? ['seed' => 'analytics_demo'],
            'created_at' => $occurredAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createConversion(
        Visitor $visitor,
        Session $session,
        CarbonImmutable $occurredAt,
        array $attributes = [],
    ): Conversion {
        return Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => $attributes['conversion_type_id'],
            'source_type' => $attributes['source_type'] ?? 'analytics_demo',
            'source_id' => $attributes['source_id'] ?? null,
            'lead_id' => $attributes['lead_id'] ?? null,
            'popup_lead_id' => $attributes['popup_lead_id'] ?? null,
            'page_id' => $attributes['page_id'] ?? null,
            'cta_id' => $attributes['cta_id'] ?? null,
            'lead_box_id' => $attributes['lead_box_id'] ?? null,
            'lead_slot_id' => $attributes['lead_slot_id'] ?? null,
            'popup_id' => $attributes['popup_id'] ?? null,
            'occurred_at' => $occurredAt,
            'properties' => $attributes['properties'] ?? ['seed' => 'analytics_demo'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordConversionEvent(
        Visitor $visitor,
        Session $session,
        \DateTimeInterface $occurredAt,
        array $attributes = [],
    ): void {
        $this->createEvent(
            $visitor,
            $session,
            'conversion.recorded',
            CarbonImmutable::instance($occurredAt)->addSeconds(1),
            $attributes,
        );
    }
}
