<?php

namespace OfflineAgency\ArubaSms\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use OfflineAgency\ArubaSms\ArubaSmsMessage;

class SendSmsNotification extends Notification
{
    use Queueable;

    public string $message;

    public string $recipient;

    public string $message_type;

    public function __construct(
        string $message,
        string $recipient
    ) {
        $this->message = $message;
        $this->recipient = $recipient;
        $this->message_type = config('aruba-sms.message_type', 'N');
    }

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['aruba-sms'];
    }

    public function toArubaSms(mixed $notifiable): ArubaSmsMessage
    {
        return new ArubaSmsMessage(
            $this->message,
            $this->recipient,
            $this->message_type
        );
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getMessageType(): string
    {
        return $this->message_type;
    }

    public function setMessageType(string $message_type): void
    {
        $this->message_type = $message_type;
    }
}
