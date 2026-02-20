<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OfflineAgency\ArubaSms\ArubaSmsClient;
use OfflineAgency\ArubaSms\ArubaSmsMessage;
use OfflineAgency\ArubaSms\Exceptions\ArubaSmsAuthException;
use OfflineAgency\ArubaSms\Exceptions\ArubaSmsDeliveryException;

it('reads base_url from config in constructor', function () {
    $client = new ArubaSmsClient;

    expect($client->getBaseUrl())->toBe('https://smspanel.aruba.it/API/v1.0/REST/');
});

it('parses user_key and session_key on auth success', function () {
    Http::fake([
        '*/login' => Http::response('test_user_key;test_session_key', 200),
    ]);

    $client = new ArubaSmsClient;
    $result = $client->auth();

    expect($result->user_key)->toBe('test_user_key')
        ->and($result->session_key)->toBe('test_session_key')
        ->and($client->getHeader())->toBe([
            'Content-type' => 'application/json',
            'user_key' => 'test_user_key',
            'Session_key' => 'test_session_key',
        ]);
});

it('throws ArubaSmsAuthException on auth failure', function () {
    Http::fake([
        '*/login' => Http::response('Unauthorized', 401),
    ]);

    $client = new ArubaSmsClient;
    $client->auth();
})->throws(ArubaSmsAuthException::class, 'Aruba sms authentication failed! Check username and password!');

it('logs and returns null in sandbox mode', function () {
    config()->set('aruba-sms.sandbox', true);

    Log::shouldReceive('info')->with('*** Aruba SMS DEBUG ***')->once();
    Log::shouldReceive('info')->with('Notification sent successfully!')->once();
    Log::shouldReceive('info')->with('Recipient: +393331234567')->once();
    Log::shouldReceive('info')->with('Message: Test message')->once();
    Log::shouldReceive('info')->with('Message Type: N')->once();
    Log::shouldReceive('info')->with('*** *** ***')->once();

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Test message', '+393331234567', 'N');
    $result = $client->sendMessage($message);

    expect($result)->toBeNull();
});

it('returns response on sendMessage success with 201', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('OK', 201),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');
    $response = $client->sendMessage($message);

    expect($response)->not->toBeNull()
        ->and($response->status())->toBe(201);
});

it('returns response on sendMessage success with 200', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('OK', 200),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');
    $response = $client->sendMessage($message);

    expect($response)->not->toBeNull()
        ->and($response->status())->toBe(200);
});

it('throws ArubaSmsDeliveryException on send failure with status check success', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('Bad Request', 400),
        '*/status' => Http::response(json_encode([
            'sms' => [
                ['type' => 'GP', 'quantity' => 100],
                ['type' => 'SI', 'quantity' => 50],
            ],
        ]), 200),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');
    $client->sendMessage($message);
})->throws(ArubaSmsDeliveryException::class, 'Message not sent');

it('throws ArubaSmsDeliveryException on send failure with status check failure', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('Bad Request', 400),
        '*/status' => Http::response('Server Error', 500),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');
    $client->sendMessage($message);
})->throws(ArubaSmsDeliveryException::class, 'Message not sent');

it('formats prepareData correctly', function () {
    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Test content', '+393331234567', 'N');

    $data = $client->prepareData($message);

    expect($data)->toBe([
        'message_type' => 'N',
        'message' => 'Test content',
        'recipient' => ['+393331234567'],
        'sender' => 'TestSender',
    ]);
});

it('returns response from checkSmsStatus', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 100]],
        ]), 200),
    ]);

    $client = new ArubaSmsClient;
    $response = $client->checkSmsStatus();

    expect($response->status())->toBe(200);
});

it('builds URL with all optional params for getSmsHistory', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*' => Http::response('[]', 200),
    ]);

    $client = new ArubaSmsClient;
    $response = $client->getSmsHistory('20260101000001', '20260201000001', 1, 10);

    expect($response->status())->toBe(200);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'smshistory?from=20260101000001')
            && str_contains($request->url(), '&to=20260201000001')
            && str_contains($request->url(), '&pageNumber=1')
            && str_contains($request->url(), '&pageSize=10');
    });
});

it('omits null params from getSmsHistory URL', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*' => Http::response('[]', 200),
    ]);

    $client = new ArubaSmsClient;
    $client->getSmsHistory('20260101000001');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'smshistory?from=20260101000001')
            && ! str_contains($request->url(), '&to=')
            && ! str_contains($request->url(), '&pageNumber=')
            && ! str_contains($request->url(), '&pageSize=');
    });
});

it('builds URL correctly for getSmsRecipientHistory', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*' => Http::response('[]', 200),
    ]);

    $client = new ArubaSmsClient;
    $client->getSmsRecipientHistory('+393331234567', '20260101000001', '20260201000001');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'rcptHistory?recipient=')
            && str_contains($request->url(), 'from=20260101000001')
            && str_contains($request->url(), '&to=20260201000001');
    });
});

it('returns null from getHeader before auth', function () {
    $client = new ArubaSmsClient;

    expect($client->getHeader())->toBeNull();
});

it('sets header correctly via setHeader', function () {
    $client = new ArubaSmsClient;
    $client->setHeader('my_user_key', 'my_session_key');

    expect($client->getHeader())->toBe([
        'Content-type' => 'application/json',
        'user_key' => 'my_user_key',
        'Session_key' => 'my_session_key',
    ]);
});

it('uses config-based sender in prepareData', function () {
    config()->set('aruba-sms.sender', 'CustomSender');

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Test', '+393331234567', 'N');
    $data = $client->prepareData($message);

    expect($data['sender'])->toBe('CustomSender');
});

it('omits null params from getSmsRecipientHistory URL', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*' => Http::response('[]', 200),
    ]);

    $client = new ArubaSmsClient;
    $client->getSmsRecipientHistory('+393331234567', '20260101000001');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'rcptHistory?')
            && str_contains($request->url(), 'recipient=')
            && str_contains($request->url(), 'from=20260101000001')
            && ! str_contains($request->url(), '&to=')
            && ! str_contains($request->url(), '&pageNumber=')
            && ! str_contains($request->url(), '&pageSize=');
    });
});

it('throws delivery exception with readonly properties on send failure', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('Bad Request', 400),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 100]],
        ]), 200),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');

    try {
        $client->sendMessage($message);
    } catch (ArubaSmsDeliveryException $e) {
        expect($e->httpStatus)->toBe(400)
            ->and($e->errorBody)->toBe('Bad Request')
            ->and($e->recipient)->toContain('+393331234567')
            ->and($e->messageContent)->toBe('Hello');

        return;
    }

    test()->fail('Expected ArubaSmsDeliveryException was not thrown');
});

it('handles malformed status response in sendMessage gracefully', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('Bad Request', 400),
        '*/status' => Http::response('not json', 200),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');
    $client->sendMessage($message);
})->throws(ArubaSmsDeliveryException::class);

it('caches session and does not re-authenticate on second call', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode(['sms' => []]), 200),
    ]);

    $client = new ArubaSmsClient;

    // First call authenticates
    $client->checkSmsStatus();
    // Second call uses cached session
    $client->checkSmsStatus();

    // Login should only be called once
    Http::assertSentCount(3); // 1 login + 2 status calls
});

it('re-authenticates after clearSession', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/status' => Http::response(json_encode(['sms' => []]), 200),
    ]);

    $client = new ArubaSmsClient;

    $client->checkSmsStatus(); // auth + status
    $client->clearSession();
    $client->checkSmsStatus(); // auth + status again

    // 2 login + 2 status = 4 total requests
    Http::assertSentCount(4);
});

it('clearSession resets header to null', function () {
    Http::fake(['*/login' => Http::response('uk;sk', 200)]);

    $client = new ArubaSmsClient;
    $client->auth();

    expect($client->getHeader())->not->toBeNull();

    $client->clearSession();

    expect($client->getHeader())->toBeNull();
});

it('prepareData uses getRecipients for multiple recipients', function () {
    $client = new ArubaSmsClient;
    $message = (new ArubaSmsMessage)->content('Hello')->to(['+393331111111', '+393332222222']);
    $data = $client->prepareData($message);

    expect($data['recipient'])->toBe(['+393331111111', '+393332222222']);
});

it('retries on ConnectionException', function () {
    $client = new ArubaSmsClient;
    $exception = new \Illuminate\Http\Client\ConnectionException('Connection timed out');

    expect($client->shouldRetry($exception))->toBeTrue();
});

it('retries on RequestException with server error', function () {
    $psrResponse = new \GuzzleHttp\Psr7\Response(500, [], 'Internal Server Error');
    $response = new \Illuminate\Http\Client\Response($psrResponse);
    $exception = new \Illuminate\Http\Client\RequestException($response);

    $client = new ArubaSmsClient;

    expect($client->shouldRetry($exception))->toBeTrue();
});

it('does not retry on RequestException with client error', function () {
    $psrResponse = new \GuzzleHttp\Psr7\Response(400, [], 'Bad Request');
    $response = new \Illuminate\Http\Client\Response($psrResponse);
    $exception = new \Illuminate\Http\Client\RequestException($response);

    $client = new ArubaSmsClient;

    expect($client->shouldRetry($exception))->toBeFalse();
});

it('does not retry on generic exceptions', function () {
    $client = new ArubaSmsClient;
    $exception = new \RuntimeException('Something went wrong');

    expect($client->shouldRetry($exception))->toBeFalse();
});

it('logs multiple recipients in sandbox mode', function () {
    config()->set('aruba-sms.sandbox', true);

    \Illuminate\Support\Facades\Log::shouldReceive('info')->with('*** Aruba SMS DEBUG ***')->once();
    \Illuminate\Support\Facades\Log::shouldReceive('info')->with('Notification sent successfully!')->once();
    \Illuminate\Support\Facades\Log::shouldReceive('info')->with('Recipient: +393331111111, +393332222222')->once();
    \Illuminate\Support\Facades\Log::shouldReceive('info')->with('Message: Hello')->once();
    \Illuminate\Support\Facades\Log::shouldReceive('info')->with('Message Type: N')->once();
    \Illuminate\Support\Facades\Log::shouldReceive('info')->with('*** *** ***')->once();

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', ['+393331111111', '+393332222222'], 'N');
    $result = $client->sendMessage($message);

    expect($result)->toBeNull();
});
