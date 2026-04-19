<?php

namespace App\Http\Requests\Requests\Admin\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in([
                Campaign::STATUS_DRAFT,
                Campaign::STATUS_ACTIVE,
                Campaign::STATUS_PAUSED,
                Campaign::STATUS_ARCHIVED,
            ])],
            'audience_type' => ['required', 'string', Rule::in([
                Campaign::AUDIENCE_LEADS,
                Campaign::AUDIENCE_POPUP_LEADS,
                Campaign::AUDIENCE_ACQUISITION_CONTACTS,
            ])],
            'entry_trigger' => ['required', 'string', Rule::in($this->allowedEntryTriggers())],
            'description' => ['nullable', 'string'],

            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['required', 'integer', 'min:1', 'distinct'],
            'steps.*.delay_amount' => ['required', 'integer', 'min:0'],
            'steps.*.delay_unit' => ['required', 'string', Rule::in(['days', 'hours', 'weeks'])],
            'steps.*.send_mode' => ['required', 'string', Rule::in([
                CampaignStep::SEND_MODE_TEMPLATE,
                CampaignStep::SEND_MODE_CUSTOM,
            ])],
            'steps.*.template_id' => ['nullable', 'integer', 'exists:communication_templates,id'],
            'steps.*.subject' => ['nullable', 'string', 'max:255'],
            'steps.*.html_body' => ['nullable', 'string'],
            'steps.*.text_body' => ['nullable', 'string'],
            'steps.*.is_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ($this->input('steps', []) as $index => $step) {
                $sendMode = $step['send_mode'] ?? null;
                $templateId = $step['template_id'] ?? null;
                $subject = $step['subject'] ?? null;
                $htmlBody = $step['html_body'] ?? null;
                $textBody = $step['text_body'] ?? null;

                if ($sendMode === CampaignStep::SEND_MODE_TEMPLATE && blank($templateId)) {
                    $validator->errors()->add("steps.{$index}.template_id", 'Choose a template for template-based steps.');
                }

                if ($sendMode === CampaignStep::SEND_MODE_CUSTOM) {
                    if (blank($subject)) {
                        $validator->errors()->add("steps.{$index}.subject", 'Add a subject for custom steps.');
                    }

                    if (blank($htmlBody) && blank($textBody)) {
                        $validator->errors()->add("steps.{$index}.html_body", 'Add an HTML body or text body for custom steps.');
                    }
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedCampaignData(): array
    {
        return $this->safe()->only([
            'name',
            'status',
            'audience_type',
            'entry_trigger',
            'description',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function validatedSteps(): array
    {
        return collect($this->validated('steps', []))
            ->map(fn (array $step): array => [
                'step_order' => (int) $step['step_order'],
                'delay_amount' => (int) $step['delay_amount'],
                'delay_unit' => (string) $step['delay_unit'],
                'send_mode' => (string) $step['send_mode'],
                'template_id' => $step['send_mode'] === CampaignStep::SEND_MODE_TEMPLATE
                    ? ($step['template_id'] ?? null)
                    : null,
                'subject' => $step['send_mode'] === CampaignStep::SEND_MODE_CUSTOM
                    ? ($step['subject'] ?? null)
                    : null,
                'html_body' => $step['send_mode'] === CampaignStep::SEND_MODE_CUSTOM
                    ? ($step['html_body'] ?? null)
                    : null,
                'text_body' => $step['send_mode'] === CampaignStep::SEND_MODE_CUSTOM
                    ? ($step['text_body'] ?? null)
                    : null,
                'is_enabled' => (bool) ($step['is_enabled'] ?? true),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'campaign name',
            'status' => 'campaign status',
            'audience_type' => 'campaign audience',
            'entry_trigger' => 'entry trigger',
            'description' => 'campaign description',
            'steps.*.step_order' => 'step order',
            'steps.*.delay_amount' => 'delay',
            'steps.*.delay_unit' => 'delay unit',
            'steps.*.send_mode' => 'send mode',
            'steps.*.template_id' => 'template',
            'steps.*.subject' => 'subject',
            'steps.*.html_body' => 'HTML body',
            'steps.*.text_body' => 'text body',
            'steps.*.is_enabled' => 'enabled state',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function allowedEntryTriggers(): array
    {
        return collect(config('communication-bindings', []))
            ->pluck('event_key')
            ->filter(fn ($eventKey): bool => is_string($eventKey) && filled($eventKey))
            ->values()
            ->all();
    }
}
