<?php

namespace App\Services\Communications;

use App\Models\CommunicationTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunicationTemplateVersionService
{
    public function createVersion(CommunicationTemplate $template, array $versionData, ?int $userId = null)
    {
        return DB::transaction(function () use ($template, $userId, $versionData) {
            $nextVersionNumber = (int) $template->versions()->max('version_number') + 1;

            $versionData['version_number'] = $nextVersionNumber;

            if ($userId !== null && Schema::hasColumn('communication_template_versions', 'created_by')) {
                $versionData['created_by'] = $userId;
            }

            return $template->versions()->create($versionData);
        });
    }
}
