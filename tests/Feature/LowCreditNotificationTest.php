<?php

use OfflineAgency\ArubaSms\Notifications\LowCreditNotification;

it('returns mail channel from via', function () {
    $notification = new LowCreditNotification(30);

    expect($notification->via(null))->toBe(['mail']);
});

it('generates correct email with subject and remaining count', function () {
    $notification = new LowCreditNotification(25);
    $mail = $notification->toMail(null);

    expect($mail->subject)->toBe('SMS credits running low');

    $introLines = collect($mail->introLines);
    expect($introLines->contains('Your SMS credits are about to run out.'))->toBeTrue()
        ->and($introLines->contains('You currently have 25 SMS credits remaining.'))->toBeTrue();
});

it('returns remaining sms count from getter', function () {
    $notification = new LowCreditNotification(42);

    expect($notification->getRemainingSms())->toBe(42);
});

it('handles zero remaining sms', function () {
    $notification = new LowCreditNotification(0);
    $mail = $notification->toMail(null);

    $introLines = collect($mail->introLines);
    expect($introLines->contains('You currently have 0 SMS credits remaining.'))->toBeTrue()
        ->and($notification->getRemainingSms())->toBe(0);
});

it('includes all three message lines', function () {
    $notification = new LowCreditNotification(15);
    $mail = $notification->toMail(null);

    $introLines = collect($mail->introLines);
    expect($introLines)->toHaveCount(3)
        ->and($introLines[0])->toBe('Your SMS credits are about to run out.')
        ->and($introLines[1])->toContain('recharge')
        ->and($introLines[2])->toContain('15 SMS credits');
});

it('uses Italian translations when locale is set to it', function () {
    app()->setLocale('it');

    $notification = new LowCreditNotification(10);
    $mail = $notification->toMail(null);

    expect($mail->subject)->toBe('Sms quasi finiti');

    $introLines = collect($mail->introLines);
    expect($introLines[0])->toBe('Stai per terminare i crediti sms.')
        ->and($introLines[2])->toContain('10 sms');
});
