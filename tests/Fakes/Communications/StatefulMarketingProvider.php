<?php

namespace Tests\Fakes\Communications;

use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\DTOs\MarketingAction;
use App\Services\Communications\DTOs\MarketingActionResult;

class StatefulMarketingProvider implements MarketingProvider
{
    /**
     * @var array<string, array<int, MarketingActionResult>>
     */
    public static array $queuedResults = [];

    /**
     * @var array<int, string>
     */
    public static array $handledActionKeys = [];

    public static function reset(): void
    {
        self::$queuedResults = [];
        self::$handledActionKeys = [];
    }

    public static function queueResult(string $actionKey, MarketingActionResult $result): void
    {
        self::$queuedResults[$actionKey] ??= [];
        self::$queuedResults[$actionKey][] = $result;
    }

    public function syncContact(MarketingAction $action): MarketingActionResult
    {
        return $this->resultFor($action, 'stateful-marketing-sync');
    }

    public function addToAudience(MarketingAction $action): MarketingActionResult
    {
        return $this->resultFor($action, 'stateful-marketing-audience');
    }

    public function applyTags(MarketingAction $action): MarketingActionResult
    {
        return $this->resultFor($action, 'stateful-marketing-tags');
    }

    public function triggerAutomation(MarketingAction $action): MarketingActionResult
    {
        return $this->resultFor($action, 'stateful-marketing-trigger');
    }

    private function resultFor(MarketingAction $action, string $provider): MarketingActionResult
    {
        self::$handledActionKeys[] = $action->actionKey;

        $result = self::$queuedResults[$action->actionKey][0] ?? null;

        if ($result) {
            array_shift(self::$queuedResults[$action->actionKey]);

            return $result;
        }

        return MarketingActionResult::success($provider, $provider.'-'.$action->contact->email, [
            'audience_key' => $action->audienceKey ?: 'audience.general',
        ]);
    }
}
