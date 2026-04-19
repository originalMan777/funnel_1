<?php

namespace App\Http\Requests\Requests\Admin\Communications;

use Illuminate\Foundation\Http\FormRequest;

class TestSendCommunicationTemplateRequest extends FormRequest
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
            'to_email' => [
                'required',
                'email',
                'max:255',
            ],
            'to_name' => [
                'nullable',
                'string',
                'max:255',
            ],
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
    public function validatedRecipient(): array
    {
        return [
            'to_email' => $this->validated('to_email'),
            'to_name' => $this->validated('to_name'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedDraftContent(): array
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
