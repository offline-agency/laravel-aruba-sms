# Changelog

All notable changes to `laravel-aruba-sms` will be documented in this file.

## 2.0.0 - 2026-02-19

### Breaking Changes
- Minimum PHP version raised to ^8.4
- Minimum Laravel version raised to ^12.0
- `ArubaSmsDeliveryException` message format changed: `STATUS:` labels renamed to `HTTP_STATUS:` / `SMS_STATUS:`
- `ArubaSmsDeliveryException` constructor signature changed to readonly promoted properties
- `CheckRemainingSmsCommand` no longer uses artisan exit codes; calls `ArubaSmsClient` directly

### Added
- Session caching in `ArubaSmsClient` to avoid re-authentication per request
- `SmsStatusResponse` DTO for structured SMS status data
- Retry on transient HTTP failures (5xx / timeouts)
- `DeferrableProvider` for lazy service registration
- `SmsSent` and `SmsFailed` events for observability
- Translatable `LowCreditNotification` with i18n support
- Multiple recipients per message support
- `ArubaSms` facade with full PHPDoc
- `.env.example` file
- Laravel Pint code style enforcement
- PHPStan level 6 static analysis
- CI pipeline with tests, PHPStan, Pint, and security audit
- 94+ tests with 100% code coverage target

### Fixed
- `SendSmsNotification::setMessageType()` now accepts a parameter (was ignoring input)
- Loose comparison `==` → `===` in `ArubaSmsCommand`
- Typo "sended" → "sent" in `ArubaSmsDeliveryException`
- Duplicate "STATUS:" labels disambiguated to "HTTP_STATUS:" / "SMS_STATUS:"
- URL building in `ArubaSmsClient` uses `http_build_query()` instead of string concatenation
- Sandbox debug log asymmetric asterisks fixed
- Defensive JSON decoding with `$response->json()` + null-checks
- `checkArubaSmsHistory()` / `checkArubaSmsStatus()` return types changed to `void`

### Changed
- `switch` → `match` expression in `ArubaSmsCommand`
- Constructor property promotion across all classes
- Carbon 3 compatible `getFromDate()` method
- `LowCreditNotification` uses readonly promoted property

## 1.1.0 - 2026-02-19

- Added `PhoneNumberFormatter` utility for Italian phone number formatting
- Switched test framework from PHPUnit to Pest
- Added PHPStan level 5 static analysis with Larastan
- Expanded test coverage (~55 tests)

## 1.0.0 - 2026-02-19

- Initial release
- Aruba SMS Panel API client (auth, send, status, history)
- Laravel notification channel (`aruba-sms`) with backward compatibility
- Fluent message builder (`ArubaSmsMessage`)
- Artisan commands (`aruba:sms`, `aruba:check-remaining-sms`)
- Generic `SendSmsNotification` and `LowCreditNotification`
- Sandbox mode for non-production environments
- Typed exceptions (`ArubaSmsAuthException`, `ArubaSmsDeliveryException`)
