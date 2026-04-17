<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class ReturnFraudDetectionDto
{
    /**
     * @param array<string, mixed> $deviceMetadata
     * @param array<string, mixed> $userBehavior
     */
    public function __construct(
        public int $orderId,
        public int $productId,
        public string $serialNumber,
        public int $userId,
        public string $correlationId,
        public string $returnReason,
        public string $condition,
        public array $deviceMetadata,
        public array $userBehavior,
        public ?string $idempotencyKey = null,
    ) {
    }

    public static function fromRequest(array $data, int $userId, string $correlationId): self
    {
        return new self(
            orderId: (int) $data['order_id'],
            productId: (int) $data['product_id'],
            serialNumber: $data['serial_number'],
            userId: $userId,
            correlationId: $correlationId,
            returnReason: $data['return_reason'],
            condition: $data['condition'],
            deviceMetadata: (array) ($data['device_metadata'] ?? []),
            userBehavior: (array) ($data['user_behavior'] ?? []),
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'serial_number' => $this->serialNumber,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'return_reason' => $this->returnReason,
            'condition' => $this->condition,
            'device_metadata' => $this->deviceMetadata,
            'user_behavior' => $this->userBehavior,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}
