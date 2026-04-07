<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2B\DTOs;

use App\Domains\RealEstate\Domain\Enums\ContractTypeEnum;

/**
 * Class CreateContractDTO
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\RealEstate\Application\B2B\DTOs
 */
final readonly class CreateContractDTO
{
    public function __construct(
        public string           $propertyId,
        public string           $agentId,
        public int              $clientId,
        public int              $tenantId,
        public ContractTypeEnum $type,
        public int              $priceKopecks,
        public ?int             $leaseDurationMonths,
        public ?string          $documentUrl,
        public string           $correlationId,
        private int $agentUserId = 0,
        private ?string $ipAddress = null,
        private readonly ?string $deviceFingerprint = null) {}

    public static function fromArray(
        array $data,
        int $tenantId,
        string $correlationId,
        int $agentUserId = 0,
        ?string $ipAddress = null,
        ?string $deviceFingerprint = null,
    ): self {
        return new self(
            propertyId: (string) $data['property_id'],
            agentId: (string) $data['agent_id'],
            clientId: (int) $data['client_id'],
            tenantId: $tenantId,
            type: ContractTypeEnum::from((string) $data['type']),
            priceKopecks: (int) ($data['price_rubles'] * 100),
            leaseDurationMonths: isset($data['lease_duration_months'])
                ? (int) $data['lease_duration_months']
                : null,
            documentUrl: isset($data['document_url']) ? (string) $data['document_url'] : null,
            correlationId: $correlationId,
            agentUserId: $agentUserId,
            ipAddress: $ipAddress,
            deviceFingerprint: $deviceFingerprint,
        );
    }
}
