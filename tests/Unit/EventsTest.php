<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use OfflineAgency\ArubaSms\ArubaSmsClient;
use OfflineAgency\ArubaSms\ArubaSmsMessage;
use OfflineAgency\ArubaSms\Events\SmsFailed;
use OfflineAgency\ArubaSms\Events\SmsSent;

it('dispatches SmsSent event on successful send', function () {
    Event::fake([SmsSent::class]);

    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('OK', 201),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');
    $client->sendMessage($message);

    Event::assertDispatched(SmsSent::class, function (SmsSent $event) {
        return $event->message->getContent() === 'Hello'
            && $event->response->status() === 201;
    });
});

it('dispatches SmsFailed event on delivery failure', function () {
    Event::fake([SmsFailed::class]);

    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('Bad Request', 400),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 10]],
        ]), 200),
    ]);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');

    try {
        $client->sendMessage($message);
    } catch (\Throwable) {
        // expected
    }

    Event::assertDispatched(SmsFailed::class, function (SmsFailed $event) {
        return $event->message->getContent() === 'Hello'
            && $event->exception instanceof \OfflineAgency\ArubaSms\Exceptions\ArubaSmsDeliveryException;
    });
});

it('does not dispatch events in sandbox mode', function () {
    Event::fake([SmsSent::class, SmsFailed::class]);
    config()->set('aruba-sms.sandbox', true);

    $client = new ArubaSmsClient;
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');
    $client->sendMessage($message);

    Event::assertNotDispatched(SmsSent::class);
    Event::assertNotDispatched(SmsFailed::class);
});

it('SmsSent event contains message and response', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $message = new ArubaSmsMessage('Test', '+393331234567', 'N');
    $response = Http::get('https://example.com');

    $event = new SmsSent($message, $response);

    expect($event->message)->toBe($message)
        ->and($event->response)->toBe($response);
});

it('SmsFailed event contains message and exception', function () {
    $message = new ArubaSmsMessage('Test', '+393331234567', 'N');
    $exception = new \RuntimeException('test error');

    $event = new SmsFailed($message, $exception);

    expect($event->message)->toBe($message)
        ->and($event->exception)->toBe($exception);
});
