# Integration Guide

Step-by-step instructions for migrating the TakeAtHome main app to use this package instead of the inline Aruba SMS code. **Do NOT apply these changes automatically** — this is a reference for manual integration.

## Step 1: Install the Package

Add the path repository to the main project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/offline-agency/laravel-aruba-sms",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

Then:

```bash
composer require offline-agency/laravel-aruba-sms
```

## Step 2: Publish Config

```bash
php artisan vendor:publish --tag=aruba-sms-config
```

## Step 3: Update `.env`

Add these new variables:

```env
ARUBA_SMS_SANDBOX=true          # Set to false in production
ARUBA_SMS_SENDER=Takeathome
ARUBA_SMS_LOW_CREDIT_RECIPIENTS=dev@takeathome.it,info@gianduja.agency
```

Existing variables (`ARUBA_SMS_ID`, `ARUBA_SMS_PASSWORD`, `MINIMUM_SMS`) continue to work unchanged.

## Step 4: Delete Replaced Files

Remove these files from the main project:

| File to Delete | Replaced by Package |
|---------------|-------------------|
| `app/Services/ArubaSms.php` | `ArubaSmsClient` |
| `app/Channels/ArubaSms.php` | `ArubaSmsChannel` |
| `app/Notifications/SendSmsNotification.php` | `Notifications\SendSmsNotification` |
| `app/Notifications/MailForMinimumSmsNotification.php` | `Notifications\LowCreditNotification` |
| `app/Console/Commands/ArubaSmsCommand.php` | `Commands\ArubaSmsCommand` |
| `app/Console/Commands/CheckArubaSms.php` | `Commands\CheckRemainingSmsCommand` |
| `config/aruba.php` | `config/aruba-sms.php` |

## Step 5: Remove Channel Registration from AppServiceProvider

In `app/Providers/AppServiceProvider.php`, remove:

```php
// Remove this import:
use App\Channels\ArubaSms;

// Remove these lines from boot():
Notification::extend('aruba-sms', function ($app) {
    return new ArubaSms();
});
```

The package's service provider automatically registers the `aruba-sms` channel.

## Step 6: Update Import Paths

### `app/Listeners/SendOrderCreatedNotification.php`

```diff
- use App\Notifications\SendSmsNotification;
+ use OfflineAgency\ArubaSms\Notifications\SendSmsNotification;
```

### `app/Console/Commands/ArubaSmsCommand.php` references (if any other file imports it)

```diff
- use App\Console\Commands\ArubaSmsCommand;
+ // No longer needed — package registers the command automatically
```

## Step 7: Update Config References

### `app/Notifications/OtpNotification.php` (line 129)

```diff
- $this->message_type = config('aruba.message_type');
+ $this->message_type = config('aruba-sms.message_type');
```

### Any other file referencing `config('aruba.*)`

Search for `config('aruba.` and replace with `config('aruba-sms.`:

| Old Key | New Key |
|---------|---------|
| `config('aruba.base_url')` | `config('aruba-sms.base_url')` |
| `config('aruba.sms_credentials.username')` | `config('aruba-sms.credentials.username')` |
| `config('aruba.sms_credentials.password')` | `config('aruba-sms.credentials.password')` |
| `config('aruba.message_type')` | `config('aruba-sms.message_type')` |
| `config('aruba.minimum_sms')` | `config('aruba-sms.minimum_sms')` |

## Step 8: Update Kernel Scheduler (if applicable)

The `CheckArubaSms` command signature changed:

```diff
- $schedule->command('check:remaining-sms')
+ $schedule->command('aruba:check-remaining-sms')
```

Note: In the current codebase, this command is commented out in `app/Console/Kernel.php`.

## Step 9: Update `.env.example`

Replace the Aruba SMS section:

```diff
  #ARUBA_SMS
  ARUBA_SMS_ID=
  ARUBA_SMS_PASSWORD=
- MINIMUM_SMS=
+ ARUBA_SMS_MINIMUM_SMS=
+ ARUBA_SMS_SENDER=Takeathome
+ ARUBA_SMS_SANDBOX=true
+ ARUBA_SMS_LOW_CREDIT_RECIPIENTS=
```

## Step 10: Verify

```bash
# Config publishes correctly
php artisan vendor:publish --tag=aruba-sms-config

# Commands are registered
php artisan list aruba

# Test send (sandbox mode)
php artisan aruba:sms notification --phoneNumber=+393331234567

# Run package tests
cd packages/offline-agency/laravel-aruba-sms
composer install
vendor/bin/pest

# Run main app tests
cd /path/to/main/project
php artisan test
```

## Optional: Use PhoneNumberFormatter

The package includes `OfflineAgency\ArubaSms\Support\PhoneNumberFormatter` which consolidates the `+39` prefix / space-stripping logic scattered in the main app. After integration, you can optionally update:

- `app/Listeners/SendOrderCreatedNotification.php` — replace inline `Str::contains($phoneNumber, '+39')` + `str_replace(' ', '', ...)` with `PhoneNumberFormatter::format($phoneNumber)`
- `app/Notifications/OtpNotification.php` — replace `str_replace(' ', '', $phone_number)` with `PhoneNumberFormatter::stripSpaces($phone_number)`

## Files That Stay in the Main App (Unchanged)

These files use SMS but contain app-specific logic and should NOT be deleted:

| File | Reason |
|------|--------|
| `app/Notifications/OtpNotification.php` | OTP message with User model reference (only needs config key update) |
| `app/Events/OtpRequested.php` | App domain event |
| `app/Listeners/SendOtpNotification.php` | App domain listener |
| `app/Listeners/SendOrderCreatedNotification.php` | Order logic (only needs import update) |
| `app/Http/Middleware/EnsureHasPhoneVerified.php` | App domain middleware |
| `app/Http/Controllers/OaFrontend/Api/OTPController.php` | App domain controller |
| `app/Repositories/UserRepository.php` | `sendOtpToken()` method is app domain |
| `app/Rules/IsOtpTokenValid.php` | OTP validation rule |
| `app/Traits/AlignUserPhoneNumberTrait.php` | Phone number formatting trait |
