<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2B\DTOs;

use App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum;

/**
 * DTO для создания объекта недвижимости (B2B).
 * agentUserId, ipAddress, deviceFingerprint — обязательны для FraudMLService.
 */
final readonly class CreatePropertyDTO
{
    public function __construct(
        public string           $agentId,
        public int              $agentUserId,
        public int              $tenantId,
        public string           $title,
        public string           $description,
        public string           $address,
        public float            $lat,
        public float            $lon,
        public PropertyTypeEnum $type,
        public int              $priceKopecks,
        public float            $areaSqm,
        public int              $rooms,
        public int              $floor,
        public int              $totalFloors,
        public string           $correlationId,
        private array $photos = [],
        private array $documents = [],
        private ?string $ipAddress = null,
        private readonly ?string $deviceFingerprint = null) {}

    public static function fromArray(
        array $data,
        int $tenantId,
        int $agentUserId,
        string $correlationId,
        ?string $ipAddress = null,
        ?string $deviceFingerprint = null,
    ): self {
        return new self(
            agentId: (string) $data['agent_id'],
            agentUserId: $agentUserId,
            tenantId: $tenantId,
            title: (string) $data['title'],
            description: (string) $data['description'],
            address: (string) $data['address'],
            lat: (float) $data['lat'],
            lon: (float) $data['lon'],
            type: PropertyTypeEnum::from((string) $data['type']),
            priceKopecks: (int) ($data['price_rubles'] * 100),
            areaSqm: (float) $data['area_sqm'],
            rooms: (int) ($data['rooms'] ?? 0),
            floor: (int) ($data['floor'] ?? 0),
            totalFloors: (int) ($data['total_floors'] ?? 0),
            correlationId: $correlationId,
            photos: (array) ($data['photos'] ?? []),
            documents: (array) ($data['documents'] ?? []),
            ipAddress: $ipAddress,
            deviceFingerprint: $deviceFingerprint,
        );
    }
}