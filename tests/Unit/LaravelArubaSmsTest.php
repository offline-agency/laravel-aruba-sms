<?php

namespace Offlineagency\LaravelArubaSms\Tests\Unit;

use Mockery;
use OfflineAgency\LaravelArubaSms\LaravelArubaSms;
use Offlineagency\LaravelArubaSms\Tests\TestCase;

class LaravelWebexFacadeTest extends TestCase
{
    /**
     * @test
     */
    public function it_loads_facade_alias()
    {
        $this->app->singleton(
            'laravel-aruba-sms',
            function ($app) {
                return Mockery::mock(LaravelArubaSms::class, function ($mock) {
                    $mock->shouldReceive('test');
                });
            });

        \LaravelArubaSms::test();
    }
}
