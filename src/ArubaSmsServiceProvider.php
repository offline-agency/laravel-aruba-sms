<?php

namespace OfflineAgency\ArubaSms;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use OfflineAgency\ArubaSms\Commands\ArubaSmsCommand;
use OfflineAgency\ArubaSms\Commands\CheckRemainingSmsCommand;

class ArubaSmsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/aruba-sms.php' => config_path('aruba-sms.php'),
        ], 'aruba-sms-config');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'aruba-sms');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/aruba-sms'),
        ], 'aruba-sms-lang');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ArubaSmsCommand::class,
                CheckRemainingSmsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/aruba-sms.php',
            'aruba-sms'
        );

        $this->app->singleton(ArubaSmsClient::class, function ($app) {
            return new ArubaSmsClient;
        });

        $this->app->alias(ArubaSmsClient::class, 'aruba-sms-client');

        Notification::extend('aruba-sms', function ($app) {
            return new ArubaSmsChannel(
                $app->make(ArubaSmsClient::class)
            );
        });
    }
}
