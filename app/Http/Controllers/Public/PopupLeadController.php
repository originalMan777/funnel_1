<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Popup;
use App\Models\PopupLead;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PopupLeadController extends Controller
{
    public function __construct(
        private readonly SecurityAuditLogger $securityAuditLogger,
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $baseValidated = $request->validate([
            'popup_id' => ['required', 'integer', Rule::exists('popups', 'id')],
            'page_key' => ['nullable', 'string', 'max:100'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            'website' => ['nullable', 'string', 'max:1'],
        ]);

        if (!empty($baseValidated['website'])) {
            throw ValidationException::withMessages([
                'popup' => 'Invalid submission.',
            ]);
        }

        $popup = Popup::query()
            ->whereKey($baseValidated['popup_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $requestedPage = $baseValidated['page_key'] ?? null;

        if (! $this->passesAudienceRule($popup, (bool) $request->user())) {
            throw ValidationException::withMessages([
                'popup' => 'This popup is not available.',
            ]);
        }

        if (!empty($popup->target_pages)) {
            $allowedPages = collect($popup->target_pages)->map(fn ($p) => (string) $p);

            if ($requestedPage === null || !$allowedPages->contains($requestedPage)) {
                throw ValidationException::withMessages([
                    'popup' => 'This popup is not valid for this page.',
                ]);
            }
        }

        if ($popup->suppression_scope === 'all_lead_popups') {
            if ($request->cookie('nojo_lead_captured') === '1') {
                throw ValidationException::withMessages([
                    'popup' => 'You have already submitted your information.',
                ]);
            }
        }

        $popupCookie = 'nojo_popup_submitted_' . Str::slug((string) $popup->slug, '_');

        if ($request->cookie($popupCookie) === '1') {
            throw ValidationException::withMessages([
                'popup' => 'You have already submitted this popup.',
            ]);
        }

        $recentSubmission = PopupLead::query()
            ->where('ip_address', $request->ip())
            ->where('popup_id', $popup->id)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recentSubmission) {
            throw ValidationException::withMessages([
                'popup' => 'Too many submissions. Please wait a moment.',
            ]);
        }

        $fields = collect($popup->form_fields ?? [])->values();

        if ($fields->isEmpty()) {
            $fields = collect(['email']);
        }

        $payload = $request->validate([
            'name' => $fields->contains('name')
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'email' => $fields->contains('email')
                ? ['required', 'email:rfc', 'max:255']
                : ['nullable', 'email:rfc', 'max:255'],
            'phone' => $fields->contains('phone')
                ? ['required', 'string', 'max:50']
                : ['nullable', 'string', 'max:50'],
            'message' => $fields->contains('message')
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'],
        ]);

        $payload = [
            'name' => isset($payload['name']) ? Str::squish(strip_tags((string) $payload['name'])) : null,
            'email' => isset($payload['email']) ? trim(Str::lower((string) $payload['email'])) : null,
            'phone' => isset($payload['phone']) ? trim(strip_tags((string) $payload['phone'])) : null,
            'message' => isset($payload['message']) ? trim((string) $payload['message']) : null,
        ];

        if (!empty($payload['email'])) {
            $recentEmailSubmission = PopupLead::query()
                ->where('popup_id', $popup->id)
                ->where('email', $payload['email'])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->exists();

            if ($recentEmailSubmission) {
                throw ValidationException::withMessages([
                    'popup' => 'Too many submissions. Please wait a moment.',
                ]);
            }
        }

        $derivedSourceUrl = $this->deriveSafeSourceUrl($request);

        $lead = PopupLead::create([
            'popup_id' => $popup->id,
            'page_key' => $requestedPage,
            'source_url' => $derivedSourceUrl,
            'lead_type' => $popup->lead_type,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'message' => $payload['message'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'popup_slug' => $popup->slug,
                'popup_type' => $popup->type,
                'popup_role' => $popup->role,
                'trigger_type' => $popup->trigger_type,
                'target_pages' => $popup->target_pages ?? [],
                'post_submit_action' => $popup->post_submit_action,
            ],
        ]);

        $cookieMinutes = 60 * 24 * 365;
        $secureCookie = (bool) (config('session.secure') ?? $request->isSecure());

        Cookie::queue(Cookie::make(
            $popupCookie,
            '1',
            $cookieMinutes,
            '/',
            null,
            $secureCookie,
            true,
            false,
            'lax'
        ));

        if ($popup->suppression_scope === 'all_lead_popups') {
            Cookie::queue(Cookie::make(
                'nojo_lead_captured',
                '1',
                $cookieMinutes,
                '/',
                null,
                $secureCookie,
                true,
                false,
                'lax'
            ));
        }

        $this->securityAuditLogger->log(
            event: 'popup_lead_created',
            request: $request,
            userId: null,
            entityType: 'popup_lead',
            entityId: (int) $lead->id,
            context: [
                'popup_id' => $popup->id,
                'popup_slug' => $popup->slug,
                'page_key' => $requestedPage,
            ]
        );

        return back()->with(
            'popupLeadSuccess',
            $popup->success_message ?: 'Thanks. We received your information.'
        );
    }

    private function deriveSafeSourceUrl(Request $request): ?string
    {
        $referer = trim((string) $request->headers->get('referer', ''));

        if ($referer === '') {
            return null;
        }

        if (!filter_var($referer, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $referer;
    }

    private function passesAudienceRule(Popup $popup, bool $isAuthenticated): bool
    {
        return match ($popup->audience) {
            'authenticated' => $isAuthenticated,
            'guests' => ! $isAuthenticated,
            default => true,
        };
    }
}
