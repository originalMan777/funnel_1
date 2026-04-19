<?php

namespace App\Services\Communications;

class CommunicationTemplateRenderer
{
    public function render(array $content, array $variables): array
    {
        return [
            'subject' => $this->renderValue((string) ($content['subject'] ?? ''), $variables),
            'preview_text' => $this->renderNullable($content['preview_text'] ?? null, $variables),
            'headline' => $this->renderNullable($content['headline'] ?? null, $variables),
            'html_body' => $this->renderValue((string) ($content['html_body'] ?? ''), $variables),
            'text_body' => $this->renderNullable($content['text_body'] ?? null, $variables),
        ];
    }

    private function renderNullable(mixed $value, array $variables): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->renderValue((string) $value, $variables);
    }

    private function renderValue(string $value, array $variables): string
    {
        return (string) preg_replace_callback('/{{\s*([A-Za-z0-9_.-]+)\s*}}/', function (array $matches) use ($variables): string {
            $resolved = $variables[$matches[1]] ?? '';

            if (is_scalar($resolved) || $resolved === null) {
                return (string) ($resolved ?? '');
            }

            return '';
        }, $value);
    }
}
