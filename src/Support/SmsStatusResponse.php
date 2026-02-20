<?php

namespace OfflineAgency\ArubaSms\Support;

use Illuminate\Http\Client\Response;

class SmsStatusResponse
{
    /** @param array<int, array{type: string, quantity: int}> $types */
    private function __construct(
        private readonly array $types,
    ) {}

    public static function fromResponse(Response $response): ?self
    {
        if ($response->status() !== 200) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['sms']) || ! is_array($data['sms'])) {
            return null;
        }

        $types = array_map(fn (array $item) => [
            'type' => $item['type'] ?? 'unknown',
            'quantity' => (int) ($item['quantity'] ?? 0),
        ], $data['sms']);

        return new self($types);
    }

    public function getGpCredits(): ?int
    {
        foreach ($this->types as $type) {
            if ($type['type'] === 'GP') {
                return $type['quantity'];
            }
        }

        return null;
    }

    /** @return array<int, array{type: string, quantity: int}> */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getFormattedSummary(): string
    {
        if (empty($this->types)) {
            return '';
        }

        $parts = array_map(
            fn (array $type) => $type['quantity'].' messages of type '.$type['type'],
            $this->types
        );

        return implode(' and ', $parts);
    }
}
