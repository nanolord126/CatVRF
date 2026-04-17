<?php declare(strict_types=1);

namespace App\Domains\Fashion\DTOs;

final readonly class FashionStyleAnalysisDto
{
    public function __construct(
        public int $designId,
        public int $userId,
        public int $tenantId,
        public ?int $businessGroupId,
        public array $styleProfile,
        public array $capsuleWardrobe,
        public array $recommendations,
        public array $embeddingVector,
        public string $photoUrl,
        public string $arTryOnUrl,
        public string $threeDModelsUrl,
        public string $correlationId,
    ) {}

    public function toArray(): array
    {
        return [
            'design_id' => $this->designId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'style_profile' => $this->styleProfile,
            'capsule_wardrobe' => $this->capsuleWardrobe,
            'recommendations' => $this->recommendations,
            'embedding_vector' => $this->embeddingVector,
            'photo_url' => $this->photoUrl,
            'ar_try_on_url' => $this->arTryOnUrl,
            'three_d_models_url' => $this->threeDModelsUrl,
            'correlation_id' => $this->correlationId,
        ];
    }
}
