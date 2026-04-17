<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class PublishListingDto
{
    public function __construct(
        public int $tenantId,
        public string $propertyUuid,
        public int $sellerId,
        public string $correlationId,
        public bool $enableBlockchainVerification,
        public bool $enablePredictiveScoring,
        public bool $makeFeatured,
        public array $documents,
        public string $publishedBy,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            propertyUuid: $request->input('property_uuid'),
            sellerId: (int) $request->input('seller_id'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            enableBlockchainVerification: (bool) $request->input('enable_blockchain_verification', true),
            enablePredictiveScoring: (bool) $request->input('enable_predictive_scoring', true),
            makeFeatured: (bool) $request->input('make_featured', false),
            documents: $request->input('documents', []),
            publishedBy: $request->input('published_by', 'seller'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'property_uuid' => $this->propertyUuid,
            'seller_id' => $this->sellerId,
            'correlation_id' => $this->correlationId,
            'enable_blockchain_verification' => $this->enableBlockchainVerification,
            'enable_predictive_scoring' => $this->enablePredictiveScoring,
            'make_featured' => $this->makeFeatured,
            'documents' => $this->documents,
            'published_by' => $this->publishedBy,
        ];
    }
}
