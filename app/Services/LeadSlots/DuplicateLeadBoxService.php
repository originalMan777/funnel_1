<?php

namespace App\Services\LeadSlots;

use App\Models\LeadBox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateLeadBoxService
{
    public function duplicate(LeadBox $leadBox): LeadBox
    {
        return DB::transaction(function () use ($leadBox): LeadBox {
            $duplicate = $leadBox->replicate([
                'status',
                'internal_name',
                'title',
                'created_at',
                'updated_at',
            ]);

            $duplicate->status = LeadBox::STATUS_DRAFT;
            $duplicate->internal_name = $this->uniqueInternalName($leadBox);
            $duplicate->title = $this->copyTitle($leadBox->title);
            $duplicate->content = $leadBox->content ?? [];
            $duplicate->settings = $leadBox->settings ?? [];
            $duplicate->save();

            return $duplicate;
        });
    }

    private function copyTitle(string $title): string
    {
        $copyTitle = $title.' (Copy)';

        return Str::limit($copyTitle, 200, '');
    }

    private function uniqueInternalName(LeadBox $leadBox): string
    {
        $base = Str::of($leadBox->internal_name)
            ->lower()
            ->replaceMatches('/[^a-z0-9_\-]+/', '_')
            ->trim('_-')
            ->value();

        if ($base === '') {
            $base = 'lead_box_'.$leadBox->id;
        }

        $base = Str::limit($base.'_copy', 150, '');
        $candidate = $base;
        $counter = 2;

        while (LeadBox::query()->where('internal_name', $candidate)->exists()) {
            $suffix = '_'.$counter;
            $candidate = Str::limit($base, 160 - strlen($suffix), '').$suffix;
            $counter++;
        }

        return $candidate;
    }
}
