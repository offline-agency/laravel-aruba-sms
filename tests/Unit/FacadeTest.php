<?php

use OfflineAgency\ArubaSms\ArubaSmsClient;
use OfflineAgency\ArubaSms\Facades\ArubaSms;

it('resolves facade to ArubaSmsClient', function () {
    expect(ArubaSms::getFacadeRoot())->toBeInstanceOf(ArubaSmsClient::class);
});

it('proxies method calls to ArubaSmsClient', function () {
    expect(ArubaSms::getBaseUrl())->toBe('https://smspanel.aruba.it/API/v1.0/REST/');
});
