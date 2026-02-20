<?php

namespace OfflineAgency\ArubaSms\Tests;

use OfflineAgency\ArubaSms\ArubaSmsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ArubaSmsServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'ArubaSms' => \OfflineAgency\ArubaSms\Facades\ArubaSms::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('aruba-sms.base_url', 'https://smspanel.aruba.it/API/v1.0/REST/');
        $app['config']->set('aruba-sms.credentials.username', 'test_user');
        $app['config']->set('aruba-sms.credentials.password', 'test_pass');
        $app['config']->set('aruba-sms.message_type', 'N');
        $app['config']->set('aruba-sms.sender', 'TestSender');
        $app['config']->set('aruba-sms.sandbox', false);
        $app['config']->set('aruba-sms.minimum_sms', 50);
        $app['config']->set('aruba-sms.low_credit_recipients', '');
    }
}
