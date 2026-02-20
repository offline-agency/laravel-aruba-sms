<?php

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use OfflineAgency\ArubaSms\ArubaSmsChannel;
use OfflineAgency\ArubaSms\ArubaSmsClient;
use OfflineAgency\ArubaSms\ArubaSmsMessage;

it('calls toArubaSms when method exists', function () {
    config()->set('aruba-sms.sandbox', true);

    $notification = new class extends Notification
    {
        public function toArubaSms($notifiable): ArubaSmsMessage
        {
            return new ArubaSmsMessage('Hello from toArubaSms', '+393331234567', 'N');
        }

        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);

    $result = $channel->send(null, $notification);
    expect($result)->toBeNull();
});

it('falls back to public properties for legacy notifications', function () {
    config()->set('aruba-sms.sandbox', true);

    $notification = new class extends Notification
    {
        public string $message = 'Legacy message';

        public string $recipient = '+393339876543';

        public string $message_type = 'N';

        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);

    $result = $channel->send(null, $notification);
    expect($result)->toBeNull();
});

it('returns null when toArubaSms returns non-message', function () {
    $notification = new class extends Notification
    {
        public function toArubaSms($notifiable): ?string
        {
            return null;
        }

        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);

    $result = $channel->send(null, $notification);
    expect($result)->toBeNull();
});

it('calls API in production mode via toArubaSms', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('OK', 201),
    ]);

    $notification = new class extends Notification
    {
        public function toArubaSms($notifiable): ArubaSmsMessage
        {
            return new ArubaSmsMessage('Production test', '+393331234567', 'N');
        }

        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);

    $response = $channel->send(null, $notification);

    expect($response->status())->toBe(201);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/sms');
    });
});

it('uses empty strings for missing legacy properties', function () {
    config()->set('aruba-sms.sandbox', true);

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);

    $result = $channel->send(null, $notification);
    expect($result)->toBeNull();
});

it('returns null when toArubaSms returns a string', function () {
    $notification = new class extends Notification
    {
        public function toArubaSms($notifiable): string
        {
            return 'not a message object';
        }

        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);

    $result = $channel->send(null, $notification);
    expect($result)->toBeNull();
});

it('propagates delivery exception through channel', function () {
    Http::fake([
        '*/login' => Http::response('uk;sk', 200),
        '*/sms' => Http::response('Bad Request', 400),
        '*/status' => Http::response(json_encode([
            'sms' => [['type' => 'GP', 'quantity' => 10]],
        ]), 200),
    ]);

    $notification = new class extends Notification
    {
        public function toArubaSms($notifiable): ArubaSmsMessage
        {
            return new ArubaSmsMessage('Test', '+393331234567', 'N');
        }

        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);
    $channel->send(null, $notification);
})->throws(\OfflineAgency\ArubaSms\Exceptions\ArubaSmsDeliveryException::class);

it('passes notifiable to toArubaSms method', function () {
    config()->set('aruba-sms.sandbox', true);

    $notification = new class extends Notification
    {
        public $receivedNotifiable = null;

        public function toArubaSms($notifiable): ArubaSmsMessage
        {
            $this->receivedNotifiable = $notifiable;

            return new ArubaSmsMessage('Test', '+393331234567', 'N');
        }

        public function via($notifiable): array
        {
            return ['aruba-sms'];
        }
    };

    $client = app(ArubaSmsClient::class);
    $channel = new ArubaSmsChannel($client);

    $channel->send('test-notifiable', $notification);
    expect($notification->receivedNotifiable)->toBe('test-notifiable');
});
