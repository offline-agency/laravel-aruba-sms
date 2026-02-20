<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use OfflineAgency\ArubaSms\Notifications\LowCreditNotification;

it('sends email when below threshold', function () {
    Notification::fake();

    config()->set('aruba-sms.minimum_sms', 50);
    config()->set('aruba-sms.low_credit_recipients', 'admin@test.com,dev@test.com');

    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 30]],
        ]), 200),
    ]);

    $this->artisan('aruba:check-remaining-sms')
        ->expectsOutput('Email sent to admin@test.com')
        ->expectsOutput('Email sent to dev@test.com')
        ->assertExitCode(0);

    Notification::assertSentOnDemand(LowCreditNotification::class, function ($notification) {
        return $notification->getRemainingSms() === 30;
    });
});

it('does not send email when above threshold', function () {
    Notification::fake();

    config()->set('aruba-sms.minimum_sms', 50);
    config()->set('aruba-sms.low_credit_recipients', 'admin@test.com');

    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 80]],
        ]), 200),
    ]);

    $this->artisan('aruba:check-remaining-sms')
        ->assertExitCode(0);

    Notification::assertNothingSent();
    Notification::assertSentOnDemandTimes(LowCreditNotification::class, 0);
});

it('handles empty recipients config', function () {
    Notification::fake();

    config()->set('aruba-sms.minimum_sms', 50);
    config()->set('aruba-sms.low_credit_recipients', '');
    config()->set('aruba-sms.sandbox', true);

    $this->artisan('aruba:check-remaining-sms')
        ->assertExitCode(0);

    Notification::assertNothingSent();
    Notification::assertSentOnDemandTimes(LowCreditNotification::class, 0);
});

it('does not send email when exactly at threshold', function () {
    Notification::fake();

    config()->set('aruba-sms.minimum_sms', 50);
    config()->set('aruba-sms.low_credit_recipients', 'admin@test.com');

    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 50]],
        ]), 200),
    ]);

    $this->artisan('aruba:check-remaining-sms')
        ->assertExitCode(0);

    Notification::assertNothingSent();
});

it('sends email to single recipient', function () {
    Notification::fake();

    config()->set('aruba-sms.minimum_sms', 50);
    config()->set('aruba-sms.low_credit_recipients', 'admin@test.com');

    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 10]],
        ]), 200),
    ]);

    $this->artisan('aruba:check-remaining-sms')
        ->expectsOutput('Email sent to admin@test.com')
        ->assertExitCode(0);

    Notification::assertSentOnDemand(LowCreditNotification::class, function ($notification) {
        return $notification->getRemainingSms() === 10;
    });
});

it('handles API returning non-200 status gracefully', function () {
    Notification::fake();

    config()->set('aruba-sms.minimum_sms', 50);
    config()->set('aruba-sms.low_credit_recipients', 'admin@test.com');

    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response('Server Error', 500),
    ]);

    $this->artisan('aruba:check-remaining-sms')
        ->assertExitCode(0);

    Notification::assertNothingSent();
});

it('uses sandbox mode with random credits', function () {
    Notification::fake();

    config()->set('aruba-sms.sandbox', true);
    config()->set('aruba-sms.minimum_sms', 200);
    config()->set('aruba-sms.low_credit_recipients', 'admin@test.com');

    // With minimum_sms=200 and random 0-100, notification should always be sent
    $this->artisan('aruba:check-remaining-sms')
        ->assertExitCode(0);

    Notification::assertSentOnDemand(LowCreditNotification::class);
});
