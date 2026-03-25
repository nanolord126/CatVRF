<?php

declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;

final class BeautyLookConstructorOutput extends Data
{
    public function __construct(
        public readonly string $lookDescription,
        public readonly array $makeupAnalysis,
        public readonly array $hairAnalysis,
        public readonly array $skinAnalysis,
        /** @var \App\Data\DTO\AI\Constructors\RecommendedProductDTO[] */
        #[DataCollectionOf(RecommendedProductDTO::class)]
        public readonly array $recommendedProducts,
        /** @var \App\Data\DTO\AI\Constructors\RecommendedServiceDTO[] */
        #[DataCollectionOf(RecommendedServiceDTO::class)]
        public readonly array $recommendedServices,
        public readonly int $totalCost,
        public readonly string $correlationId,
    ) {
    }
}
