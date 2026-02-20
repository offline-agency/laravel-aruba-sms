<?php

namespace OfflineAgency\ArubaSms\Events;

use Illuminate\Http\Client\Response;
use OfflineAgency\ArubaSms\ArubaSmsMessage;

class SmsSent
{
    public function __construct(
        public readonly ArubaSmsMessage $message,
        public readonly Response $response,
    ) {}
}
