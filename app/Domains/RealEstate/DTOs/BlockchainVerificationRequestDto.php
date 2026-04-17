<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;

final readonly class BlockchainVerificationRequestDto
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly int $userId,
        public readonly string $correlationId,
        public readonly int $propertyId,
        public readonly array $documentHashes,
        public readonly ?string $blockchainNetwork = 'ethereum',
        public readonly ?bool $generateSmartContract = true
    ) {}

    public static function from(Request $request): self
    {
        $documentHashes = $request->input('document_hashes', []);
        
        if (empty($documentHashes)) {
            throw new \InvalidArgumentException('Document hashes are required');
        }

        return new self(
            tenantId: (int) tenant()?->id ?? $request->input('tenant_id'),
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId: (int) $request->user()?->id ?? $request->input('user_id'),
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            propertyId: (int) $request->route('propertyId'),
            documentHashes: $documentHashes,
            blockchainNetwork: $request->input('blockchain_network', 'ethereum'),
            generateSmartContract: $request->input('generate_smart_contract', true)
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'property_id' => $this->propertyId,
            'document_hashes' => $this->documentHashes,
            'blockchain_network' => $this->blockchainNetwork,
            'generate_smart_contract' => $this->generateSmartContract,
        ];
    }
}
