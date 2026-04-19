<?php

namespace Tests\Fakes\Communications;

use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\DTOs\MarketingAction;
use App\Services\Communications\DTOs\MarketingActionResult;

class FakeMarketingProvider implements MarketingProvider
{
    public function syncContact(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('fake-marketing', 'fake-sync-'.$action->contact->email, [
            'audience_key' => $action->audienceKey,
        ]);
    }

    public function addToAudience(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('fake-marketing', 'fake-audience-'.$action->contact->email, [
            'audience_key' => $action->audienceKey,
        ]);
    }

    public function applyTags(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('fake-marketing', 'fake-tags-'.$action->contact->email, [
            'audience_key' => $action->audienceKey,
        ]);
    }

    public function triggerAutomation(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('fake-marketing', 'fake-trigger-'.$action->contact->email, [
            'audience_key' => $action->audienceKey ?: 'audience.general',
        ]);
    }
}
