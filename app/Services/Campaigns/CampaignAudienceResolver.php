<?php

namespace App\Services\Campaigns;

use App\Models\CampaignEnrollment;

class CampaignAudienceResolver
{
    /**
     * @return array{email: string, name: ?string, source_type: string, source_id: int}|null
     */
    public function resolve(CampaignEnrollment $enrollment): ?array
    {
        $enrollment->loadMissing([
            'lead',
            'popupLead',
            'acquisitionContact',
        ]);

        if ($enrollment->lead && filled($enrollment->lead->email)) {
            return [
                'email' => (string) $enrollment->lead->email,
                'name' => $enrollment->lead->first_name ?: null,
                'source_type' => 'lead',
                'source_id' => (int) $enrollment->lead->id,
            ];
        }

        if ($enrollment->popupLead && filled($enrollment->popupLead->email)) {
            return [
                'email' => (string) $enrollment->popupLead->email,
                'name' => $enrollment->popupLead->name ?: null,
                'source_type' => 'popupLead',
                'source_id' => (int) $enrollment->popupLead->id,
            ];
        }

        if ($enrollment->acquisitionContact && filled($enrollment->acquisitionContact->primary_email)) {
            return [
                'email' => (string) $enrollment->acquisitionContact->primary_email,
                'name' => $enrollment->acquisitionContact->display_name ?: null,
                'source_type' => 'acquisitionContact',
                'source_id' => (int) $enrollment->acquisitionContact->id,
            ];
        }

        return null;
    }
}
