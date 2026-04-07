<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для обновления статуса платежа (capture / refund / webhook).
 */
final readonly class UpdatePaymentRecordDto
{
    public function __construct(
        public int $paymentRecordId,
        public string $status,
        public string $correlationId,
        public ?string $providerPaymentId = null,
        public ?array $providerResponse = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Создать из HTTP-запроса.
     */
    public static function from(Request $request): self
    {
        return new self(
            paymentRecordId: (int) $request->input('payment_record_id', 0),
            status: (string) $request->input('status', ''),
            correlationId: (string) ($request->header('X-Correlation-ID') ?? $request->input('correlation_id', '')),
            providerPaymentId: $request->input('provider_payment_id'),
            providerResponse: $request->input('provider_response'),
            metadata: $request->input('metadata'),
        );
    }

    /**
     * Массив для обновления записи в БД.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'status' => $this->status,
            'correlation_id' => $this->correlationId,
        ];

        if ($this->providerPaymentId !== null) {
            $data['provider_payment_id'] = $this->providerPaymentId;
        }

        if ($this->providerResponse !== null) {
            $data['provider_response'] = $this->providerResponse;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Контекст для аудит-лога.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'payment_record_id' => $this->paymentRecordId,
            'new_status' => $this->status,
            'provider_payment_id' => $this->providerPaymentId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
