<?php

namespace App\Services\QO;

use App\Models\QOItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateQOItemService
{
    public function duplicate(QOItem $item): QOItem
    {
        return DB::transaction(function () use ($item) {
            $item->loadMissing([
                'questions.options',
                'outcomes',
                'promoRules',
                'ctaTemplates',
            ]);

            $duplicate = $item->replicate();
            $duplicate->title = $this->uniqueTitle($item->title);
            $duplicate->internal_name = $item->internal_name
                ? $this->uniqueTitle($item->internal_name)
                : null;
            $duplicate->slug = $this->uniqueSlug($item->slug ?: $item->title);
            $duplicate->status = 'draft';
            $duplicate->published_at = null;
            $duplicate->save();

            foreach ($item->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->qo_item_id = $duplicate->id;
                $newQuestion->save();

                foreach ($question->options as $option) {
                    $newOption = $option->replicate();
                    $newOption->qo_question_id = $newQuestion->id;
                    $newOption->save();
                }
            }

            foreach ($item->outcomes as $outcome) {
                $newOutcome = $outcome->replicate();
                $newOutcome->qo_item_id = $duplicate->id;
                $newOutcome->save();
            }

            foreach ($item->promoRules as $promoRule) {
                $newPromoRule = $promoRule->replicate();
                $newPromoRule->qo_item_id = $duplicate->id;
                $newPromoRule->save();
            }

            foreach ($item->ctaTemplates as $ctaTemplate) {
                $newCtaTemplate = $ctaTemplate->replicate();
                $newCtaTemplate->qo_item_id = $duplicate->id;
                $newCtaTemplate->save();
            }

            return $duplicate->refresh();
        });
    }

    protected function uniqueTitle(string $title): string
    {
        $baseTitle = trim($title) !== '' ? trim($title) : 'Untitled QO Item';
        $copyTitle = $baseTitle.' (Copy)';

        if (! QOItem::query()->where('title', $copyTitle)->exists()) {
            return $copyTitle;
        }

        $counter = 2;

        do {
            $candidate = $baseTitle.' (Copy '.$counter.')';
            $counter++;
        } while (QOItem::query()->where('title', $candidate)->exists());

        return $candidate;
    }

    protected function uniqueSlug(string $source): string
    {
        $baseSlug = Str::slug($source) ?: 'qo-item';
        $copySlug = $baseSlug.'-copy';

        if (! QOItem::query()->where('slug', $copySlug)->exists()) {
            return $copySlug;
        }

        $counter = 2;

        do {
            $candidate = $copySlug.'-'.$counter;
            $counter++;
        } while (QOItem::query()->where('slug', $candidate)->exists());

        return $candidate;
    }
}
