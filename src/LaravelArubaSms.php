<?php

namespace OfflineAgency\LaravelArubaSms;

use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class LaravelArubaSms

{
  protected $base_url;
  protected $header;

  public function __construct()
  {
    $this->setBaseUrl();
  }

  public function auth()
  {
    $username = config('aruba-sms.credentials.username');
    $password = config('aruba-sms.credentials.password');

    $url = $this->getBaseUrl() . 'login?' .
      'username=' . $username . '&' .
      'password=' . $password;

    $response = Http::get(
      $url
    );

    $status = $response->status();
    if ($status === 200) {
      $values = explode(
        ";",
        $response->body()
      );

      $this->setHeader(
        $values[0],
        $values[1]
      );

      return (object)[
        'user_key' => $values[0],
        'session_key' => $values[1]
      ];
    } else {
      throw new Exception('Aruba sms authentication failed! Check username and password!');
    }
  }

  public function sendMessage(
    Notification $notification
  )
  {
    if (config('app.env') === 'production') {
      $this->auth();

      $data = $this->prepare_data(
        $notification
      );

      $url = $this->getBaseUrl() . 'sms';

      $response = Http::withHeaders(
        $this->getHeader()
      )->post(
        $url,
        $data
      );

      $status = $response->status();
      if (
        $status === 200 ||
        $status === 201
      ) {
        return $response;
      } else {
        $status_response = $this->checkSmsStatus();

        $status_message = 'ERROR - ';
        if ($status_response->status() === 200) {
          $status_response = json_decode(
            $status_response->body()
          );

          $type_quantity = '';
          foreach ($status_response->sms as $sms) {
            $type_quantity .= $sms->quantity . ' messages of type ' . $sms->type . ' and ';
          }
          $type_quantity = substr(
            $type_quantity,
            0,
            -5
          );

          $status_message = 'Status API return "' . $status . '" with ' . $type_quantity;
        } else {
          $status_message .= 'Status API return "' . $status . '" - ' . $response->body();
        }

        throw new Exception('Message not sended - ' .
          'STATUS: ' . $response->status() . ' - ' .
          'ERROR MESSAGE: ' . $response->body() . ' - ' .
          'RECIPIENT: ' . json_encode(Arr::get($data,  'recipient')) . ' - ' .
          'CONTENT: ' . Arr::get($data, 'message') . ' - ' .
          'STATUS: ' . $status_message
        );
      }
    }
  }

  public function checkSmsStatus(): Response
  {
    $this->auth();

    $url = $this->getBaseUrl() . 'status';

    return Http::withHeaders(
      $this->getHeader()
    )->get(
      $url
    );
  }

  public function getSmsHistory(
    $from,
    $to,
    $pageNumber,
    $pageSize
  ): Response
  {
    $this->auth();

    $url = $this->getBaseUrl() . 'smshistory?' .
      'from=' . $from;

    if (!is_null($to)) {
      $url .= '&to=' . $to;
    }

    if (!is_null($pageNumber)) {
      $url .= '&pageNumber=' . $pageNumber;
    }

    if (!is_null($pageSize)) {
      $url .= '&pageSize=' . $pageSize;
    }

    return Http::withHeaders(
      $this->getHeader()
    )->get(
      $url
    );
  }

  public function getSmsRecipientHistory(
    $recipient,
    $from,
    $to,
    $pageNumber,
    $pageSize
  ): Response
  {
    $this->auth();

    $url = $this->getBaseUrl() . 'rcptHistory?' .
      'recipient=' . $recipient . '&' .
      'from=' . $from;

    if (!is_null($to)) {
      $url .= '&to=' . $to;
    }

    if (!is_null($pageNumber)) {
      $url .= '&pageNumber=' . $pageNumber;
    }

    if (!is_null($pageSize)) {
      $url .= '&pageSize=' . $pageSize;
    }

    return Http::withHeaders(
      $this->getHeader()
    )->get(
      $url
    );
  }

  public function prepare_data(
    Notification $notification
  ): array
  {
    return [
      'message_type' => $notification->message_type,
      'message' => $notification->message,
      'recipient' => $notification->recipient,
    ];
  }

  public function getBaseUrl()
  {
    return $this->base_url;
  }

  public function setBaseUrl(): void
  {
    $this->base_url = config('aruba.base_url');
  }

  public function getHeader()
  {
    return $this->header;
  }

  public function setHeader(
    $user_key,
    $session_key
  ): void
  {
    $this->header = [
      'Content-type' => 'application/json',
      'user_key' => $user_key,
      'Session_key' => $session_key,
    ];
  }
}
