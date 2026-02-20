<?php

namespace OfflineAgency\ArubaSms;

class ArubaSmsMessage
{
    protected string $content;

    protected string $recipient;

    /** @var array<int, string> */
    protected array $recipients = [];

    protected string $message_type;

    /**
     * @param  string|array<int, string>  $recipient
     */
    public function __construct(
        string $content = '',
        string|array $recipient = '',
        ?string $message_type = null,
    ) {
        $this->content = $content;

        if (is_array($recipient)) {
            $this->recipients = $recipient;
            $this->recipient = $recipient[0] ?? '';
        } else {
            $this->recipient = $recipient;
        }

        $this->message_type = $message_type ?? config('aruba-sms.message_type', 'N');
    }

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the recipient(s).
     *
     * @param  string|array<int, string>  $recipient
     */
    public function to(string|array $recipient): self
    {
        if (is_array($recipient)) {
            $this->recipients = $recipient;
            $this->recipient = $recipient[0] ?? '';
        } else {
            $this->recipient = $recipient;
            $this->recipients = [];
        }

        return $this;
    }

    public function messageType(string $type): self
    {
        $this->message_type = $type;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get the primary recipient.
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * Get all recipients as an array.
     *
     * @return array<int, string>
     */
    public function getRecipients(): array
    {
        if (! empty($this->recipients)) {
            return $this->recipients;
        }

        return $this->recipient !== '' ? [$this->recipient] : [];
    }

    public function getMessageType(): string
    {
        return $this->message_type;
    }
}
