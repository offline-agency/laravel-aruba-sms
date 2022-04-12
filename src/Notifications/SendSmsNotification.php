<?php

namespace OfflineAgency\LaravelArubaSms\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class SendSmsNotification extends Notification
{
    use Queueable;

    public $message;
    public $recipient;
    public $message_type;

    public function __construct(
        $message,
        $recipient
    )
    {
        $this->setMessage(
            $message
        );

        $this->setRecipient(
            $recipient
        );

        $this->setMessageType();
    }

    public function via(
        $notifiable
    ): array
    {
        return [
            'aruba-sms'
        ];
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(
        $message
    ): void
    {
        $this->message = $message;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setRecipient(
        $recipient
    ): void
    {
        $this->recipient = $recipient;
    }

    public function getMessageType()
    {
        return $this->message_type;
    }

    public function setMessageType(): void
    {
        $this->message_type = config('aruba.message_type');
    }
}
