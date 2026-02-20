<?php

namespace OfflineAgency\ArubaSms\Exceptions;

use Exception;

class ArubaSmsAuthException extends Exception
{
    public function __construct(string $message = 'Aruba sms authentication failed! Check username and password!')
    {
        parent::__construct($message);
    }
}
