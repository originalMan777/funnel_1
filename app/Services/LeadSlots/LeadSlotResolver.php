<?php

namespace App\Services\LeadSlots;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;

class LeadSlotResolver
{
    private const SLOT_TYPE_MAP = [
        'home_intro' => LeadBox::TYPE_RESOURCE,
        'home_mid' => LeadBox::TYPE_SERVICE,
        'home_bottom' => LeadBox::TYPE_OFFER,
    ];

    private const HOME_SLOT_KEYS = [
        'home_intro',
        'home_mid',
        'home_bottom',
    ];

    public function __construct(
        private readonly LeadBoxPresenter $presenter,
    ) {
    }

    /**
     * Resolve slot render models for a given page key.
     *
     * @return array<string, array<string,mixed>|null> Map: slotKey => renderModel|null
     */
    public function resolve(string $pageKey): array
    {
        $slotKeys = config('lead_blocks.page_slots')[$pageKey] ?? [];

        // Defensive: keep homepage slots resolvable even if the config page map is stale/out-of-sync.
        if ($pageKey === 'home') {
            $slotKeys = array_values(array_unique(array_merge($slotKeys, self::HOME_SLOT_KEYS)));
        }

        $resolved = [];
        foreach ($slotKeys as $slotKey) {
            $resolved[$slotKey] = $this->resolveSlot($slotKey, $pageKey);
        }

        return $resolved;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function resolveSlot(string $slotKey, string $pageKey): ?array
    {
        $slot = LeadSlot::query()->where('key', $slotKey)->first();
        if (! $slot || ! $slot->is_enabled) {
            return null;
        }

        $assignment = LeadAssignment::query()
            ->with('leadBox')
            ->where('lead_slot_id', $slot->id)
            ->first();

        if (! $assignment || ! $assignment->leadBox) {
            return null;
        }

        $leadBox = $assignment->leadBox;
        $requiredType = self::SLOT_TYPE_MAP[$slotKey] ?? null;

        if (
            trim(strtolower((string) $leadBox->status)) !== LeadBox::STATUS_ACTIVE
            || ($requiredType !== null && $leadBox->type !== $requiredType)
        ) {
            return null;
        }

        return $this->presenter->present($leadBox, $assignment, $slotKey, $pageKey);
    }
}
