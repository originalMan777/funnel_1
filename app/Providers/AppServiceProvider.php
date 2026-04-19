<?php

namespace App\Providers;

use App\Services\Communications\CommunicationRuntimeConfig;
use App\Services\Communications\CommunicationSettingsRepository;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\Providers\LaravelPostmarkTransactionalEmailProvider;
use App\Services\Communications\Providers\LogTransactionalEmailProvider;
use App\Services\Communications\Providers\MailchimpMarketingProvider;
use App\Services\Communications\Providers\NullMarketingProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CommunicationSettingsRepository::class);
        $this->app->bind(CommunicationRuntimeConfig::class);

        $this->app->bind(TransactionalEmailProvider::class, function () {
            $runtimeConfig = app(CommunicationRuntimeConfig::class);

            return match ($runtimeConfig->transactionalProvider()) {
                'postmark' => new LaravelPostmarkTransactionalEmailProvider,
                default => new LogTransactionalEmailProvider,
            };
        });

        $this->app->bind(MarketingProvider::class, function () {
            $runtimeConfig = app(CommunicationRuntimeConfig::class);

            return match ($runtimeConfig->marketingProvider()) {
                'mailchimp' => new MailchimpMarketingProvider($runtimeConfig),
                default => new NullMarketingProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
