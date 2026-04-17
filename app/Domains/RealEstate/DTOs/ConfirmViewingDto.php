<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class ConfirmViewingDto
{
    public function __construct(
        public int $tenantId,
        public string $viewingUuid,
        public int $buyerId,
        public string $correlationId,
        public string $faceIdToken,
        public float $confidenceScore,
        public string $verificationMethod,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            viewingUuid: $request->input('viewing_uuid'),
            buyerId: (int) $request->input('buyer_id'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            faceIdToken: $request->input('faceid_token'),
            confidenceScore: (float) $request->input('confidence_score', 0.0),
            verificationMethod: $request->input('verification_method', 'selfie'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'viewing_uuid' => $this->viewingUuid,
            'buyer_id' => $this->buyerId,
            'correlation_id' => $this->correlationId,
            'faceid_token' => $this->faceIdToken,
            'confidence_score' => $this->confidenceScore,
            'verification_method' => $this->verificationMethod,
        ];
    }
}
