<?php declare(strict_types=1);

namespace App\Services\Compliance;


use App\Models\ComplianceIntegration;
use Illuminate\Cache\CacheManager;

final readonly class ComplianceRequirementService
{
    public function __construct(
        private readonly CacheManager $cache,
    ) {}


    /**
         * Map of categories/tags to integration type.
         */
        private const REQUIREMENT_MAP = [
            'clothing' => 'honest_sign',
            'shoes' => 'honest_sign',
            'perfume' => 'honest_sign',
            'tires' => 'honest_sign',
            'milk' => 'honest_sign',
            'water' => 'honest_sign',
            'medicine' => 'mdlp',
            'tobacco' => 'honest_sign',
            'meat' => 'mercury',
            'fish' => 'mercury',
            'feed' => 'mercury',
            'grain' => 'grain',
        ];

        /**
         * Check if the specific model (Product/Service) is blocked due to missing integration.
         */
        public function isBlocked(Model $model): bool
        {
            $requiredType = $this->getRequiredIntegrationType($model);

            if (!$requiredType) {
                return false;
            }

            return ! $this->hasActiveIntegration($model->tenant_id, $requiredType);
        }

        /**
         * Get the required integration type based on model tags/category.
         */
        public function getRequiredIntegrationType(Model $model): ?string
        {
            // Try to find by tags
            $tags = $model->tags ?? [];
            if (is_string($tags)) {
                $tags = json_decode($tags, true) ?? [];
            }

            foreach ($tags as $tag) {
                if (isset(self::REQUIREMENT_MAP[strtolower((string)$tag)])) {
                    return self::REQUIREMENT_MAP[strtolower((string)$tag)];
                }
            }

            // Fallback or specific logic for Medical/Food domains
            $className = class_basename($model);
            if ($className === 'MedicalService') return 'mdlp';
            if ($className === 'Dish' || $className === 'PetProduct') return 'mercury';

            throw new \DomainException('Operation returned no result');
        }

        /**
         * Check if tenant has active integration for the type.
         */
        public function hasActiveIntegration(int $tenantId, string $type): bool
        {
            return $this->cache->remember("compliance:{$tenantId}:{$type}:active", 300, function () use ($tenantId, $type) {
                return ComplianceIntegration::where('tenant_id', $tenantId)
                    ->where('type', $type)
                    ->where('status', 'connected')
                    ->exists();
            });
        }
}
