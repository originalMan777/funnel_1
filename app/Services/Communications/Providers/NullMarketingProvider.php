<?php

namespace App\Services\Communications\Providers;

use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\DTOs\MarketingAction;
use App\Services\Communications\DTOs\MarketingActionResult;

class NullMarketingProvider implements MarketingProvider
{
    public function syncContact(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('null');
    }

    public function addToAudience(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('null');
    }

    public function applyTags(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('null');
    }

    public function triggerAutomation(MarketingAction $action): MarketingActionResult
    {
        return MarketingActionResult::success('null');
    }
}
