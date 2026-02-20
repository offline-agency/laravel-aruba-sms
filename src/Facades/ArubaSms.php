<?php

namespace OfflineAgency\ArubaSms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static object auth()
 * @method static void clearSession()
 * @method static \Illuminate\Http\Client\Response|null sendMessage(\OfflineAgency\ArubaSms\ArubaSmsMessage $message)
 * @method static \Illuminate\Http\Client\Response checkSmsStatus()
 * @method static \Illuminate\Http\Client\Response getSmsHistory(string $from, ?string $to = null, ?int $pageNumber = null, ?int $pageSize = null)
 * @method static \Illuminate\Http\Client\Response getSmsRecipientHistory(string $recipient, string $from, ?string $to = null, ?int $pageNumber = null, ?int $pageSize = null)
 * @method static array<string, mixed> prepareData(\OfflineAgency\ArubaSms\ArubaSmsMessage $message)
 * @method static string getBaseUrl()
 *
 * @see \OfflineAgency\ArubaSms\ArubaSmsClient
 */
class ArubaSms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'aruba-sms-client';
    }
}
