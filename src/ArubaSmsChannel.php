<?php

namespace OfflineAgency\ArubaSms;

use Illuminate\Notifications\Notification;

class ArubaSmsChannel
{
    protected ArubaSmsClient $client;

    public function __construct(ArubaSmsClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification.
     *
     * Supports two patterns:
     * 1. Notification implements toArubaSms($notifiable) returning ArubaSmsMessage
     * 2. Legacy: notification has public $message, $recipient, $message_type properties
     */
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        if (method_exists($notification, 'toArubaSms')) {
            $message = $notification->toArubaSms($notifiable);

            if (! $message instanceof ArubaSmsMessage) {
                return null;
            }

            return $this->client->sendMessage($message);
        }

        // Legacy backward compatibility: read public properties directly.
        // This supports existing notifications like OtpNotification that
        // expose $message, $recipient, $message_type as public properties.
        $message = new ArubaSmsMessage(
            $notification->message ?? '',
            $notification->recipient ?? '',
            $notification->message_type ?? null
        );

        return $this->client->sendMessage($message);
    }
}
