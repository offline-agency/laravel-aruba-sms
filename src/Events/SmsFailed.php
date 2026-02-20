<?php

namespace OfflineAgency\ArubaSms\Events;

use OfflineAgency\ArubaSms\ArubaSmsMessage;

class SmsFailed
{
    public function __construct(
        public readonly ArubaSmsMessage $message,
        public readonly \Throwable $exception,
    ) {}
}
