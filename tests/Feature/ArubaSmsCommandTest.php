<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

it('outputs sms quantities for status subcommand', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [
                ['type' => 'GP', 'quantity' => 100],
                ['type' => 'SI', 'quantity' => 50],
            ],
        ]), 200),
    ]);

    $this->artisan('aruba:sms', ['command_type' => 'status'])
        ->expectsOutput('Status API return "200" with 100 messages of type GP and 50 messages of type SI')
        ->assertExitCode(0);
});

it('handles API error for status subcommand', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response('Server Error', 500),
    ]);

    $this->artisan('aruba:sms', ['command_type' => 'status'])
        ->assertExitCode(0);
});

it('succeeds for history subcommand', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*smshistory*' => Http::response(json_encode(['history' => []]), 200),
    ]);

    $this->artisan('aruba:sms', [
        'command_type' => 'history',
        '--from' => '20260101000001',
    ])->assertExitCode(0);
});

it('requires recipient option for recipient-history', function () {
    $this->artisan('aruba:sms', ['command_type' => 'recipient-history'])
        ->expectsOutput('Missing require parameter "recipient". Please see project doc.')
        ->assertExitCode(0);
});

it('succeeds for recipient-history with recipient', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*rcptHistory*' => Http::response(json_encode(['history' => []]), 200),
    ]);

    $this->artisan('aruba:sms', [
        'command_type' => 'recipient-history',
        '--recipient' => '+393331234567',
        '--from' => '20260101000001',
    ])->assertExitCode(0);
});

it('requires phone number for notification subcommand', function () {
    $this->artisan('aruba:sms', ['command_type' => 'notification'])
        ->expectsOutput('Missing require parameter "phoneNumber". Please see project doc.')
        ->assertExitCode(0);
});

it('sends sms in sandbox for notification subcommand', function () {
    config()->set('aruba-sms.sandbox', true);

    $this->artisan('aruba:sms', [
        'command_type' => 'notification',
        '--phoneNumber' => ['+393331234567'],
    ])->assertExitCode(0);
});

it('returns GP quantity as exit code for remaining-credit-raw', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [
                ['type' => 'GP', 'quantity' => 75],
                ['type' => 'SI', 'quantity' => 20],
            ],
        ]), 200),
    ]);

    $this->artisan('aruba:sms', ['command_type' => 'remaining-credit-raw'])
        ->assertExitCode(75);
});

it('returns random int in sandbox for remaining-credit-raw', function () {
    config()->set('aruba-sms.sandbox', true);

    // Artisan::call() returns the integer exit code directly,
    // unlike $this->artisan() which returns a PendingCommand.
    $exitCode = Artisan::call('aruba:sms', ['command_type' => 'remaining-credit-raw']);

    expect($exitCode)->toBeGreaterThanOrEqual(0)
        ->and($exitCode)->toBeLessThanOrEqual(100);
});

it('warns on invalid command type', function () {
    $this->artisan('aruba:sms', ['command_type' => 'invalid'])
        ->expectsOutput('Command type not valid')
        ->assertExitCode(0);
});

it('handles API error for history subcommand', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*smshistory*' => Http::response('Server Error', 500),
    ]);

    $this->artisan('aruba:sms', [
        'command_type' => 'history',
        '--from' => '20260101000001',
    ])->assertExitCode(0);
});

it('handles recipient-history with all optional parameters', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*rcptHistory*' => Http::response(json_encode(['history' => []]), 200),
    ]);

    $this->artisan('aruba:sms', [
        'command_type' => 'recipient-history',
        '--recipient' => '+393331234567',
        '--from' => '20260101000001',
        '--to' => '20260201000001',
        '--pageNumber' => '1',
        '--pageSize' => '10',
    ])->assertExitCode(0);
});

it('handles recipient-history API error', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*rcptHistory*' => Http::response('Server Error', 500),
    ]);

    $this->artisan('aruba:sms', [
        'command_type' => 'recipient-history',
        '--recipient' => '+393331234567',
    ])->assertExitCode(0);
});

it('returns null exit code when no GP type in remaining-credit-raw', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'SI', 'quantity' => 20]],
        ]), 200),
    ]);

    $exitCode = Artisan::call('aruba:sms', ['command_type' => 'remaining-credit-raw']);
    expect($exitCode)->toBe(0);
});

it('returns null exit code when status API fails for remaining-credit-raw', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response('Server Error', 500),
    ]);

    $exitCode = Artisan::call('aruba:sms', ['command_type' => 'remaining-credit-raw']);
    expect($exitCode)->toBe(0);
});

it('uses default from date when --from not provided for history', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*smshistory*' => Http::response(json_encode(['history' => []]), 200),
    ]);

    $this->artisan('aruba:sms', ['command_type' => 'history'])
        ->assertExitCode(0);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'smshistory?from=');
    });
});

it('outputs single status type correctly', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 100]],
        ]), 200),
    ]);

    $this->artisan('aruba:sms', ['command_type' => 'status'])
        ->expectsOutput('Status API return "200" with 100 messages of type GP')
        ->assertExitCode(0);
});
