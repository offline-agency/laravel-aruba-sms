<?php

namespace OfflineAgency\ArubaSms\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use OfflineAgency\ArubaSms\ArubaSmsClient;
use OfflineAgency\ArubaSms\Notifications\LowCreditNotification;
use OfflineAgency\ArubaSms\Support\SmsStatusResponse;

class CheckRemainingSmsCommand extends Command
{
    protected $signature = 'aruba:check-remaining-sms';

    protected $description = 'Check remaining SMS credits and notify if below threshold';

    public function handle(): void
    {
        $minimum_sms = (float) config('aruba-sms.minimum_sms');

        if (config('aruba-sms.sandbox')) {
            $remaining_sms = random_int(0, 100);
        } else {
            $client = app(ArubaSmsClient::class);
            $response = $client->checkSmsStatus();

            $statusDto = SmsStatusResponse::fromResponse($response);
            $remaining_sms = $statusDto?->getGpCredits();
        }

        if ($remaining_sms !== null && $remaining_sms < $minimum_sms) {
            $recipients = array_filter(
                array_map('trim', explode(',', config('aruba-sms.low_credit_recipients', '')))
            );

            foreach ($recipients as $email) {
                Notification::route('mail', $email)
                    ->notify(new LowCreditNotification($remaining_sms));

                $this->info('Email sent to '.$email);
            }
        }
    }
}
