<?php declare(strict_types=1);

namespace App\Domains\Fashion\DTOs;

final readonly class FashionARPreviewDto
{
    public function __construct(
        public int $designId,
        public int $productId,
        public int $userId,
        public int $tenantId,
        public string $arModelUrl,
        public ?string $textureUrl,
        public array $modelViewerConfig,
        public string $correlationId,
    ) {}

    public function toArray(): array
    {
        return [
            'design_id' => $this->designId,
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'ar_model_url' => $this->arModelUrl,
            'texture_url' => $this->textureUrl,
            'model_viewer_config' => $this->modelViewerConfig,
            'correlation_id' => $this->correlationId,
        ];
    }
}
