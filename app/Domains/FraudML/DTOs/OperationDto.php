<?php

declare(strict_types=1);

/**
 * Class OperationDto
 *
 * Part of the FraudML vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\FraudML\DTOs
 */
final readonly class OperationDto
{
    public function __construct(
        public int $tenant_id,
        public int $user_id,
        public string $operation_type,
        public int $amount,
        public string $ip_address,
        public string $device_fingerprint,
        public string $correlation_id) {}
}
