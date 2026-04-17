<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class MasterMatchingByPhotoDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public UploadedFile $photo,
        public ?string $serviceType = null,
        public ?string $preferredGender = null,
        public ?float $maxDistance = null,
        public ?float $minRating = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?bool $isB2B = null,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            businessGroupId: $request->has('inn') && $request->has('business_card_id')
                ? (int) $request->input('business_card_id')
                : null,
            userId: (int) $request->input('user_id'),
            photo: $request->file('photo'),
            serviceType: $request->input('service_type'),
            preferredGender: $request->input('preferred_gender'),
            maxDistance: $request->input('max_distance') ? (float) $request->input('max_distance') : null,
            minRating: $request->input('min_rating') ? (float) $request->input('min_rating') : null,
            priceMin: $request->input('price_min') ? (int) $request->input('price_min') : null,
            priceMax: $request->input('price_max') ? (int) $request->input('price_max') : null,
            correlationId: $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid(),
            idempotencyKey: $request->header('X-Idempotency-Key'),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'service_type' => $this->serviceType,
            'preferred_gender' => $this->preferredGender,
            'max_distance' => $this->maxDistance,
            'min_rating' => $this->minRating,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'is_b2b' => $this->isB2B,
        ];
    }
}
