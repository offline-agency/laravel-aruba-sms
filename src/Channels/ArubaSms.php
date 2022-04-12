<?php

namespace OfflineAgency\LaravelArubaSms\Channels;

use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use App\Services\ArubaSms as ArubaSmsService;

class ArubaSms
{
    public function send(
        $notifiable,
        Notification $notification
    )
    {
        $aruba_sms_service = new ArubaSmsService;

        return $aruba_sms_service->sendMessage(
            $notification
        );
    }
}
