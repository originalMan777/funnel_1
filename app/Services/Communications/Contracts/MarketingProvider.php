<?php

namespace App\Services\Communications\Contracts;

use App\Services\Communications\DTOs\MarketingAction;
use App\Services\Communications\DTOs\MarketingActionResult;

interface MarketingProvider
{
    public function syncContact(MarketingAction $action): MarketingActionResult;

    public function addToAudience(MarketingAction $action): MarketingActionResult;

    public function applyTags(MarketingAction $action): MarketingActionResult;

    public function triggerAutomation(MarketingAction $action): MarketingActionResult;
}
