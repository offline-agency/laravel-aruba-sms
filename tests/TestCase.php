<?php

namespace Offlineagency\LaravelArubaSms\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use OfflineAgency\LaravelArubaSms\LaravelArubaSmsFacade;
use OfflineAgency\LaravelArubaSms\LaravelArubaSmsServiceProvider;
use Orchestra\Testbench\Concerns\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function getPackageProviders(
        $app
    ): array
    {
        return [
            LaravelArubaSmsServiceProvider::class,
        ];
    }

    public function getPackageAliases(
        $app
    ): array
    {
        return [
            'LaravelArubaSms' => LaravelArubaSmsFacade::class,
        ];
    }
}
