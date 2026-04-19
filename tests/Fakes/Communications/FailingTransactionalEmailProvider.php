<?php

namespace Tests\Fakes\Communications;

use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\DTOs\TransactionalEmail;
use App\Services\Communications\DTOs\TransactionalSendResult;

class FailingTransactionalEmailProvider implements TransactionalEmailProvider
{
    public function send(TransactionalEmail $email): TransactionalSendResult
    {
        return TransactionalSendResult::failure(
            provider: 'fake-failing',
            errorMessage: 'Simulated provider failure for '.$email->actionKey,
        );
    }
}
