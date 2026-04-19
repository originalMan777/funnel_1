<?php

namespace App\Http\Requests\Requests\Admin\Communications;

use App\Models\CommunicationTemplate;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunicationTemplateRequest extends FormRequest
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
        /** @var \App\Models\CommunicationTemplate $template */
        $template = $this->route('template');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('communication_templates', 'key')->ignore($template->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'required',
                'string',
                Rule::in([
                    CommunicationTemplate::STATUS_DRAFT,
                    CommunicationTemplate::STATUS_ACTIVE,
                    CommunicationTemplate::STATUS_ARCHIVED,
                ]),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'from_name_override' => [
                'nullable',
                'string',
                'max:255',
            ],
            'from_email_override' => [
                'nullable',
                'email',
                'max:255',
            ],
            'reply_to_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'bindings' => [
                'nullable',
                'array',
            ],
            'bindings.*.event_key' => [
                'required_with:bindings',
                'string',
                'max:255',
                Rule::in($this->allowedEventKeys()),
            ],
            'bindings.*.action_key' => [
                'required_with:bindings',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! $this->bindingActionIsAllowed($attribute, $value)) {
                        $fail('The selected binding action key is invalid for the selected binding event key.');
                    }
                },
            ],
            'bindings.*.is_enabled' => [
                'nullable',
                'boolean',
            ],
            'bindings.*.priority' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'key' => 'template key',
            'name' => 'template name',
            'status' => 'template status',
            'from_name_override' => 'from name override',
            'from_email_override' => 'from email override',
            'reply_to_email' => 'reply-to email',
            'bindings.*.event_key' => 'binding event key',
            'bindings.*.action_key' => 'binding action key',
            'bindings.*.is_enabled' => 'binding enabled state',
            'bindings.*.priority' => 'binding priority',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedTemplateData(): array
    {
        $data = $this->safe()->only([
            'key',
            'name',
            'status',
            'description',
            'from_name_override',
            'from_email_override',
            'reply_to_email',
        ]);

        $data['channel'] = CommunicationTemplate::CHANNEL_EMAIL;
        $data['category'] = CommunicationTemplate::CATEGORY_TRANSACTIONAL;

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function validatedBindings(): array
    {
        return collect($this->validated('bindings', []))
            ->map(fn (array $binding): array => [
                'event_key' => $binding['event_key'],
                'action_key' => $binding['action_key'],
                'channel' => CommunicationTemplate::CHANNEL_EMAIL,
                'is_enabled' => (bool) ($binding['is_enabled'] ?? true),
                'priority' => (int) ($binding['priority'] ?? 100),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function allowedEventKeys(): array
    {
        return collect(config('communication-bindings', []))
            ->pluck('event_key')
            ->filter(fn ($eventKey): bool => is_string($eventKey) && filled($eventKey))
            ->values()
            ->all();
    }

    private function bindingActionIsAllowed(string $attribute, string $actionKey): bool
    {
        if (! preg_match('/bindings\.(\d+)\.action_key/', $attribute, $matches)) {
            return false;
        }

        $eventKey = data_get($this->input('bindings'), $matches[1].'.event_key');

        if (! is_string($eventKey) || blank($eventKey)) {
            return false;
        }

        return in_array($actionKey, $this->allowedActionKeysForEvent($eventKey), true);
    }

    /**
     * @return array<int, string>
     */
    private function allowedActionKeysForEvent(string $eventKey): array
    {
        $definition = collect(config('communication-bindings', []))
            ->firstWhere('event_key', $eventKey);

        return collect($definition['actions'] ?? [])
            ->pluck('action_key')
            ->filter(fn ($actionKey): bool => is_string($actionKey) && filled($actionKey))
            ->values()
            ->all();
    }
}
