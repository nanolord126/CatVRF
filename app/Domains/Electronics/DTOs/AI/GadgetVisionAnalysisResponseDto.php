<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs\AI;

final readonly class GadgetVisionAnalysisResponseDto
{
    /**
     * @param array<int, array<string, mixed>> $recommendedProducts
     * @param array<string, mixed> $visionAnalysis
     * @param array<string, mixed> $arPreviewUrls
     * @param array<string, mixed> $pricingInfo
     */
    public function __construct(
        public bool $success,
        public string $correlationId,
        public array $visionAnalysis,
        public array $recommendedProducts,
        public array $arPreviewUrls,
        public array $pricingInfo,
        public bool $videoCallAvailable,
        public ?string $videoCallToken = null,
        public ?string $flashSaleOffer = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'correlation_id' => $this->correlationId,
            'vision_analysis' => $this->visionAnalysis,
            'recommended_products' => $this->recommendedProducts,
            'ar_preview_urls' => $this->arPreviewUrls,
            'pricing_info' => $this->pricingInfo,
            'video_call_available' => $this->videoCallAvailable,
            'video_call_token' => $this->videoCallToken,
            'flash_sale_offer' => $this->flashSaleOffer,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            correlationId: $data['correlation_id'] ?? '',
            visionAnalysis: $data['vision_analysis'] ?? [],
            recommendedProducts: $data['recommended_products'] ?? [],
            arPreviewUrls: $data['ar_preview_urls'] ?? [],
            pricingInfo: $data['pricing_info'] ?? [],
            videoCallAvailable: $data['video_call_available'] ?? false,
            videoCallToken: $data['video_call_token'] ?? null,
            flashSaleOffer: $data['flash_sale_offer'] ?? null,
        );
    }
}
