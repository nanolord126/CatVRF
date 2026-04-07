<?php

declare(strict_types=1);

/**
 * RequestViewingDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/requestviewingdto
 */


namespace App\Domains\RealEstate\Application\B2C\DTOs;

use DateTimeImmutable;

/**
 * Class RequestViewingDTO
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
 * @package App\Domains\RealEstate\Application\B2C\DTOs
 */
final readonly class RequestViewingDTO
{
    public function __construct(
        public string            $propertyId,
        public int               $clientId,
        public DateTimeImmutable $scheduledAt,
        public string            $clientName,
        public string            $clientPhone,
        public ?string           $notes,
        public string            $correlationId,
        private ?string $ipAddress = null,
        private readonly ?string $deviceFingerprint = null) {}

    public static function fromArray(
        array $data,
        int $clientId,
        string $correlationId,
        ?string $ipAddress = null,
        ?string $deviceFingerprint = null,
    ): self {
        return new self(
            propertyId: (string) $data['property_id'],
            clientId: $clientId,
            scheduledAt: new DateTimeImmutable($data['scheduled_at']),
            clientName: (string) $data['client_name'],
            clientPhone: (string) $data['client_phone'],
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            correlationId: $correlationId,
            ipAddress: $ipAddress,
            deviceFingerprint: $deviceFingerprint,
        );
    }
}
