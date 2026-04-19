<?php

namespace App\Services\Communications;

use App\Models\CommunicationSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class CommunicationSettingsRepository
{
    private const ALLOWED_KEYS = [
        'transactional_provider',
        'marketing_provider',
        'admin_notification_email',
        'admin_notification_name',
        'marketing_default_audience_key',
        'mailchimp_audiences',
        'mailchimp_tags',
        'mailchimp_triggers',
    ];

    private ?array $cache = null;

    public function allowedKeys(): array
    {
        return self::ALLOWED_KEYS;
    }

    public function defaults(): array
    {
        return [
            'transactional_provider' => config('communications.transactional_provider', 'log'),
            'marketing_provider' => config('communications.marketing.provider', 'null'),
            'admin_notification_email' => config('communications.admin_notification_email'),
            'admin_notification_name' => config('communications.admin_notification_name'),
            'marketing_default_audience_key' => config('communications.marketing.default_audience_key', 'audience.general'),
            'mailchimp_audiences' => config('communications.marketing.mailchimp.audiences', []),
            'mailchimp_tags' => config('communications.marketing.mailchimp.tags', []),
            'mailchimp_triggers' => config('communications.marketing.mailchimp.triggers', []),
        ];
    }

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! $this->settingsTableExists()) {
            return $this->cache = [];
        }

        return $this->cache = CommunicationSetting::query()
            ->whereIn('key', self::ALLOWED_KEYS)
            ->pluck('value', 'key')
            ->map(fn (mixed $value) => is_array($value) && array_key_exists('value', $value) ? $value['value'] : $value)
            ->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->guardAllowedKey($key);

        if (! $this->settingsTableExists()) {
            return;
        }

        CommunicationSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['value' => $this->normalizeValue($key, $value)]],
        );

        $this->cache = null;
    }

    public function putMany(array $settings): void
    {
        $unknownKeys = array_diff(array_keys($settings), self::ALLOWED_KEYS);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException('Unsupported communication setting keys: '.implode(', ', $unknownKeys));
        }

        foreach ($settings as $key => $value) {
            $this->put((string) $key, $value);
        }
    }

    private function guardAllowedKey(string $key): void
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            throw new InvalidArgumentException("Unsupported communication setting key [{$key}].");
        }
    }

    private function normalizeValue(string $key, mixed $value): mixed
    {
        return match ($key) {
            'transactional_provider',
            'marketing_provider',
            'marketing_default_audience_key' => $this->normalizeString($value),
            'admin_notification_email',
            'admin_notification_name' => $this->normalizeNullableString($value),
            'mailchimp_audiences',
            'mailchimp_tags' => $this->normalizeKeyValueMap($value),
            'mailchimp_triggers' => $this->normalizeTriggerMap($value),
            default => throw new InvalidArgumentException("Unsupported communication setting key [{$key}]."),
        };
    }

    private function normalizeString(mixed $value): string
    {
        return trim((string) $value);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeKeyValueMap(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->mapWithKeys(function (mixed $mappedValue, mixed $mappedKey): array {
                $key = trim((string) $mappedKey);

                if ($key === '') {
                    return [];
                }

                return [$key => trim((string) $mappedValue)];
            })
            ->all();
    }

    private function normalizeTriggerMap(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->mapWithKeys(function (mixed $mappedValue, mixed $mappedKey): array {
                $key = trim((string) $mappedKey);

                if ($key === '') {
                    return [];
                }

                $audienceKey = trim((string) Arr::get($mappedValue, 'audience_key'));
                $tags = $this->normalizeTags(Arr::get($mappedValue, 'tags', []));

                return [$key => [
                    'audience_key' => $audienceKey,
                    'tags' => $tags,
                ]];
            })
            ->all();
    }

    private function normalizeTags(mixed $value): array
    {
        $tags = $value instanceof Collection ? $value->all() : $value;

        return collect(is_array($tags) ? $tags : [])
            ->map(fn (mixed $tag) => trim((string) $tag))
            ->filter()
            ->values()
            ->all();
    }

    private function settingsTableExists(): bool
    {
        return Schema::hasTable('communication_settings');
    }
}
