<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class BookSlotDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public int $slotId,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?string $biometricHash = null,
        public ?string $deviceFingerprint = null,
        public ?bool $isCorporate = null,
        public ?int $paymentMethodId = null,
    ) {}

    public static function from(\Illuminate\Http\Request $request): self
    {
        $isB2B = $request->has('inn') && $request->has('business_card_id');

        return new self(
            tenantId: (int) tenant()->id,
            businessGroupId: $isB2B ? (int) $request->input('business_card_id') : null,
            userId: (int) $request->input('user_id'),
            slotId: (int) $request->input('slot_id'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid(),
            idempotencyKey: $request->header('X-Idempotency-Key'),
            biometricHash: $request->input('biometric_hash'),
            deviceFingerprint: hash('sha256', $request->ip() . $request->userAgent()),
            isCorporate: $isB2B,
            paymentMethodId: $request->input('payment_method_id') ? (int) $request->input('payment_method_id') : null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'slot_id' => $this->slotId,
            'correlation_id' => $this->correlationId,
            'biometric_hash' => $this->biometricHash,
            'device_fingerprint' => $this->deviceFingerprint,
            'is_corporate' => $this->isCorporate,
            'payment_method_id' => $this->paymentMethodId,
        ];
    }
}
