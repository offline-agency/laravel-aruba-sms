<?php

use OfflineAgency\ArubaSms\ArubaSmsClient;

it('merges config with default values', function () {
    expect(config('aruba-sms.base_url'))->not->toBeNull()
        ->and(config('aruba-sms.credentials.username'))->not->toBeNull()
        ->and(config('aruba-sms.message_type'))->not->toBeNull();
});

it('resolves ArubaSmsClient as singleton', function () {
    $client1 = app(ArubaSmsClient::class);
    $client2 = app(ArubaSmsClient::class);

    expect($client1)->toBeInstanceOf(ArubaSmsClient::class)
        ->and($client1)->toBe($client2);
});

it('resolves facade accessor', function () {
    $client = app('aruba-sms-client');

    expect($client)->toBeInstanceOf(ArubaSmsClient::class);
});

it('registers artisan commands', function () {
    $commands = Illuminate\Support\Facades\Artisan::all();

    expect($commands)->toHaveKey('aruba:sms')
        ->and($commands)->toHaveKey('aruba:check-remaining-sms');
});

it('registers aruba-sms notification channel', function () {
    // The channel is registered via Notification::extend in the service provider.
    // We verify by sending a notification through the channel in sandbox mode.
    config()->set('aruba-sms.sandbox', true);

    $notification = new OfflineAgency\ArubaSms\Notifications\SendSmsNotification('Test', '+393331234567');

    // If the channel wasn't registered, this would throw
    Illuminate\Support\Facades\Notification::route('aruba-sms', null)
        ->notify($notification);

    expect(true)->toBeTrue();
});

it('resolves singleton alias and class to same instance', function () {
    $byClass = app(ArubaSmsClient::class);
    $byAlias = app('aruba-sms-client');

    expect($byClass)->toBe($byAlias);
});

it('loads translation files', function () {
    expect(__('aruba-sms::notifications.low_credit.subject'))->toBe('SMS credits running low');
});

it('merges retry config defaults', function () {
    expect(config('aruba-sms.retry.times'))->toBe(3)
        ->and(config('aruba-sms.retry.sleep_ms'))->toBe(100);
});
