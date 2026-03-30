<?php declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

/**
 * @phpstan-type Analysis array<string, mixed>
 * @phpstan-type RecommendedProducts array<int, RecommendedProductDTO>
 * @phpstan-type RecommendedServices array<int, RecommendedServiceDTO>
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
    ) {
    }
}
