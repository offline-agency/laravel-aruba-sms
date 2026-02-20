<?php

namespace OfflineAgency\ArubaSms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OfflineAgency\ArubaSms\Events\SmsFailed;
use OfflineAgency\ArubaSms\Events\SmsSent;
use OfflineAgency\ArubaSms\Exceptions\ArubaSmsAuthException;
use OfflineAgency\ArubaSms\Exceptions\ArubaSmsDeliveryException;
use OfflineAgency\ArubaSms\Support\SmsStatusResponse;

class ArubaSmsClient
{
    protected string $base_url;

    /** @var array<string, string>|null */
    protected ?array $header = null;

    public function __construct()
    {
        $this->base_url = config('aruba-sms.base_url');
    }

    /**
     * Authenticate with the Aruba SMS Panel API.
     *
     * Uses cached session credentials when available.
     *
     * @throws ArubaSmsAuthException
     */
    public function auth(): object
    {
        if ($this->header !== null) {
            return (object) ['cached' => true];
        }

        $username = config('aruba-sms.credentials.username');
        $password = config('aruba-sms.credentials.password');

        $url = $this->base_url.'login';

        $response = $this->httpClient()
            ->withBasicAuth($username, $password)
            ->get($url);

        $status = $response->status();
        if ($status === 200) {
            $values = explode(';', $response->body());

            $this->setHeader($values[0], $values[1]);

            return (object) [
                'user_key' => $values[0],
                'session_key' => $values[1],
            ];
        }

        throw new ArubaSmsAuthException;
    }

    /**
     * Clear the cached session, forcing re-authentication on next request.
     */
    public function clearSession(): void
    {
        $this->header = null;
    }

    /**
     * Send an SMS message via the Aruba SMS Panel API.
     *
     * In sandbox mode, the message is logged instead of being sent.
     *
     * @throws ArubaSmsAuthException
     * @throws ArubaSmsDeliveryException
     */
    public function sendMessage(ArubaSmsMessage $message): ?Response
    {
        if (config('aruba-sms.sandbox')) {
            Log::info('*** Aruba SMS DEBUG ***');
            Log::info('Notification sent successfully!');
            Log::info('Recipient: '.implode(', ', $message->getRecipients()));
            Log::info('Message: '.$message->getContent());
            Log::info('Message Type: '.$message->getMessageType());
            Log::info('*** *** ***');

            return null;
        }

        $this->auth();

        $data = $this->prepareData($message);

        $url = $this->base_url.'sms';

        $response = $this->httpClient()
            ->withHeaders($this->getHeader())
            ->post($url, $data);

        $status = $response->status();
        if ($status === 200 || $status === 201) {
            event(new SmsSent($message, $response));

            return $response;
        }

        $statusResponse = $this->checkSmsStatus();
        $statusDto = SmsStatusResponse::fromResponse($statusResponse);

        $statusMessage = $statusDto !== null
            ? 'Status API return "'.$status.'" with '.$statusDto->getFormattedSummary()
            : 'ERROR - Status API return "'.$status.'" - '.$response->body();

        $exception = new ArubaSmsDeliveryException(
            $response->status(),
            $response->body(),
            json_encode(Arr::get($data, 'recipient')),
            Arr::get($data, 'message'),
            $statusMessage
        );

        event(new SmsFailed($message, $exception));

        throw $exception;
    }

    /**
     * Check remaining SMS credits.
     *
     * @throws ArubaSmsAuthException
     */
    public function checkSmsStatus(): Response
    {
        $this->auth();

        $url = $this->base_url.'status';

        return $this->httpClient()
            ->withHeaders($this->getHeader())
            ->get($url);
    }

    /**
     * Get SMS sending history.
     *
     * @throws ArubaSmsAuthException
     */
    public function getSmsHistory(
        string $from,
        ?string $to = null,
        ?int $pageNumber = null,
        ?int $pageSize = null
    ): Response {
        $this->auth();

        $params = array_filter([
            'from' => $from,
            'to' => $to,
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
        ], fn ($v) => $v !== null);

        $url = $this->base_url.'smshistory?'.http_build_query($params);

        return $this->httpClient()
            ->withHeaders($this->getHeader())
            ->get($url);
    }

    /**
     * Get SMS history for a specific recipient.
     *
     * @throws ArubaSmsAuthException
     */
    public function getSmsRecipientHistory(
        string $recipient,
        string $from,
        ?string $to = null,
        ?int $pageNumber = null,
        ?int $pageSize = null
    ): Response {
        $this->auth();

        $params = array_filter([
            'recipient' => $recipient,
            'from' => $from,
            'to' => $to,
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
        ], fn ($v) => $v !== null);

        $url = $this->base_url.'rcptHistory?'.http_build_query($params);

        return $this->httpClient()
            ->withHeaders($this->getHeader())
            ->get($url);
    }

    /**
     * Prepare the API request payload from an ArubaSmsMessage.
     *
     * @return array<string, mixed>
     */
    public function prepareData(ArubaSmsMessage $message): array
    {
        return [
            'message_type' => $message->getMessageType(),
            'message' => $message->getContent(),
            'recipient' => $message->getRecipients(),
            'sender' => config('aruba-sms.sender'),
        ];
    }

    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    /** @return array<string, string>|null */
    public function getHeader(): ?array
    {
        return $this->header;
    }

    public function setHeader(string $user_key, string $session_key): void
    {
        $this->header = [
            'Content-type' => 'application/json',
            'user_key' => $user_key,
            'Session_key' => $session_key,
        ];
    }

    /**
     * Build an HTTP client with retry for transient failures.
     */
    protected function httpClient(): PendingRequest
    {
        $times = (int) config('aruba-sms.retry.times', 3);
        $sleepMs = (int) config('aruba-sms.retry.sleep_ms', 100);

        return Http::retry($times, $sleepMs, function (\Throwable $exception): bool {
            if ($exception instanceof ConnectionException) {
                return true;
            }

            if ($exception instanceof RequestException) {
                return $exception->response->serverError();
            }

            return false;
        }, throw: false);
    }
}
