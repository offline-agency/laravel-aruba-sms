<?php

use OfflineAgency\ArubaSms\ArubaSmsMessage;
use OfflineAgency\ArubaSms\Notifications\SendSmsNotification;

it('returns aruba-sms channel from via', function () {
    $notification = new SendSmsNotification('Hello', '+393331234567');

    expect($notification->via(null))->toBe(['aruba-sms']);
});

it('returns ArubaSmsMessage from toArubaSms', function () {
    $notification = new SendSmsNotification('Your order is ready', '+393339876543');
    $message = $notification->toArubaSms(null);

    expect($message)->toBeInstanceOf(ArubaSmsMessage::class)
        ->and($message->getContent())->toBe('Your order is ready')
        ->and($message->getRecipient())->toBe('+393339876543')
        ->and($message->getMessageType())->toBe('N');
});

it('defaults message_type from config', function () {
    config()->set('aruba-sms.message_type', 'N');

    $notification = new SendSmsNotification('Test', '+393331234567');

    expect($notification->getMessageType())->toBe('N');
});

it('exposes public properties for legacy compatibility', function () {
    $notification = new SendSmsNotification('Test message', '+393331234567');

    expect($notification->message)->toBe('Test message')
        ->and($notification->recipient)->toBe('+393331234567')
        ->and($notification->message_type)->toBe('N');
});

it('supports getters and setters', function () {
    $notification = new SendSmsNotification('Initial', '+393331234567');

    $notification->setMessage('Updated');
    expect($notification->getMessage())->toBe('Updated');

    $notification->setRecipient('+393339999999');
    expect($notification->getRecipient())->toBe('+393339999999');
});

it('sets message type via setMessageType', function () {
    $notification = new SendSmsNotification('Test', '+393331234567');
    expect($notification->getMessageType())->toBe('N');

    $notification->setMessageType('SI');
    expect($notification->getMessageType())->toBe('SI');
});
