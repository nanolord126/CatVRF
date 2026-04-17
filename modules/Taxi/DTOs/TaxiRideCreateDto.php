<?php declare(strict_types=1);

namespace Modules\Taxi\DTOs;

use Spatie\LaravelData\Data;

/**
 * DTO for creating taxi ride — Production Ready 2026.
 * 
 * Contains all required and optional fields for ride creation.
 */
final class TaxiRideCreateDto extends Data
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $passengerId,
        public readonly float $pickupLatitude,
        public readonly float $pickupLongitude,
        public readonly float $dropoffLatitude,
        public readonly float $dropoffLongitude,
        public readonly string $pickupAddress,
        public readonly string $dropoffAddress,
        public readonly int $estimatedPriceKopeki,
        public readonly string $correlationId,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $inn = null,
        public readonly ?string $businessCardId = null,
        public readonly bool $voiceOrder = false,
        public readonly bool $biometricVerified = false,
        public readonly bool $splitPayment = false,
        public readonly array $splitPaymentUsers = [],
        public readonly bool $arNavigationEnabled = true,
        public readonly bool $videoCallRequested = false,
        public readonly ?string $vehicleClass = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $deviceFingerprint = null,
    ) {}
}
