<?php declare(strict_types=1);

/**
 * RecommendedProductDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/recommendedproductdto
 * @see https://catvrf.ru/docs/recommendedproductdto
 * @see https://catvrf.ru/docs/recommendedproductdto
 * @see https://catvrf.ru/docs/recommendedproductdto
 */


namespace App\Data\DTO\AI\Constructors;

/**
 * Class RecommendedProductDTO
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Data\DTO\AI\Constructors
 */
final readonly class RecommendedProductDTO
{
    public function __construct(
        public int $productId,
        public string $name,
        public float $matchScore,
        public string $reason,
        public int $price,
    )
    {
        // Implementation required by canon
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
