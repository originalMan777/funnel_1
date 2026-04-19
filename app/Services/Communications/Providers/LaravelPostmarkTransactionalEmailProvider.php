<?php

namespace App\Services\Communications\Providers;

use App\Mail\CommunicationMessageMail;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\DTOs\TransactionalEmail;
use App\Services\Communications\DTOs\TransactionalSendResult;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LaravelPostmarkTransactionalEmailProvider implements TransactionalEmailProvider
{
    public function send(TransactionalEmail $email): TransactionalSendResult
    {
        try {
            Mail::mailer(config('communications.postmark.mailer', 'postmark'))
                ->to($email->toEmail, $email->toName)
                ->send(new CommunicationMessageMail($email));

            return TransactionalSendResult::success('postmark');
        } catch (Throwable $exception) {
            report($exception);

            return TransactionalSendResult::failure('postmark', $exception->getMessage());
        }
    }
}
