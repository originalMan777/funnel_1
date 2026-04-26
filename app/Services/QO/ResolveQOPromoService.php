<?php

namespace App\Services\QO;

use App\Models\QOItem;
use App\Models\QOOutcome;

class ResolveQOPromoService
{
    public function resolve(
        QOItem $item,
        string $zone,
        ?int $questionOrder = null,
        ?QOOutcome $outcome = null
    ): ?int {
        return match ($zone) {
            'intro' => $item->intro_lead_box_id,
            'inline' => $this->resolveInline($item, $questionOrder),
            'pre_outcome' => $item->pre_outcome_lead_box_id,
            'post_outcome' => $outcome?->lead_box_id ?? $item->post_outcome_lead_box_id,
            default => null,
        };
    }

    protected function resolveInline(QOItem $item, ?int $questionOrder): ?int
    {
        return match ($item->inline_promo_mode) {
            'fixed' => $item->inline_lead_box_id,
            'rotate_interval' => $this->resolveRotateInterval($item, $questionOrder),
            'custom_ranges' => $this->resolveCustomRange($item, $questionOrder),
            default => null,
        };
    }

    protected function resolveRotateInterval(QOItem $item, ?int $questionOrder): ?int
    {
        if (!$questionOrder || !$item->inline_rotation_interval) {
            return $item->inline_lead_box_id;
        }

        $rules = $item->promoRules()
            ->where('is_active', true)
            ->where('rule_type', 'interval')
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            return $item->inline_lead_box_id;
        }

        $index = intdiv(max($questionOrder - 1, 0), max((int) $item->inline_rotation_interval, 1));

        return $rules[$index % $rules->count()]?->lead_box_id ?? $item->inline_lead_box_id;
    }

    protected function resolveCustomRange(QOItem $item, ?int $questionOrder): ?int
    {
        if (!$questionOrder) {
            return $item->inline_lead_box_id;
        }

        $rule = $item->promoRules()
            ->where('is_active', true)
            ->where('rule_type', 'range')
            ->where(function ($query) use ($questionOrder) {
                $query
                    ->whereNull('start_question_order')
                    ->orWhere('start_question_order', '<=', $questionOrder);
            })
            ->where(function ($query) use ($questionOrder) {
                $query
                    ->whereNull('end_question_order')
                    ->orWhere('end_question_order', '>=', $questionOrder);
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->first();

        return $rule?->lead_box_id ?? $item->inline_lead_box_id;
    }
}
