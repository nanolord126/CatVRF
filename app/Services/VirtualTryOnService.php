<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use App\Domains\Shared\Material\Services\MaterialAllergyService;
use App\Models\Product3DAsset;
use App\Models\UserBodyProfile;
use App\Models\VirtualTryOnAsset;
use App\Models\VirtualTryOnResult;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use Illuminate\Config\Repository;

final readonly class VirtualTryOnService
{
    use WithAuditLogging;
    private const CACHE_TTL_MINUTES = 30;
    private const MAX_QUEUE_SIZE = 100;
    private const MAX_PROCESSING_TIME_SECONDS = 300;
    private const THUMBNAIL_SIZE = 400;
    private const SCENE_THUMBNAIL_WIDTH = 800;
    private const SCENE_THUMBNAIL_HEIGHT = 600;

    public function __construct(
        private readonly MaterialAllergyService $allergyService,
        private readonly SizeRecommendationService $sizeService,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
        private readonly FilesystemManager $storage,
        private readonly Repository $config,
        private readonly AuditService $auditService,
    ) {}

    public function generateTryOn(
        int $userId,
        int $productId,
        string $productType,
        ?int $variantId = null,
        string $mode = VirtualTryOnResult::MODE_2D,
        array $options = []
    ): array {
        $startTime = microtime(true);

        try {
            $userProfile = $this->getOrFetchUserProfile($userId);
            if (!$userProfile) {
                return [
                    'success' => false,
                    'error' => 'User body profile not found',
                    'requires_profile' => true,
                ];
            }

            $product = $this->fetchProduct($productId, $productType);
            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'Product not found',
                ];
            }

            $variant = $variantId ? $this->fetchVariant($variantId, $productType) : null;

            $compatibilityCheck = $this->checkProductCompatibility($product, $userProfile, $productType);
            if (!$compatibilityCheck['is_compatible']) {
                return [
                    'success' => false,
                    'error' => 'Product not compatible with user profile',
                    'allergy_conflicts' => $compatibilityCheck['conflicts'] ?? [],
                    'warnings' => $compatibilityCheck['warnings'] ?? [],
                ];
            }

            $tryOnAsset = $this->getOrCreateTryOnAsset($product, $variant, $productType);
            if (!$tryOnAsset || $tryOnAsset->status !== VirtualTryOnAsset::STATUS_COMPLETED) {
                return [
                    'success' => false,
                    'error' => 'Try-on asset not ready',
                    'asset_status' => $tryOnAsset?->status ?? 'not_found',
                ];
            }

            $sizeRecommendation = $this->sizeService->recommendSizeForProduct($userId, $productId, $productType);

            $result = $this->processTryOnGeneration(
                $userId,
                $userProfile,
                $product,
                $variant,
                $tryOnAsset,
                $mode,
                $options,
                $sizeRecommendation
            );

            $processingTime = round((microtime(true) - $startTime) * 1000);

            return [
                'success' => true,
                'result_id' => $result['id'] ?? null,
                'result_url' => $result['result_url'] ?? null,
                'thumbnail_url' => $result['thumbnail_url'] ?? null,
                'mode' => $mode,
                'confidence' => $result['confidence'] ?? 0.0,
                'quality' => $result['quality'] ?? 0.0,
                'fit_score' => $result['fit_score'] ?? 0.0,
                'size_recommendation' => $sizeRecommendation['recommended_size'] ?? null,
                'processing_time_ms' => $processingTime,
                'warnings' => $compatibilityCheck['warnings'] ?? [],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Virtual try-on generation failed', [
                'user_id' => $userId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate try-on',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    public function generateTryOnAsync(
        int $userId,
        int $productId,
        string $productType,
        ?int $variantId = null,
        string $mode = VirtualTryOnResult::MODE_2D,
        array $options = []
    ): array {
        $queueKey = "try_on_queue:{$userId}";

        if ($this->cache->get($queueKey, 0) >= self::MAX_QUEUE_SIZE) {
            return [
                'success' => false,
                'error' => 'Queue limit reached',
            ];
        }

        $this->cache->increment($queueKey);

        $jobId = (string) Str::uuid();

        dispatch(new \App\Jobs\GenerateVirtualTryOnJob(
            $userId,
            $productId,
            $productType,
            $variantId,
            $mode,
            $options,
            $jobId,
        ))->onQueue('virtual_try_on');

        return [
            'success' => true,
            'job_id' => $jobId,
            'status' => 'queued',
            'estimated_time_seconds' => $this->estimateProcessingTime($mode),
        ];
    }

    public function getTryOnResult(int $resultId): ?array
    {
        $result = VirtualTryOnResult::with(['userProfile', 'product', 'variant'])->find($resultId);

        if (!$result) {
            return null;
        }

        return [
            'id' => $result->id,
            'uuid' => $result->uuid,
            'user_id' => $result->user_id,
            'mode' => $result->mode,
            'result_url' => $result->result_url,
            'thumbnail_url' => $result->thumbnail_url,
            'confidence_score' => $result->confidence_score,
            'quality_score' => $result->quality_score,
            'fit_score' => $result->fit_score,
            'size_recommendation' => $result->size_recommendation,
            'size_accuracy' => $result->size_accuracy,
            'color_accuracy' => $result->color_accuracy,
            'texture_quality' => $result->texture_quality,
            'processing_time_ms' => $result->processing_time_ms,
            'is_saved' => $result->is_saved,
            'is_shared' => $result->is_shared,
            'share_token' => $result->share_token,
            'created_at' => $result->created_at->toISOString(),
            'product' => [
                'id' => $result->product_id,
                'name' => $result->product?->name ?? 'Unknown',
                'image' => $result->product?->main_image ?? null,
            ],
        ];
    }

    public function getUserTryOnHistory(int $userId, int $limit = 20, int $offset = 0): array
    {
        $cacheKey = "user_try_on_history:{$userId}:{$limit}:{$offset}";

        return $this->cache->remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($userId, $limit, $offset) {
            $results = VirtualTryOnResult::where('user_id', $userId)
                ->with(['product', 'variant'])
                ->orderByDesc('created_at')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $total = VirtualTryOnResult::where('user_id', $userId)->count();

            return [
                'results' => $results->map(fn ($r) => $this->getTryOnResult($r->id))->filter()->values(),
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total,
            ];
        });
    }

    public function saveTryOnResult(int $userId, int $resultId): bool
    {
        $result = VirtualTryOnResult::where('id', $resultId)
            ->where('user_id', $userId)
            ->first();

        if (!$result) {
            return false;
        }

        $result->saveResult();
        return true;
    }

    public function deleteTryOnResult(int $userId, int $resultId): bool
    {
        $result = VirtualTryOnResult::where('id', $resultId)
            ->where('user_id', $userId)
            ->first();

        if (!$result) {
            return false;
        }

        $result->delete();
        return true;
    }

    public function shareTryOnResult(int $userId, int $resultId): ?string
    {
        $result = VirtualTryOnResult::where('id', $resultId)
            ->where('user_id', $userId)
            ->first();

        if (!$result) {
            return null;
        }

        return $result->share();
    }

    public function getSharedTryOnResult(string $shareToken): ?array
    {
        $result = VirtualTryOnResult::where('share_token', $shareToken)
            ->where('is_shared', true)
            ->first();

        if (!$result) {
            return null;
        }

        $result->incrementViewCount();

        return $this->getTryOnResult($result->id);
    }

    private function getOrFetchUserProfile(int $userId): ?UserBodyProfile
    {
        return $this->cache->remember("user_profile:{$userId}", now()->addHours(24), function () use ($userId) {
            return UserBodyProfile::where('user_id', $userId)->first();
        });
    }

    private function fetchProduct(int $productId, string $productType): ?object
    {
        return match($productType) {
            'fashion', 'clothing' => \App\Domains\Fashion\Models\FashionProduct::find($productId),
            'footwear', 'shoes' => \App\Domains\Footwear\Models\FootwearProduct::find($productId),
            default => null,
        };
    }

    private function fetchVariant(int $variantId, string $productType): ?object
    {
        return match($productType) {
            'fashion', 'clothing' => \App\Domains\Fashion\Models\FashionProductVariant::find($variantId),
            'footwear', 'shoes' => \App\Domains\Footwear\Models\FootwearVariant::find($variantId),
            default => null,
        };
    }

    private function checkProductCompatibility(object $product, UserBodyProfile $userProfile, string $productType): array
    {
        $materials = $product->materials ?? collect();

        if ($materials->isEmpty()) {
            return [
                'is_compatible' => true,
                'warnings' => [
                    'No material information available for allergy check',
                ],
            ];
        }

        $userAllergies = $this->getUserAllergies($userProfile->user_id);
        $compatibility = $this->allergyService->checkProductCompatibility(
            $product,
            $userAllergies,
            $productType
        );

        return $compatibility;
    }

    private function getUserAllergies(int $userId): array
    {
        return $this->cache->remember("user_allergies:{$userId}", now()->addDays(7), function () use ($userId) {
            $allergyService = app(\Modules\Contraindications\Application\Services\ContraindicationService::class);
            $userAllergies = $allergyService->getUserAllergies($userId);

            return $userAllergies->pluck('name')->toArray();
        });
    }

    private function getOrCreateTryOnAsset(object $product, ?object $variant, string $productType): ?VirtualTryOnAsset
    {
        $asset = VirtualTryOnAsset::where('product_id', $product->id)
            ->where('product_type', get_class($product))
            ->where('status', VirtualTryOnAsset::STATUS_COMPLETED)
            ->first();

        if ($asset) {
            return $asset;
        }

        $asset = VirtualTryOnAsset::create([
            'product_id' => $product->id,
            'product_type' => get_class($product),
            'variant_id' => $variant?->id,
            'variant_type' => $variant ? get_class($variant) : null,
            'type' => $this->determineAssetType($productType),
            'status' => VirtualTryOnAsset::STATUS_PENDING,
            'tenant_id' => tenant()->id,
        ]);

        dispatch(new \App\Jobs\GenerateTryOnAssetJob($asset->id))->onQueue('try_on_assets');

        return $asset;
    }

    private function determineAssetType(string $productType): string
    {
        return match($productType) {
            'fashion', 'clothing' => VirtualTryOnAsset::TYPE_CLOTHING_FULL_BODY,
            'footwear', 'shoes' => VirtualTryOnAsset::TYPE_FOOTWEAR,
            default => VirtualTryOnAsset::TYPE_ACCESSORY,
        };
    }

    private function processTryOnGeneration(
        int $userId,
        UserBodyProfile $userProfile,
        object $product,
        ?object $variant,
        VirtualTryOnAsset $tryOnAsset,
        string $mode,
        array $options,
        array $sizeRecommendation
    ): array {
        $startTime = microtime(true);

        $result = match($mode) {
            VirtualTryOnResult::MODE_2D => $this->generate2DTryOn($userProfile, $tryOnAsset, $options),
            VirtualTryOnResult::MODE_AR => $this->generateARTryOn($userProfile, $tryOnAsset, $options),
            VirtualTryOnResult::MODE_3D => $this->generate3DTryOn($userProfile, $tryOnAsset, $options),
            default => $this->generate2DTryOn($userProfile, $tryOnAsset, $options),
        };

        $processingTime = round((microtime(true) - $startTime) * 1000);

        $virtualTryOnResult = VirtualTryOnResult::create([
            'user_id' => $userId,
            'user_profile_id' => $userProfile->id,
            'user_profile_type' => get_class($userProfile),
            'product_id' => $product->id,
            'product_type' => get_class($product),
            'variant_id' => $variant?->id,
            'variant_type' => $variant ? get_class($variant) : null,
            'try_on_asset_id' => $tryOnAsset->id,
            'mode' => $mode,
            'result_image_url' => $result['image_url'] ?? null,
            'result_video_url' => $result['video_url'] ?? null,
            'result_3d_scene_url' => $result['scene_url'] ?? null,
            'thumbnail_url' => $result['thumbnail_url'] ?? null,
            'pose' => $options['pose'] ?? 'default',
            'lighting' => $options['lighting'] ?? 'natural',
            'background' => $options['background'] ?? 'studio',
            'camera_angle' => $options['camera_angle'] ?? 'front',
            'confidence_score' => $result['confidence'] ?? 0.85,
            'quality_score' => $result['quality'] ?? 0.80,
            'fit_score' => $result['fit_score'] ?? 0.75,
            'size_recommendation' => $sizeRecommendation['recommended_size'] ?? null,
            'size_accuracy' => $result['size_accuracy'] ?? 'unknown',
            'color_accuracy' => $result['color_accuracy'] ?? 0.90,
            'texture_quality' => $result['texture_quality'] ?? 0.85,
            'processing_time_ms' => $processingTime,
            'generation_method' => $result['method'] ?? 'ai_diffusion',
            'is_saved' => $options['auto_save'] ?? false,
            'tenant_id' => tenant()->id,
        ]);

        return [
            'id' => $virtualTryOnResult->id,
            'result_url' => $virtualTryOnResult->result_url,
            'thumbnail_url' => $virtualTryOnResult->thumbnail_url,
            'confidence' => $virtualTryOnResult->confidence_score,
            'quality' => $virtualTryOnResult->quality_score,
            'fit_score' => $virtualTryOnResult->fit_score,
        ];
    }

    private function generate2DTryOn(UserBodyProfile $userProfile, VirtualTryOnAsset $asset, array $options): array
    {
        $modelService = app(\App\Services\AI\TryOnDiffusionService::class);

        $inputImage = $userProfile->profile_photo_processed_url ?? $userProfile->profile_photo_url;
        if (!$inputImage) {
            throw new \Exception('User profile photo required for 2D try-on');
        }

        $result = $modelService->generate([
            'user_image' => $inputImage,
            'garment_image' => $asset->model_2d_url,
            'segmentation_mask' => $asset->segmentation_mask,
            'pose' => $options['pose'] ?? 'default',
            'preserve_hair' => $options['preserve_hair'] ?? true,
            'preserve_face' => $options['preserve_face'] ?? true,
        ]);

        $imageUrl = $this->storeGeneratedImage($result['image'], 'try_on_2d');

        return [
            'image_url' => $imageUrl,
            'thumbnail_url' => $this->generateThumbnail($imageUrl),
            'confidence' => $result['confidence'] ?? 0.85,
            'quality' => $result['quality'] ?? 0.80,
            'fit_score' => $this->calculate2DFitScore($userProfile, $asset),
            'size_accuracy' => $this->estimateSizeAccuracy($userProfile, $asset),
            'color_accuracy' => 0.90,
            'texture_quality' => 0.85,
            'method' => 'ai_diffusion',
        ];
    }

    private function generateARTryOn(UserBodyProfile $userProfile, VirtualTryOnAsset $asset, array $options): array
    {
        $result = $this->generate2DTryOn($userProfile, $asset, $options);

        $result['video_url'] = $this->generateARVideo($result['image_url'], $asset);
        $result['method'] = 'ai_diffusion_ar';

        return $result;
    }

    private function generate3DTryOn(UserBodyProfile $userProfile, VirtualTryOnAsset $asset, array $options): array
    {
        if (!$asset->model_3d_url) {
            throw new \Exception('3D model not available for this product');
        }

        $sceneService = app(\App\Services\3D\ThreeJSSceneService::class);

        $sceneUrl = $sceneService->generateScene([
            'avatar_url' => $userProfile->profile_photo_3d_avatar_url,
            'garment_url' => $asset->model_3d_url,
            'body_measurements' => [
                'height' => $userProfile->height_cm,
                'chest' => $userProfile->chest_circumference_cm,
                'waist' => $userProfile->waist_circumference_cm,
                'hips' => $userProfile->hip_circumference_cm,
            ],
            'pose' => $options['pose'] ?? 'default',
            'lighting' => $options['lighting'] ?? 'studio',
            'background' => $options['background'] ?? 'neutral',
        ]);

        return [
            'scene_url' => $sceneUrl,
            'thumbnail_url' => $this->generateSceneThumbnail($sceneUrl),
            'confidence' => 0.90,
            'quality' => 0.95,
            'fit_score' => $this->calculate3DFitScore($userProfile, $asset),
            'size_accuracy' => 'high',
            'color_accuracy' => 0.95,
            'texture_quality' => 0.90,
            'method' => '3d_rendering',
        ];
    }

    private function storeGeneratedImage(string $imageData, string $prefix): string
    {
        $filename = "{$prefix}/" . Str::uuid() . '.png';
        $this->storage->disk('cdn')->put($filename, base64_decode($imageData));
        return $this->storage->disk('cdn')->url($filename);
    }

    private function generateThumbnail(string $imageUrl): string
    {
        $imageService = app(\App\Services\Image\ImageOptimizationService::class);
        return $imageService->generateThumbnail($imageUrl, self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
    }

    private function generateARVideo(string $imageUrl, VirtualTryOnAsset $asset): string
    {
        $videoService = app(\App\Services\Video\ARVideoGeneratorService::class);
        return $videoService->generateFromImage($imageUrl, $asset->animation_presets ?? []);
    }

    private function generateSceneThumbnail(string $sceneUrl): string
    {
        $thumbnailService = app(\App\Services\3D\SceneThumbnailService::class);
        return $thumbnailService->generate($sceneUrl, self::SCENE_THUMBNAIL_WIDTH, self::SCENE_THUMBNAIL_HEIGHT);
    }

    private function calculate2DFitScore(UserBodyProfile $userProfile, VirtualTryOnAsset $asset): float
    {
        $score = 0.75;

        if ($asset->isCompatibleWithBodyType($userProfile->body_type)) {
            $score += 0.10;
        }

        if ($userProfile->is_verified) {
            $score += 0.10;
        }

        return min(0.99, $score);
    }

    private function calculate3DFitScore(UserBodyProfile $userProfile, VirtualTryOnAsset $asset): float
    {
        $score = 0.85;

        if ($asset->is_rigged) {
            $score += 0.10;
        }

        if ($asset->isCompatibleWithBodyType($userProfile->body_type)) {
            $score += 0.05;
        }

        return min(0.99, $score);
    }

    private function estimateSizeAccuracy(UserBodyProfile $userProfile, VirtualTryOnAsset $asset): string
    {
        if ($asset->isCompatibleWithBodyType($userProfile->body_type)) {
            return 'high';
        }

        return 'moderate';
    }

    private function estimateProcessingTime(string $mode): int
    {
        return match($mode) {
            VirtualTryOnResult::MODE_2D => 15,
            VirtualTryOnResult::MODE_AR => 30,
            VirtualTryOnResult::MODE_3D => 45,
            default => 20,
        };
    }

    public function clearUserCache(int $userId): void
    {
        $this->cache->forget("user_profile:{$userId}");
        $this->cache->forget("user_allergies:{$userId}");
        $this->cache->tags(["user_try_on_history:{$userId}"])->flush();
    }
}
