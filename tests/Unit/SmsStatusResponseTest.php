<?php

use Illuminate\Support\Facades\Http;
use OfflineAgency\ArubaSms\Support\SmsStatusResponse;

it('parses valid response with multiple types', function () {
    Http::fake([
        '*' => Http::response(json_encode([
            'sms' => [
                ['type' => 'GP', 'quantity' => 100],
                ['type' => 'SI', 'quantity' => 50],
            ],
        ]), 200),
    ]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto)->not->toBeNull()
        ->and($dto->getTypes())->toHaveCount(2)
        ->and($dto->getGpCredits())->toBe(100);
});

it('returns null for non-200 response', function () {
    Http::fake(['*' => Http::response('Server Error', 500)]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto)->toBeNull();
});

it('returns null for malformed JSON response', function () {
    Http::fake(['*' => Http::response('not json', 200)]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto)->toBeNull();
});

it('returns null for response missing sms key', function () {
    Http::fake(['*' => Http::response(json_encode(['other' => 'data']), 200)]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto)->toBeNull();
});

it('returns null GP credits when no GP type exists', function () {
    Http::fake([
        '*' => Http::response(json_encode([
            'sms' => [['type' => 'SI', 'quantity' => 50]],
        ]), 200),
    ]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto)->not->toBeNull()
        ->and($dto->getGpCredits())->toBeNull();
});

it('formats summary with single type', function () {
    Http::fake([
        '*' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 100]],
        ]), 200),
    ]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto->getFormattedSummary())->toBe('100 messages of type GP');
});

it('formats summary with multiple types joined by and', function () {
    Http::fake([
        '*' => Http::response(json_encode([
            'sms' => [
                ['type' => 'GP', 'quantity' => 100],
                ['type' => 'SI', 'quantity' => 50],
            ],
        ]), 200),
    ]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto->getFormattedSummary())->toBe('100 messages of type GP and 50 messages of type SI');
});

it('returns empty summary for empty sms array', function () {
    Http::fake([
        '*' => Http::response(json_encode(['sms' => []]), 200),
    ]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto)->not->toBeNull()
        ->and($dto->getFormattedSummary())->toBe('')
        ->and($dto->getGpCredits())->toBeNull()
        ->and($dto->getTypes())->toBeEmpty();
});

it('defaults missing quantity to 0 and missing type to unknown', function () {
    Http::fake([
        '*' => Http::response(json_encode([
            'sms' => [['other_field' => 'value']],
        ]), 200),
    ]);

    $response = Http::get('https://example.com/status');
    $dto = SmsStatusResponse::fromResponse($response);

    expect($dto->getTypes())->toBe([['type' => 'unknown', 'quantity' => 0]])
        ->and($dto->getFormattedSummary())->toBe('0 messages of type unknown');
});
