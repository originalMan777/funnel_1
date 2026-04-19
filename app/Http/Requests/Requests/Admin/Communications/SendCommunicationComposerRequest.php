<?php

namespace App\Http\Requests\Requests\Admin\Communications;

class SendCommunicationComposerRequest extends PreviewCommunicationComposerRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
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
            'from_email' => [
                'required',
                'email',
                'max:255',
            ],
            'from_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);
    }

    /**
     * @return array{to_email: string, to_name: ?string}
     */
    public function validatedRecipient(): array
    {
        return [
            'to_email' => $this->validated('to_email'),
            'to_name' => $this->validated('to_name'),
        ];
    }

    /**
     * @return array{from_email: string, from_name: ?string}
     */
    public function validatedSender(): array
    {
        return [
            'from_email' => $this->validated('from_email'),
            'from_name' => $this->validated('from_name'),
        ];
    }
}
