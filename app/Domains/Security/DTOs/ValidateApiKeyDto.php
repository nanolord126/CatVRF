<?php declare(strict_types=1);

namespace App\Domains\Security\DTOs;

final readonly class ValidateApiKeyDto
{
    public function __construct(
        public string $rawKey,
        public string $clientIp,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            rawKey: $data['api_key'],
            clientIp: $data['client_ip'] ?? request()->ip(),
        );
    }

    public function toArray(): array
    {
        return [
            'raw_key' => $this->rawKey,
            'client_ip' => $this->clientIp,
        ];
    }
}
