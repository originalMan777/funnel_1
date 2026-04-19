<?php

namespace App\Http\Requests\Requests\Admin\Communications;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunicationTemplateVersionRequest extends FormRequest
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
            'subject' => [
                'required',
                'string',
                'max:255',
            ],
            'preview_text' => [
                'nullable',
                'string',
                'max:255',
            ],
            'headline' => [
                'nullable',
                'string',
                'max:255',
            ],
            'html_body' => [
                'required',
                'string',
            ],
            'text_body' => [
                'nullable',
                'string',
            ],
            'variables_schema' => [
                'nullable',
                'array',
            ],
            'sample_payload' => [
                'nullable',
                'array',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'preview_text' => 'preview text',
            'html_body' => 'HTML body',
            'text_body' => 'text body',
            'variables_schema' => 'variables schema',
            'sample_payload' => 'sample payload',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedVersionData(): array
    {
        return $this->safe()->only([
            'subject',
            'preview_text',
            'headline',
            'html_body',
            'text_body',
            'variables_schema',
            'sample_payload',
            'notes',
        ]);
    }
}
