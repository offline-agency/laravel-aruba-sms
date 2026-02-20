<?php

use OfflineAgency\ArubaSms\Support\PhoneNumberFormatter;

it('adds +39 prefix when no prefix present', function () {
    expect(PhoneNumberFormatter::format('3331234567'))->toBe('+393331234567');
});

it('preserves existing +39 prefix', function () {
    expect(PhoneNumberFormatter::format('+393331234567'))->toBe('+393331234567');
});

it('preserves other international prefixes', function () {
    expect(PhoneNumberFormatter::format('+447911123456'))->toBe('+447911123456');
});

it('strips spaces and adds prefix', function () {
    expect(PhoneNumberFormatter::format('333 123 4567'))->toBe('+393331234567');
});

it('strips spaces from number with existing prefix', function () {
    expect(PhoneNumberFormatter::format('+39 333 123 4567'))->toBe('+393331234567');
});

it('returns empty string for empty input', function () {
    expect(PhoneNumberFormatter::format(''))->toBe('');
});

it('strips spaces only via stripSpaces', function () {
    expect(PhoneNumberFormatter::stripSpaces('+39 333 123 4567'))->toBe('+393331234567');
});

it('handles number without spaces in stripSpaces', function () {
    expect(PhoneNumberFormatter::stripSpaces('+393331234567'))->toBe('+393331234567');
});

it('returns empty string for input of only spaces', function () {
    expect(PhoneNumberFormatter::format('   '))->toBe('');
});

it('handles number with leading and trailing spaces', function () {
    expect(PhoneNumberFormatter::format(' +393331234567 '))->toBe('+393331234567');
});

it('adds +39 prefix to number starting with 0039', function () {
    // 0039 is a common alternate Italian prefix format - it gets +39 prepended
    expect(PhoneNumberFormatter::format('00393331234567'))->toBe('+3900393331234567');
});
