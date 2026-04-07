<?php

declare(strict_types=1);

/**
 * ConfirmViewingDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/confirmviewingdto
 */


namespace App\Domains\RealEstate\Application\B2B\DTOs;

use DateTimeImmutable;

/**
 * Class ConfirmViewingDTO
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
final readonly class ConfirmViewingDTO
{
    public function __construct(
        public string             $viewingId,
        public int                $tenantId,
        public ?DateTimeImmutable $rescheduledAt,
        public string             $correlationId,
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
            viewingId: (string) $data['viewing_id'],
            tenantId: $tenantId,
            rescheduledAt: isset($data['rescheduled_at'])
                ? new DateTimeImmutable($data['rescheduled_at'])
                : null,
            correlationId: $correlationId,
            agentUserId: $agentUserId,
            ipAddress: $ipAddress,
            deviceFingerprint: $deviceFingerprint,
        );
    }
}
