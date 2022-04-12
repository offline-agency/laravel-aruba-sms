<?php

namespace OfflineAgency\LaravelArubaSms\Console\Commands;

use App\Models\User;
use App\Notifications\SendSmsNotification;
use OfflineAgency\LaravelArubaSms\LaravelArubaSms;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ArubaSmsCommand extends Command
{
    protected $signature = 'aruba:sms {command_type} {--from=} {--to=} {--pageNumber=} {--pageSize=} {--recipient=} {--phoneNumber=*}';

    protected $description = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $argument = $this->argument('command_type');
        switch ($argument) {
            case 'status':
                $this->checkArubaSmsStatus();
                break;
            case 'history':
                $this->checkArubaSmsHistory();
                break;
            case 'recipient-history':
                $this->checkArubaSmsRecipientHistory();
                break;
            case 'notification':
                $this->testSendSms();
                break;
            default:
                $this->warn('Command type not valid');
        }
    }

    public function testSendSms()
    {
        if (
            !is_null($this->option('phoneNumber')) &&
            !empty($this->option('phoneNumber'))
        ) {
            $phoneNumbers = $this->option('phoneNumber');
            $recipient = [];
            $message = 'Sms from console command';
            foreach ($phoneNumbers as $phoneNumber) {
                $phoneNumber = Str::contains($phoneNumber, '+39') ? $phoneNumber : '+39' . $phoneNumber;
                if (Str::contains($phoneNumber, ' ')) {
                    $phoneNumber = str_replace(
                        ' ',
                        '',
                        $phoneNumber
                    );
                }
                array_push(
                    $recipient,
                    $phoneNumber
                );
            }

            $user = new User;
            Notification::send(
                $user,
                new SendSmsNotification(
                    $message,
                    $recipient
                )
            );
        } else {
            $this->error('Missing require parameter "phoneNumber". Please see project doc.');
        }
    }

    public function checkArubaSmsRecipientHistory()
    {
        if (!is_null($this->option('recipient'))) {
            $aruba_sms_service = new LaravelArubaSms;

            $recipient = $this->option('recipient');
            $from = !is_null($this->option('from')) ? $this->option('from') : $this->getFromDate();
            $to = $this->option('to');
            $pageNumber = $this->option('pageNumber');
            $pageSize = $this->option('pageSize');

            $response = $aruba_sms_service->getSmsRecipientHistory(
                $recipient,
                $from,
                $to,
                $pageNumber,
                $pageSize
            );

            $status = $response->status();
            if ($status === 200) {
                $message = 'Recipient History API return "' . $status . '" - ' . $response->body();
                $this->info($message);
            } else {
                $message = 'Recipient History API return "' . $status . '" - ' . $response->body();
                $this->error($message);
            }
        } else {
            $this->error('Missing require parameter "recipient". Please see project docs.');
        }
    }

    public function checkArubaSmsHistory(): string
    {
        $aruba_sms_service = new LaravelArubaSms;

        $from = !is_null($this->option('from')) ? $this->option('from') : $this->getFromDate();
        $to = $this->option('to');
        $pageNumber = $this->option('pageNumber');
        $pageSize = $this->option('pageSize');

        $response = $aruba_sms_service->getSmsHistory(
            $from,
            $to,
            $pageNumber,
            $pageSize
        );

        $status = $response->status();
        if ($status === 200) {
            $message = 'History API return "' . $status . '" - ' . $response->body();
            $this->info($message);
        } else {
            $message = 'History API return "' . $status . '" - ' . $response->body();
            $this->error($message);
        }

        return $response->body();
    }

    public function checkArubaSmsStatus(): string
    {
        $aruba_sms_service = new ArubaSmsService;

        $response = $aruba_sms_service->checkSmsStatus();

        return $this->getStatusMessage(
            $response
        );
    }

    public function getStatusMessage(
        Response $response
    ): string
    {
        $status = $response->status();

        $message = 'ERROR - ';
        if ($status === 200) {
            $response = json_decode(
                $response->body()
            );

            $type_quantity = '';
            foreach ($response->sms as $sms) {
                $type_quantity .= $sms->quantity . ' messages of type ' . $sms->type . ' and ';
            }
            $type_quantity = substr(
                $type_quantity,
                0,
                -5
            );

            $message = 'Status API return "' . $status . '" with ' . $type_quantity;
            $this->info($message);
        } else {
            $message .= 'Status API return "' . $status . '" - ' . $response->body();
            $this->error($message);
        }

        return $message;
    }

    public function getFromDate(): string
    {
        $date = Carbon::now();
        $date->hour(0);
        $date->minute(0);
        $date->second(1);
        return $date->format('YmdHis');
    }
}
