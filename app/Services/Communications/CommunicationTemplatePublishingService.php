<?php

namespace App\Services\Communications;

use App\Models\CommunicationTemplate;
use App\Models\CommunicationTemplateVersion;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CommunicationTemplatePublishingService
{
    public function publish(CommunicationTemplate $template, CommunicationTemplateVersion $version)
    {
        if ((int) $version->communication_template_id !== (int) $template->id) {
            throw new InvalidArgumentException('Template version does not belong to the provided template.');
        }

        return DB::transaction(function () use ($template, $version) {
            $template->versions()->update([
                'is_published' => false,
            ]);

            $version->forceFill([
                'is_published' => true,
                'published_at' => now(),
            ])->save();

            $template->forceFill([
                'current_version_id' => $version->id,
            ])->save();

            return $version;
        });
    }
}
