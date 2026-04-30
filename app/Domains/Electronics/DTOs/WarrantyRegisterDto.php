<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class WarrantyRegisterDto
{
    public function __construct(
        public int $productId,
        public string $serialNumber,
        public string $orderId,
        public int $userId,
        public int $monthsDuration = 12,
        public string $correlationId = '',
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'serial_number' => $this->serialNumber,
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'months_duration' => $this->monthsDuration,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            productId: $data['product_id'],
            serialNumber: $data['serial_number'],
            orderId: $data['order_id'],
            userId: $data['user_id'],
            monthsDuration: $data['months_duration'] ?? 12,
            correlationId: $data['correlation_id'] ?? '',
        );
    }
}
