<?php

namespace App\Http\Middleware;

use App\Models\Popup;
use App\Services\Analytics\AnalyticsBootstrapService;
use App\Services\LeadSlots\LeadSlotResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private static ?bool $leadTablesReady = null;

    private static ?bool $popupTableReady = null;

    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'popupLeadSuccess' => fn () => $request->session()->get('popupLeadSuccess'),
            ],
            'siteContent' => config('site_content'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'analytics' => fn () => app(AnalyticsBootstrapService::class)->forRequest($request),
            'popupManager' => fn () => $this->resolvePopupManager($request),
            'leadSlots' => fn () => $this->resolveLeadSlots($request),
        ];
    }

    /**
     * @return array<string, array<string,mixed>|null>
     */
    private function resolveLeadSlots(Request $request): array
    {
        $pageKey = $this->resolvePopupPageKey($request);

        if (! $pageKey || ! $this->leadTablesAreReady()) {
            return [];
        }

        return app(LeadSlotResolver::class)->resolve($pageKey);
    }

    private function leadTablesAreReady(): bool
    {
        if (self::$leadTablesReady !== null) {
            return self::$leadTablesReady;
        }

        return self::$leadTablesReady = Schema::hasTable('lead_slots')
            && Schema::hasTable('lead_assignments')
            && Schema::hasTable('lead_boxes');
    }

    /**
     * @return array{pageKey:?string,leadCaptured:bool,isAuthenticated:bool,popups:array<int,array<string,mixed>>}
     */
    private function resolvePopupManager(Request $request): array
    {
        $pageKey = $this->resolvePopupPageKey($request);
        $leadCaptured = $request->cookie('nojo_lead_captured') === '1';
        $isAuthenticated = (bool) $request->user();

        if (! $pageKey || ! $this->popupTableIsReady()) {
            return [
                'pageKey' => $pageKey,
                'leadCaptured' => $leadCaptured,
                'isAuthenticated' => $isAuthenticated,
                'popups' => [],
            ];
        }

        $popups = Popup::query()
            ->select([
                'id',
                'name',
                'slug',
                'type',
                'role',
                'priority',
                'eyebrow',
                'headline',
                'body',
                'cta_text',
                'success_message',
                'layout',
                'trigger_type',
                'trigger_delay',
                'trigger_scroll',
                'target_pages',
                'device',
                'frequency',
                'audience',
                'suppress_if_lead_captured',
                'suppression_scope',
                'form_fields',
                'lead_type',
                'post_submit_action',
                'post_submit_redirect_url',
            ])
            ->where('is_active', true)
            ->where(function ($query) use ($pageKey) {
                $query->whereNull('target_pages')
                    ->orWhereJsonLength('target_pages', 0)
                    ->orWhereJsonContains('target_pages', $pageKey);
            })
            ->where(function ($query) use ($isAuthenticated) {
                $allowedAudiences = $isAuthenticated
                    ? ['everyone', 'authenticated']
                    : ['everyone', 'guests'];

                $query->whereNull('audience')
                    ->orWhereIn('audience', $allowedAudiences);
            })
            ->orderBy('priority')
            ->latest('updated_at')
            ->get()
            ->filter(function (Popup $popup) use ($leadCaptured, $request): bool {
                if ($popup->suppress_if_lead_captured && $leadCaptured) {
                    return false;
                }

                if ($popup->suppression_scope === 'this_popup_only' && $this->popupSpecificCookieKey($popup, $request) !== null) {
                    return false;
                }

                return true;
            })
            ->values()
            ->map(fn (Popup $popup) => [
                'id' => $popup->id,
                'name' => $popup->name,
                'slug' => $popup->slug,
                'type' => $popup->type,
                'role' => $popup->role,
                'priority' => $popup->priority,
                'eyebrow' => $popup->eyebrow,
                'headline' => $popup->headline,
                'body' => $popup->body,
                'cta_text' => $popup->cta_text,
                'success_message' => $popup->success_message,
                'layout' => $popup->layout,
                'trigger_type' => $popup->trigger_type,
                'trigger_delay' => $popup->trigger_delay,
                'trigger_scroll' => $popup->trigger_scroll,
                'target_pages' => $popup->target_pages ?? [],
                'device' => $popup->device,
                'frequency' => $popup->frequency,
                'audience' => $popup->audience,
                'suppress_if_lead_captured' => $popup->suppress_if_lead_captured,
                'suppression_scope' => $popup->suppression_scope,
                'form_fields' => $popup->form_fields ?? ['name', 'email'],
                'lead_type' => $popup->lead_type,
                'post_submit_action' => $popup->post_submit_action,
                'post_submit_redirect_url' => $popup->post_submit_redirect_url,
            ])
            ->all();

        return [
            'pageKey' => $pageKey,
            'leadCaptured' => $leadCaptured,
            'isAuthenticated' => $isAuthenticated,
            'popups' => $popups,
        ];
    }

    private function popupTableIsReady(): bool
    {
        if (self::$popupTableReady !== null) {
            return self::$popupTableReady;
        }

        if (! Schema::hasTable('popups')) {
            return self::$popupTableReady = false;
        }

        foreach (['role', 'priority', 'audience', 'suppress_if_lead_captured', 'suppression_scope', 'post_submit_action'] as $column) {
            if (! Schema::hasColumn('popups', $column)) {
                return self::$popupTableReady = false;
            }
        }

        return self::$popupTableReady = true;
    }

    private function popupSpecificCookieKey(Popup $popup, Request $request): ?string
    {
        $cookieKey = 'nojo_popup_submitted_'.Str::slug((string) $popup->slug, '_');

        return $request->cookie($cookieKey) === '1' ? $cookieKey : null;
    }

    private function resolvePopupPageKey(Request $request): ?string
    {
        $routeName = $request->route()?->getName();
        $excludedRoutePrefixes = config('public_pages.excluded_route_prefixes', []);
        $excludedRouteNames = config('public_pages.excluded_route_names', []);
        $routeNameKeys = config('public_pages.route_name_keys', []);
        $routePrefixKeys = config('public_pages.route_prefix_keys', []);
        $pathKeys = config('public_pages.path_keys', []);
        $pathPrefixKeys = config('public_pages.path_prefix_keys', []);

        if ($routeName) {
            foreach ($excludedRoutePrefixes as $excludedRoutePrefix) {
                if (str_starts_with($routeName, $excludedRoutePrefix)) {
                    return null;
                }
            }

            if (in_array($routeName, $excludedRouteNames, true)) {
                return null;
            }

            if (array_key_exists($routeName, $routeNameKeys)) {
                return $routeNameKeys[$routeName];
            }

            foreach ($routePrefixKeys as $routePrefix => $pageKey) {
                if (str_starts_with($routeName, $routePrefix)) {
                    return $pageKey;
                }
            }

            return null;
        }

        $path = $request->path();

        if (array_key_exists($path, $pathKeys)) {
            return $pathKeys[$path];
        }

        foreach ($pathPrefixKeys as $pathPrefix => $pageKey) {
            if (str_starts_with($path, $pathPrefix)) {
                return $pageKey;
            }
        }

        return null;
    }
}
