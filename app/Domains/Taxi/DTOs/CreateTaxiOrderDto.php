<?php declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

use Illuminate\Http\Request;

final readonly class CreateTaxiOrderDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $passengerId,
        public string $pickupAddress,
        public float $pickupLat,
        public float $pickupLon,
        public string $dropoffAddress,
        public float $dropoffLat,
        public float $dropoffLon,
        public string $paymentMethod,
        public bool $isSplitPayment,
        public ?array $splitPaymentDetails,
        public bool $voiceOrderEnabled,
        public bool $biometricAuthRequired,
        public bool $videoCallEnabled,
        public ?string $inn,
        public ?string $businessCardId,
        public ?string $ipAddress,
        public ?string $deviceFingerprint,
        public string $correlationId,
        public ?string $idempotencyKey,
        public string $deviceType,
        public string $appVersion,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->input('tenant_id', tenant()->id),
            businessGroupId: $request->input('business_group_id'),
            passengerId: (int) $request->input('passenger_id'),
            pickupAddress: (string) $request->input('pickup_address'),
            pickupLat: (float) $request->input('pickup_lat'),
            pickupLon: (float) $request->input('pickup_lon'),
            dropoffAddress: (string) $request->input('dropoff_address'),
            dropoffLat: (float) $request->input('dropoff_lat'),
            dropoffLon: (float) $request->input('dropoff_lon'),
            paymentMethod: (string) $request->input('payment_method', 'wallet'),
            isSplitPayment: (bool) $request->input('is_split_payment', false),
            splitPaymentDetails: $request->input('split_payment_details'),
            voiceOrderEnabled: (bool) $request->input('voice_order_enabled', false),
            biometricAuthRequired: (bool) $request->input('biometric_auth_required', false),
            videoCallEnabled: (bool) $request->input('video_call_enabled', false),
            inn: $request->input('inn'),
            businessCardId: $request->input('business_card_id'),
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: (string) $request->header('X-Correlation-ID', ''),
            idempotencyKey: $request->header('X-Idempotency-Key'),
            deviceType: (string) $request->input('device_type', 'mobile'),
            appVersion: (string) $request->input('app_version', '1.0.0'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'passenger_id' => $this->passengerId,
            'pickup_address' => $this->pickupAddress,
            'pickup_lat' => $this->pickupLat,
            'pickup_lon' => $this->pickupLon,
            'dropoff_address' => $this->dropoffAddress,
            'dropoff_lat' => $this->dropoffLat,
            'dropoff_lon' => $this->dropoffLon,
            'payment_method' => $this->paymentMethod,
            'is_split_payment' => $this->isSplitPayment,
            'split_payment_details' => $this->splitPaymentDetails,
            'voice_order_enabled' => $this->voiceOrderEnabled,
            'biometric_auth_required' => $this->biometricAuthRequired,
            'video_call_enabled' => $this->videoCallEnabled,
            'inn' => $this->inn,
            'business_card_id' => $this->businessCardId,
            'ip_address' => $this->ipAddress,
            'device_fingerprint' => $this->deviceFingerprint,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'device_type' => $this->deviceType,
            'app_version' => $this->appVersion,
        ];
    }
}
