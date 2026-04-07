<?php

declare(strict_types=1);

/**
 * DeliveryData — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/deliverydata
 */


namespace App\Domains\Delivery\Domain\DTOs;

use App\Domains\Delivery\Domain\Enums\DeliveryStatus;
use Spatie\LaravelData\Data;

/**
 * Class DeliveryData
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Delivery\Domain\DTOs
 */
final class DeliveryData extends Data
{
    public function __construct(
        private readonly int $order_id,
        private readonly int $tenant_id,
        private readonly ?int $courier_id,
        private readonly DeliveryStatus $status,
        private readonly string $from_address,
        private readonly string $to_address,
        private readonly ?array $payload,
        private readonly ?string $correlation_id) {

    }
}
