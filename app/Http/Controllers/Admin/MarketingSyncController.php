<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingContactSync;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MarketingSyncController extends Controller
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->string('status')->toString());
        $provider = trim((string) $request->string('provider')->toString());
        $email = trim((string) $request->string('email')->toString());
        $audienceKey = trim((string) $request->string('audience_key')->toString());
        $dateFrom = $this->normalizeDateFilter($request->string('date_from')->toString());
        $dateTo = $this->normalizeDateFilter($request->string('date_to')->toString());

        $syncs = MarketingContactSync::query()
            ->with('acquisitionContact:id,display_name,primary_email')
            ->when($status !== '', fn ($query) => $query->where('last_sync_status', $status))
            ->when($provider !== '', fn ($query) => $query->where('provider', $provider))
            ->when($email !== '', fn ($query) => $query->where('email', 'like', '%'.$email.'%'))
            ->when($audienceKey !== '', fn ($query) => $query->where('audience_key', 'like', '%'.$audienceKey.'%'))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('updated_at', '>=', Carbon::parse($dateFrom)->toDateString()))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('updated_at', '<=', Carbon::parse($dateTo)->toDateString()))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (MarketingContactSync $sync) => [
                'id' => $sync->id,
                'acquisition_contact' => $sync->acquisitionContact ? [
                    'id' => $sync->acquisitionContact->id,
                    'display_name' => $sync->acquisitionContact->display_name,
                    'email' => $sync->acquisitionContact->primary_email,
                ] : null,
                'provider' => $sync->provider,
                'audience_key' => $sync->audience_key,
                'email' => $sync->email,
                'external_contact_id' => $sync->external_contact_id,
                'last_sync_status' => $sync->last_sync_status,
                'last_error_message' => $sync->last_error_message,
                'last_synced_at' => optional($sync->last_synced_at)?->toISOString(),
                'updated_at' => optional($sync->updated_at)?->toISOString(),
            ]);

        return Inertia::render('Admin/Communications/Syncs/Index', [
            'filters' => [
                'status' => $status,
                'provider' => $provider,
                'email' => $email,
                'audience_key' => $audienceKey,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'syncs' => $syncs,
        ]);
    }

    public function show(MarketingContactSync $marketingContactSync): Response
    {
        $marketingContactSync->load('acquisitionContact:id,display_name,primary_email');

        return Inertia::render('Admin/Communications/Syncs/Show', [
            'sync' => [
                'id' => $marketingContactSync->id,
                'acquisition_contact' => $marketingContactSync->acquisitionContact ? [
                    'id' => $marketingContactSync->acquisitionContact->id,
                    'display_name' => $marketingContactSync->acquisitionContact->display_name,
                    'email' => $marketingContactSync->acquisitionContact->primary_email,
                ] : null,
                'provider' => $marketingContactSync->provider,
                'audience_key' => $marketingContactSync->audience_key,
                'email' => $marketingContactSync->email,
                'external_contact_id' => $marketingContactSync->external_contact_id,
                'last_sync_status' => $marketingContactSync->last_sync_status,
                'last_error_message' => $marketingContactSync->last_error_message,
                'metadata' => $marketingContactSync->metadata ?? [],
                'created_at' => optional($marketingContactSync->created_at)?->toISOString(),
                'updated_at' => optional($marketingContactSync->updated_at)?->toISOString(),
                'last_synced_at' => optional($marketingContactSync->last_synced_at)?->toISOString(),
            ],
        ]);
    }

    private function normalizeDateFilter(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return '';
        }
    }
}
