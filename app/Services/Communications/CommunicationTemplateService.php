<?php

namespace App\Services\Communications;

use App\Models\CommunicationTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunicationTemplateService
{
    public function create(array $templateData, array $bindings, ?int $userId = null)
    {
        return DB::transaction(function () use ($bindings, $templateData, $userId) {
            if ($userId !== null && Schema::hasColumn('communication_templates', 'created_by')) {
                $templateData['created_by'] = $userId;
            }

            if ($userId !== null && Schema::hasColumn('communication_templates', 'updated_by')) {
                $templateData['updated_by'] = $userId;
            }

            $template = CommunicationTemplate::query()->create($templateData);

            $this->syncBindings($template, $bindings);

            return $template;
        });
    }

    public function update(CommunicationTemplate $template, array $templateData, array $bindings, ?int $userId = null)
    {
        return DB::transaction(function () use ($bindings, $template, $templateData, $userId) {
            if ($userId !== null && Schema::hasColumn('communication_templates', 'updated_by')) {
                $templateData['updated_by'] = $userId;
            }

            $template->fill($templateData);
            $template->save();

            $this->syncBindings($template, $bindings);

            return $template;
        });
    }

    private function syncBindings(CommunicationTemplate $template, array $bindings): void
    {
        $template->bindings()->delete();

        foreach ($bindings as $binding) {
            $template->bindings()->create($binding);
        }
    }
}
