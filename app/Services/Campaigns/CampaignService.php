<?php

namespace App\Services\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function create(array $campaignData, array $steps, ?int $userId = null): Campaign
    {
        return DB::transaction(function () use ($campaignData, $steps, $userId): Campaign {
            if ($userId !== null) {
                $campaignData['created_by'] = $userId;
                $campaignData['updated_by'] = $userId;
            }

            /** @var \App\Models\Campaign $campaign */
            $campaign = Campaign::query()->create($campaignData);

            $this->syncSteps($campaign, $steps);

            return $campaign->load('steps');
        });
    }

    public function update(Campaign $campaign, array $campaignData, array $steps, ?int $userId = null): Campaign
    {
        return DB::transaction(function () use ($campaign, $campaignData, $steps, $userId): Campaign {
            if ($userId !== null) {
                $campaignData['updated_by'] = $userId;
            }

            $campaign->fill($campaignData);
            $campaign->save();

            $this->syncSteps($campaign, $steps);

            return $campaign->load('steps');
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function syncSteps(Campaign $campaign, array $steps): void
    {
        $campaign->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            $campaign->steps()->create([
                'step_order' => (int) ($step['step_order'] ?? ($index + 1)),
                'delay_amount' => (int) ($step['delay_amount'] ?? 0),
                'delay_unit' => (string) ($step['delay_unit'] ?? 'days'),
                'send_mode' => (string) ($step['send_mode'] ?? 'template'),
                'template_id' => $step['template_id'] ?? null,
                'subject' => $step['subject'] ?? null,
                'html_body' => $step['html_body'] ?? null,
                'text_body' => $step['text_body'] ?? null,
                'is_enabled' => (bool) ($step['is_enabled'] ?? true),
            ]);
        }
    }
}
