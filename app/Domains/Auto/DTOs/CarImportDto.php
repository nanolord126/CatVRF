<?php declare(strict_types=1);

namespace App\Domains\Auto\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CarImportDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $vin,
        public string $country,
        public float $declaredValue,
        public string $currency,
        public string $engineType,
        public ?float $engineVolume,
        public int $manufactureYear,
        public string $correlationId,
        public ?string $ipAddress,
        public ?string $deviceFingerprint,
        public bool $isB2b,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) tenant()->id,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId: (int) $request->user()->id,
            vin: $request->input('vin', ''),
            country: $request->input('country', ''),
            declaredValue: (float) $request->input('declared_value', 0),
            currency: $request->input('currency', 'eur'),
            engineType: $request->input('engine_type', 'petrol'),
            engineVolume: $request->input('engine_volume') ? (float) $request->input('engine_volume') : null,
            manufactureYear: (int) $request->input('manufacture_year', date('Y')),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            isB2b: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'vin' => $this->vin,
            'country' => $this->country,
            'declared_value' => $this->declaredValue,
            'currency' => $this->currency,
            'engine_type' => $this->engineType,
            'engine_volume' => $this->engineVolume,
            'manufacture_year' => $this->manufactureYear,
            'correlation_id' => $this->correlationId,
            'ip_address' => $this->ipAddress,
            'device_fingerprint' => $this->deviceFingerprint,
            'is_b2b' => $this->isB2b,
        ];
    }
}
