<?php

namespace App\Http\Requests\Requests\Admin\Communications;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCommunicationComposerRequest extends FormRequest
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
            'message' => [
                'required',
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
    public function validatedDraftContent(): array
    {
        return $this->safe()->only([
            'subject',
            'preview_text',
            'headline',
            'message',
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
