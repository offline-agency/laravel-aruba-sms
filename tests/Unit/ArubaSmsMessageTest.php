<?php

use OfflineAgency\ArubaSms\ArubaSmsMessage;

it('sets all properties via constructor', function () {
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');

    expect($message->getContent())->toBe('Hello')
        ->and($message->getRecipient())->toBe('+393331234567')
        ->and($message->getMessageType())->toBe('N');
});

it('defaults message_type from config', function () {
    config()->set('aruba-sms.message_type', 'N');

    $message = new ArubaSmsMessage('Hello', '+393331234567');

    expect($message->getMessageType())->toBe('N');
});

it('defaults to empty strings', function () {
    $message = new ArubaSmsMessage;

    expect($message->getContent())->toBe('')
        ->and($message->getRecipient())->toBe('');
});

it('returns self from fluent setters', function () {
    $message = new ArubaSmsMessage;

    expect($message->content('test'))->toBe($message)
        ->and($message->to('+39333'))->toBe($message)
        ->and($message->messageType('N'))->toBe($message);
});

it('builds message via fluent chain', function () {
    $message = (new ArubaSmsMessage)
        ->content('Your code is 123456')
        ->to('+393331234567')
        ->messageType('N');

    expect($message->getContent())->toBe('Your code is 123456')
        ->and($message->getRecipient())->toBe('+393331234567')
        ->and($message->getMessageType())->toBe('N');
});

it('fluent chain overrides constructor values', function () {
    $message = new ArubaSmsMessage('Original', '+390000000000', 'N');

    $message->content('Updated')
        ->to('+391111111111')
        ->messageType('SI');

    expect($message->getContent())->toBe('Updated')
        ->and($message->getRecipient())->toBe('+391111111111')
        ->and($message->getMessageType())->toBe('SI');
});

it('constructor with empty string message_type falls back to config', function () {
    config()->set('aruba-sms.message_type', 'GP');

    $message = new ArubaSmsMessage('Test', '+39333', null);

    expect($message->getMessageType())->toBe('GP');
});

it('accepts array of recipients via constructor', function () {
    $message = new ArubaSmsMessage('Hello', ['+393331111111', '+393332222222'], 'N');

    expect($message->getRecipient())->toBe('+393331111111')
        ->and($message->getRecipients())->toBe(['+393331111111', '+393332222222']);
});

it('accepts array of recipients via to fluent method', function () {
    $message = (new ArubaSmsMessage)
        ->content('Hello')
        ->to(['+393331111111', '+393332222222']);

    expect($message->getRecipient())->toBe('+393331111111')
        ->and($message->getRecipients())->toBe(['+393331111111', '+393332222222']);
});

it('to with string clears multi-recipient state', function () {
    $message = (new ArubaSmsMessage)
        ->to(['+393331111111', '+393332222222'])
        ->to('+393339999999');

    expect($message->getRecipient())->toBe('+393339999999')
        ->and($message->getRecipients())->toBe(['+393339999999']);
});

it('getRecipients returns single recipient as array', function () {
    $message = new ArubaSmsMessage('Hello', '+393331234567', 'N');

    expect($message->getRecipients())->toBe(['+393331234567']);
});

it('getRecipients returns empty array for empty recipient', function () {
    $message = new ArubaSmsMessage;

    expect($message->getRecipients())->toBe([]);
});

it('constructor with empty array recipients', function () {
    $message = new ArubaSmsMessage('Hello', [], 'N');

    expect($message->getRecipient())->toBe('')
        ->and($message->getRecipients())->toBe([]);
});
