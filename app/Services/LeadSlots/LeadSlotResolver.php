<?php

namespace App\Services\LeadSlots;

use App\Models\LeadBox;
use App\Models\LeadSlot;

class LeadSlotResolver
{
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

        $registrySlotKeys = collect(config('lead_blocks.slot_definitions', []))
            ->filter(fn (array $definition) => ($definition['page_key'] ?? null) === $pageKey)
            ->keys()
            ->all();

        $slotKeys = array_values(array_unique(array_merge($slotKeys, $registrySlotKeys)));

        if ($slotKeys === []) {
            return [];
        }

        $slots = LeadSlot::query()
            ->with('assignment.leadBox')
            ->whereIn('key', $slotKeys)
            ->get()
            ->keyBy('key');

        $resolved = [];
        foreach ($slotKeys as $slotKey) {
            $resolved[$slotKey] = $this->resolveSlot(
                $slots->get($slotKey),
                $slotKey,
                $pageKey,
            );
        }

        return $resolved;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function resolveSlot(?LeadSlot $slot, string $slotKey, string $pageKey): ?array
    {
        if (! $slot || ! $slot->is_enabled) {
            return null;
        }

        $assignment = $slot->assignment;
        if (! $assignment || ! $assignment->leadBox) {
            return null;
        }

        $leadBox = $assignment->leadBox;

        if (trim(strtolower((string) $leadBox->status)) !== LeadBox::STATUS_ACTIVE) {
            return null;
        }

        return $this->presenter->present($leadBox, $assignment, $slotKey, $pageKey);
    }
}
