<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class SerialNumberValidationDto
{
    public function __construct(
        public int $productId,
        public string $serialNumber,
        public int $userId,
        public string $correlationId,
        public ?int $orderId = null,
        public ?string $purchaseDate = null,
        public ?string $proofOfPurchaseUrl = null,
        public ?string $idempotencyKey = null,
    ) {
    }

    public static function fromRequest(array $data, int $userId, string $correlationId): self
    {
        return new self(
            productId: (int) $data['product_id'],
            serialNumber: $data['serial_number'],
            userId: $userId,
            correlationId: $correlationId,
            orderId: $data['order_id'] ?? null,
            purchaseDate: $data['purchase_date'] ?? null,
            proofOfPurchaseUrl: $data['proof_of_purchase_url'] ?? null,
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'serial_number' => $this->serialNumber,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'order_id' => $this->orderId,
            'purchase_date' => $this->purchaseDate,
            'proof_of_purchase_url' => $this->proofOfPurchaseUrl,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}
