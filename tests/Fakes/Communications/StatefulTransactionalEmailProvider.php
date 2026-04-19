<?php

namespace Tests\Fakes\Communications;

use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\DTOs\TransactionalEmail;
use App\Services\Communications\DTOs\TransactionalSendResult;

class StatefulTransactionalEmailProvider implements TransactionalEmailProvider
{
    /**
     * @var array<string, array<int, TransactionalSendResult>>
     */
    public static array $queuedResults = [];

    /**
     * @var array<int, string>
     */
    public static array $sentRecipients = [];

    /**
     * @var array<int, string>
     */
    public static array $sentActionKeys = [];

    public static function reset(): void
    {
        self::$queuedResults = [];
        self::$sentRecipients = [];
        self::$sentActionKeys = [];
    }

    public static function queueResult(string $actionKey, TransactionalSendResult $result): void
    {
        self::$queuedResults[$actionKey] ??= [];
        self::$queuedResults[$actionKey][] = $result;
    }

    public function send(TransactionalEmail $email): TransactionalSendResult
    {
        self::$sentRecipients[] = $email->toEmail;
        self::$sentActionKeys[] = $email->actionKey;

        $result = self::$queuedResults[$email->actionKey][0] ?? null;

        if ($result) {
            array_shift(self::$queuedResults[$email->actionKey]);

            return $result;
        }

        return TransactionalSendResult::success(
            provider: 'stateful',
            providerMessageId: 'stateful-'.$email->actionKey,
        );
    }
}
