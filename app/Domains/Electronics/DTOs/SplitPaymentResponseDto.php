<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class SplitPaymentResponseDto
{
    /**
     * @param array<array{source: string, amount_kopecks: int, status: string, transaction_id: string}> $paymentResults
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public bool $success,
        public string $correlationId,
        public string $paymentId,
        public array $paymentResults,
        public int $totalAmountKopecks,
        public bool $escrowEnabled,
        public ?string $escrowReleaseDate,
        public array $metadata,
        public ?string $failureReason = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'correlation_id' => $this->correlationId,
            'payment_id' => $this->paymentId,
            'payment_results' => $this->paymentResults,
            'total_amount_kopecks' => $this->totalAmountKopecks,
            'escrow_enabled' => $this->escrowEnabled,
            'escrow_release_date' => $this->escrowReleaseDate,
            'metadata' => $this->metadata,
            'failure_reason' => $this->failureReason,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            correlationId: $data['correlation_id'] ?? '',
            paymentId: $data['payment_id'] ?? '',
            paymentResults: $data['payment_results'] ?? [],
            totalAmountKopecks: $data['total_amount_kopecks'] ?? 0,
            escrowEnabled: $data['escrow_enabled'] ?? false,
            escrowReleaseDate: $data['escrow_release_date'] ?? null,
            metadata: $data['metadata'] ?? [],
            failureReason: $data['failure_reason'] ?? null,
        );
    }
}
