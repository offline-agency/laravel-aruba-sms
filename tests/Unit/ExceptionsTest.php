<?php

use OfflineAgency\ArubaSms\Exceptions\ArubaSmsAuthException;
use OfflineAgency\ArubaSms\Exceptions\ArubaSmsDeliveryException;

it('has default message for ArubaSmsAuthException', function () {
    $exception = new ArubaSmsAuthException;

    expect($exception->getMessage())->toBe('Aruba sms authentication failed! Check username and password!');
});

it('accepts custom message for ArubaSmsAuthException', function () {
    $exception = new ArubaSmsAuthException('Custom error');

    expect($exception->getMessage())->toBe('Custom error');
});

it('formats delivery exception message with structured labels', function () {
    $exception = new ArubaSmsDeliveryException(
        400,
        'Bad Request',
        '["+393331234567"]',
        'Hello world',
        'Status API return "400" with 100 messages of type GP'
    );

    expect($exception->getMessage())
        ->toContain('Message not sent')
        ->toContain('HTTP_STATUS: 400')
        ->toContain('ERROR MESSAGE: Bad Request')
        ->toContain('RECIPIENT: ["+393331234567"]')
        ->toContain('CONTENT: Hello world')
        ->toContain('SMS_STATUS: Status API return "400"');
});

it('extends base Exception class', function () {
    expect(new ArubaSmsAuthException)->toBeInstanceOf(Exception::class)
        ->and(new ArubaSmsDeliveryException(400, '', '', '', ''))->toBeInstanceOf(Exception::class);
});

it('handles empty string parameters in delivery exception', function () {
    $exception = new ArubaSmsDeliveryException(500, '', '', '', '');

    expect($exception->getMessage())->toContain('Message not sent')
        ->and($exception->httpStatus)->toBe(500)
        ->and($exception->errorBody)->toBe('')
        ->and($exception->recipient)->toBe('')
        ->and($exception->messageContent)->toBe('')
        ->and($exception->statusInfo)->toBe('');
});

it('exposes readonly properties on delivery exception', function () {
    $exception = new ArubaSmsDeliveryException(
        403,
        'Forbidden',
        '+393331234567',
        'Test content',
        'Status info'
    );

    expect($exception->httpStatus)->toBe(403)
        ->and($exception->errorBody)->toBe('Forbidden')
        ->and($exception->recipient)->toBe('+393331234567')
        ->and($exception->messageContent)->toBe('Test content')
        ->and($exception->statusInfo)->toBe('Status info');
});
