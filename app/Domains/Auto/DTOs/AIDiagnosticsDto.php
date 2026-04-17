<?php declare(strict_types=1);

namespace App\Domains\Auto\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final readonly class AIDiagnosticsDto
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $vin,
        public UploadedFile $photo,
        public ?float $latitude,
        public ?float $longitude,
        public string $correlationId,
        public ?string $ipAddress,
        public ?string $deviceFingerprint,
        public bool $isB2b,
    ) {}

    public static function from(Request $request): self
    {
        $photo = $request->file('photo');
        if ($photo === null || !$photo->isValid()) {
            throw new \RuntimeException('Valid photo file is required');
        }

        return new self(
            tenantId: (int) tenant()->id,
            userId: (int) $request->user()->id,
            vin: $request->input('vin', ''),
            photo: $photo,
            latitude: $request->input('latitude') ? (float) $request->input('latitude') : null,
            longitude: $request->input('longitude') ? (float) $request->input('longitude') : null,
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
            'user_id' => $this->userId,
            'vin' => $this->vin,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'correlation_id' => $this->correlationId,
            'ip_address' => $this->ipAddress,
            'device_fingerprint' => $this->deviceFingerprint,
            'is_b2b' => $this->isB2b,
        ];
    }
}
