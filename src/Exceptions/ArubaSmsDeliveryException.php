<?php

namespace OfflineAgency\ArubaSms\Exceptions;

use Exception;

class ArubaSmsDeliveryException extends Exception
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $errorBody,
        public readonly string $recipient,
        public readonly string $messageContent,
        public readonly string $statusInfo
    ) {
        $message = 'Message not sent - '
            .'HTTP_STATUS: '.$httpStatus.' - '
            .'ERROR MESSAGE: '.$errorBody.' - '
            .'RECIPIENT: '.$recipient.' - '
            .'CONTENT: '.$messageContent.' - '
            .'SMS_STATUS: '.$statusInfo;

        parent::__construct($message);
    }
}
