<?php

namespace App\Http\Requests\Requests\Admin\Communications;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCommunicationTemplateRequest extends FormRequest
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
            'sample_payload' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedPreviewContent(): array
    {
        return $this->safe()->only([
            'subject',
            'preview_text',
            'headline',
            'html_body',
            'text_body',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedSamplePayload(): array
    {
        return $this->validated('sample_payload', []);
    }
}
