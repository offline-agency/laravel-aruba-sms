<?php

namespace OfflineAgency\ArubaSms\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowCreditNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly int $remaining_sms,
    ) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('aruba-sms::notifications.low_credit.subject'))
            ->line(__('aruba-sms::notifications.low_credit.line1'))
            ->line(__('aruba-sms::notifications.low_credit.line2'))
            ->line(__('aruba-sms::notifications.low_credit.line3', ['count' => $this->remaining_sms]));
    }

    public function getRemainingSms(): int
    {
        return $this->remaining_sms;
    }
}
