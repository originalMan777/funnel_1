<?php

namespace App\Services\Communications\Contracts;

use App\Services\Communications\DTOs\TransactionalEmail;
use App\Services\Communications\DTOs\TransactionalSendResult;

interface TransactionalEmailProvider
{
    public function send(TransactionalEmail $email): TransactionalSendResult;
}
