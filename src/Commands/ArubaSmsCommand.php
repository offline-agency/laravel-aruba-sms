<?php

namespace OfflineAgency\ArubaSms\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Notification;
use OfflineAgency\ArubaSms\ArubaSmsClient;
use OfflineAgency\ArubaSms\Notifications\SendSmsNotification;
use OfflineAgency\ArubaSms\Support\PhoneNumberFormatter;
use OfflineAgency\ArubaSms\Support\SmsStatusResponse;

class ArubaSmsCommand extends Command
{
    protected $signature = 'aruba:sms {command_type} {--from=} {--to=} {--pageNumber=} {--pageSize=} {--recipient=} {--phoneNumber=*}';

    protected $description = 'Aruba SMS Panel management: status, history, recipient-history, notification, remaining-credit-raw';

    public function handle(): ?int
    {
        $argument = $this->argument('command_type');

        if ($argument === 'remaining-credit-raw') {
            return $this->checkArubaSmsRaw();
        }

        match ($argument) {
            'status' => $this->checkArubaSmsStatus(),
            'history' => $this->checkArubaSmsHistory(),
            'recipient-history' => $this->checkArubaSmsRecipientHistory(),
            'notification' => $this->testSendSms(),
            default => $this->warn('Command type not valid'),
        };

        return null;
    }

    public function checkArubaSmsRaw(): ?int
    {
        if (! config('aruba-sms.sandbox')) {
            $client = app(ArubaSmsClient::class);
            $response = $client->checkSmsStatus();

            $statusDto = SmsStatusResponse::fromResponse($response);

            return $statusDto?->getGpCredits();
        }

        return random_int(0, 100);
    }

    public function testSendSms(): void
    {
        if (
            ! is_null($this->option('phoneNumber')) &&
            ! empty($this->option('phoneNumber'))
        ) {
            $phoneNumbers = $this->option('phoneNumber');
            $recipient = [];
            $message = 'Sms from console command';

            foreach ($phoneNumbers as $phoneNumber) {
                $recipient[] = PhoneNumberFormatter::format($phoneNumber);
            }

            Notification::route('aruba-sms', null)
                ->notify(
                    new SendSmsNotification(
                        $message,
                        $recipient[0]
                    )
                );
        } else {
            $this->error('Missing require parameter "phoneNumber". Please see project doc.');
        }
    }

    public function checkArubaSmsRecipientHistory(): void
    {
        if (! is_null($this->option('recipient'))) {
            $client = app(ArubaSmsClient::class);

            $recipient = $this->option('recipient');
            $from = ! is_null($this->option('from')) ? $this->option('from') : $this->getFromDate();
            $to = $this->option('to');
            $pageNumber = ! is_null($this->option('pageNumber')) ? (int) $this->option('pageNumber') : null;
            $pageSize = ! is_null($this->option('pageSize')) ? (int) $this->option('pageSize') : null;

            $response = $client->getSmsRecipientHistory(
                $recipient,
                $from,
                $to,
                $pageNumber,
                $pageSize
            );

            $status = $response->status();
            if ($status === 200) {
                $this->info('Recipient History API return "'.$status.'" - '.$response->body());
            } else {
                $this->error('Recipient History API return "'.$status.'" - '.$response->body());
            }
        } else {
            $this->error('Missing require parameter "recipient". Please see project doc.');
        }
    }

    public function checkArubaSmsHistory(): void
    {
        $client = app(ArubaSmsClient::class);

        $from = ! is_null($this->option('from')) ? $this->option('from') : $this->getFromDate();
        $to = $this->option('to');
        $pageNumber = ! is_null($this->option('pageNumber')) ? (int) $this->option('pageNumber') : null;
        $pageSize = ! is_null($this->option('pageSize')) ? (int) $this->option('pageSize') : null;

        $response = $client->getSmsHistory(
            $from,
            $to,
            $pageNumber,
            $pageSize
        );

        $status = $response->status();
        if ($status === 200) {
            $this->info('History API return "'.$status.'" - '.$response->body());
        } else {
            $this->error('History API return "'.$status.'" - '.$response->body());
        }
    }

    public function checkArubaSmsStatus(): void
    {
        $client = app(ArubaSmsClient::class);
        $response = $client->checkSmsStatus();

        $this->getStatusMessage($response);
    }

    public function getStatusMessage(Response $response): string
    {
        $status = $response->status();

        $statusDto = SmsStatusResponse::fromResponse($response);

        if ($statusDto !== null) {
            $message = 'Status API return "'.$status.'" with '.$statusDto->getFormattedSummary();
            $this->info($message);
        } else {
            $message = 'ERROR - Status API return "'.$status.'" - '.$response->body();
            $this->error($message);
        }

        return $message;
    }

    public function getFromDate(): string
    {
        return Carbon::today()->addSecond()->format('YmdHis');
    }
}
