<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

final readonly class VolumeOrderDto implements BooksDtoInterface
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public string $type,
        public array $items,
        public string $shippingAddress,
        public bool $requestInvoice = true,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'type' => $this->type,
            'items' => $this->items,
            'shipping_address' => $this->shippingAddress,
            'request_invoice' => $this->requestInvoice,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            tenantId: $data['tenant_id'],
            type: $data['type'],
            items: $data['items'],
            shippingAddress: $data['shipping_address'],
            requestInvoice: $data['request_invoice'] ?? true,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
