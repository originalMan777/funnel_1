<?php

namespace App\Services\Communications\Providers;

use App\Services\Communications\CommunicationRuntimeConfig;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\DTOs\MarketingAction;
use App\Services\Communications\DTOs\MarketingActionResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MailchimpMarketingProvider implements MarketingProvider
{
    public function __construct(
        private readonly CommunicationRuntimeConfig $runtimeConfig,
    ) {}

    public function syncContact(MarketingAction $action): MarketingActionResult
    {
        $audienceKey = $action->audienceKey ?: $this->runtimeConfig->defaultMarketingAudienceKey();

        if (blank($audienceKey)) {
            return MarketingActionResult::failure('mailchimp', 'No Mailchimp audience key is configured for contact sync.');
        }

        return $this->upsertAudienceMember($action, (string) $audienceKey);
    }

    public function addToAudience(MarketingAction $action): MarketingActionResult
    {
        if (blank($action->audienceKey)) {
            return MarketingActionResult::failure('mailchimp', 'No audience key was provided for Mailchimp audience sync.');
        }

        return $this->upsertAudienceMember($action, (string) $action->audienceKey);
    }

    public function applyTags(MarketingAction $action): MarketingActionResult
    {
        if (blank($action->audienceKey)) {
            return MarketingActionResult::failure('mailchimp', 'No audience key was provided for Mailchimp tag sync.');
        }

        $listId = $this->resolveAudienceId((string) $action->audienceKey);

        if ($listId === null) {
            return MarketingActionResult::failure('mailchimp', "No Mailchimp audience mapping exists for [{$action->audienceKey}].");
        }

        $tags = collect($action->tagKeys)
            ->map(fn (string $tagKey): array => [
                'name' => $this->resolveTagName($tagKey),
                'status' => 'active',
            ])
            ->values()
            ->all();

        if ($tags === []) {
            return MarketingActionResult::success('mailchimp', metadata: [
                'audience_key' => $action->audienceKey,
            ]);
        }

        try {
            $response = $this->client()->post(sprintf(
                'lists/%s/members/%s/tags',
                $listId,
                $this->subscriberHash($action->contact->email),
            ), [
                'tags' => $tags,
            ]);

            if ($response->failed()) {
                return MarketingActionResult::failure('mailchimp', $this->extractErrorMessage($response->json()), [
                    'audience_key' => $action->audienceKey,
                ]);
            }

            return MarketingActionResult::success('mailchimp', metadata: [
                'audience_key' => $action->audienceKey,
                'tags' => $tags,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return MarketingActionResult::failure('mailchimp', $exception->getMessage(), [
                'audience_key' => $action->audienceKey,
            ]);
        }
    }

    public function triggerAutomation(MarketingAction $action): MarketingActionResult
    {
        $mapping = $this->triggerMappings()[(string) $action->triggerKey] ?? [];
        $audienceKey = Arr::get($mapping, 'audience_key');
        $tagKeys = collect(Arr::wrap(Arr::get($mapping, 'tags')))
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        if (blank($audienceKey) || $tagKeys === []) {
            return MarketingActionResult::failure(
                'mailchimp',
                "No Mailchimp trigger mapping exists for [{$action->triggerKey}]."
            );
        }

        return $this->applyTags(new MarketingAction(
            type: MarketingAction::TYPE_APPLY_TAGS,
            actionKey: $action->actionKey,
            contact: $action->contact,
            audienceKey: (string) $audienceKey,
            tagKeys: $tagKeys,
            triggerKey: $action->triggerKey,
            payload: $action->payload,
        ));
    }

    private function upsertAudienceMember(MarketingAction $action, string $audienceKey): MarketingActionResult
    {
        $listId = $this->resolveAudienceId($audienceKey);

        if ($listId === null) {
            return MarketingActionResult::failure('mailchimp', "No Mailchimp audience mapping exists for [{$audienceKey}].");
        }

        try {
            $response = $this->client()->put(sprintf(
                'lists/%s/members/%s',
                $listId,
                $this->subscriberHash($action->contact->email),
            ), [
                'email_address' => $action->contact->email,
                'status_if_new' => 'subscribed',
                'status' => 'subscribed',
                'merge_fields' => array_filter([
                    'FNAME' => $action->contact->name,
                    'PHONE' => $action->contact->phone,
                ], fn (mixed $value): bool => filled($value)),
            ]);

            if ($response->failed()) {
                return MarketingActionResult::failure('mailchimp', $this->extractErrorMessage($response->json()), [
                    'audience_key' => $audienceKey,
                ]);
            }

            $body = (array) $response->json();

            return MarketingActionResult::success(
                provider: 'mailchimp',
                externalContactId: Arr::get($body, 'id') ?: Arr::get($body, 'contact_id') ?: $this->subscriberHash($action->contact->email),
                metadata: [
                    'audience_key' => $audienceKey,
                    'list_id' => $listId,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            return MarketingActionResult::failure('mailchimp', $exception->getMessage(), [
                'audience_key' => $audienceKey,
            ]);
        }
    }

    private function client(): PendingRequest
    {
        $apiKey = (string) config('services.mailchimp.api_key');
        $serverPrefix = (string) config('services.mailchimp.server_prefix');

        if ($serverPrefix === '' && str_contains($apiKey, '-')) {
            $serverPrefix = (string) Str::afterLast($apiKey, '-');
        }

        return Http::baseUrl(sprintf('https://%s.api.mailchimp.com/3.0/', $serverPrefix))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.mailchimp.timeout', 10))
            ->withBasicAuth('anystring', $apiKey);
    }

    private function resolveAudienceId(string $audienceKey): ?string
    {
        $audienceId = $this->audienceMappings()[$audienceKey] ?? null;

        return filled($audienceId) ? (string) $audienceId : null;
    }

    private function resolveTagName(string $tagKey): string
    {
        $mappedTag = $this->tagMappings()[$tagKey] ?? null;

        if (filled($mappedTag)) {
            return (string) $mappedTag;
        }

        return Str::of($tagKey)
            ->replace('.', '_')
            ->replace(':', '_')
            ->limit(100, '')
            ->toString();
    }

    private function subscriberHash(string $email): string
    {
        return md5(Str::lower(trim($email)));
    }

    /**
     * @return array<string, string|null>
     */
    private function audienceMappings(): array
    {
        return $this->runtimeConfig->mailchimpAudiences();
    }

    /**
     * @return array<string, string|null>
     */
    private function tagMappings(): array
    {
        return $this->runtimeConfig->mailchimpTags();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function triggerMappings(): array
    {
        return $this->runtimeConfig->mailchimpTriggers();
    }

    /**
     * @param  array<string, mixed>  $responseBody
     */
    private function extractErrorMessage(array $responseBody): string
    {
        return (string) ($responseBody['detail'] ?? $responseBody['title'] ?? 'Mailchimp request failed.');
    }
}
