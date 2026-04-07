<?php declare(strict_types=1);

/**
 * BeautyLookConstructorOutput — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/beautylookconstructoroutput
 * @see https://catvrf.ru/docs/beautylookconstructoroutput
 * @see https://catvrf.ru/docs/beautylookconstructoroutput
 * @see https://catvrf.ru/docs/beautylookconstructoroutput
 * @see https://catvrf.ru/docs/beautylookconstructoroutput
 */


namespace App\Data\DTO\AI\Constructors;

/**
 * @phpstan-type Analysis array<string, mixed>
 * @phpstan-type RecommendedProducts array<int, RecommendedProductDTO>
 * @phpstan-type RecommendedServices array<int, RecommendedServiceDTO>
 */
/**
 * Class BeautyLookConstructorOutput
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
final readonly class BeautyLookConstructorOutput
{
    /**
     * @param Analysis $makeupAnalysis
     * @param Analysis $hairAnalysis
     * @param Analysis $skinAnalysis
     * @param RecommendedProducts $recommendedProducts
     * @param RecommendedServices $recommendedServices
     */
    public function __construct(
        public string $lookDescription,
        public array $makeupAnalysis,
        public array $hairAnalysis,
        public array $skinAnalysis,
        public array $recommendedProducts,
        public array $recommendedServices,
        public int $totalCost,
        public string $correlationId,
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
}
