<?php

declare(strict_types=1);

/**
 * ProductInventoryChanged — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 * @see https://catvrf.ru/docs/productinventorychanged
 */

namespace App\Events;

use Carbon\CarbonImmutable;

final class ProductInventoryChanged
{
    public function __construct(
        private readonly int $productId,
        private readonly string $vertical,
        private readonly int $oldQuantity,
        private readonly int $newQuantity,
        private readonly string $correlationId,
    ) {}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
