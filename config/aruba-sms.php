<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Aruba SMS Panel API Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('ARUBA_SMS_BASE_URL', 'https://smspanel.aruba.it/API/v1.0/REST/'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */
    'credentials' => [
        'username' => env('ARUBA_SMS_ID', ''),
        'password' => env('ARUBA_SMS_PASSWORD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Message Type
    |--------------------------------------------------------------------------
    |
    | The Aruba SMS message type. 'N' = Normal SMS.
    |
    */
    'message_type' => env('ARUBA_SMS_MESSAGE_TYPE', 'N'),

    /*
    |--------------------------------------------------------------------------
    | Sender Name
    |--------------------------------------------------------------------------
    |
    | The sender name that appears on the recipient's device.
    |
    */
    'sender' => env('ARUBA_SMS_SENDER', 'Takeathome'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, SMS messages are logged instead of being sent to the
    | Aruba API. Useful for development and testing environments.
    |
    */
    'sandbox' => env('ARUBA_SMS_SANDBOX', false),

    /*
    |--------------------------------------------------------------------------
    | Minimum SMS Credit Threshold
    |--------------------------------------------------------------------------
    |
    | When remaining SMS credits fall below this value, a low-credit email
    | notification will be sent to the configured recipients.
    |
    */
    'minimum_sms' => env('ARUBA_SMS_MINIMUM_SMS', env('MINIMUM_SMS', 50)),

    /*
    |--------------------------------------------------------------------------
    | Low Credit Notification Recipients
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of email addresses to notify when SMS credits
    | fall below the minimum threshold.
    |
    */
    'low_credit_recipients' => env('ARUBA_SMS_LOW_CREDIT_RECIPIENTS', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for transient HTTP failures (5xx, timeouts).
    | Set times to 0 to disable retries.
    |
    */
    'retry' => [
        'times' => env('ARUBA_SMS_RETRY_TIMES', 3),
        'sleep_ms' => env('ARUBA_SMS_RETRY_SLEEP_MS', 100),
    ],
];
