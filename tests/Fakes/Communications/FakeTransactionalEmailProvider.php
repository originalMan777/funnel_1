<?php

namespace Tests\Fakes\Communications;

use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\DTOs\TransactionalEmail;
use App\Services\Communications\DTOs\TransactionalSendResult;

class FakeTransactionalEmailProvider implements TransactionalEmailProvider
{
    public function send(TransactionalEmail $email): TransactionalSendResult
    {
        return TransactionalSendResult::success(
            provider: 'fake',
            providerMessageId: 'fake-'.$email->actionKey,
        );
    }
}
