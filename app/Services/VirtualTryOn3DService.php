<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use App\Domains\Shared\Material\Services\MaterialAllergyService;
use App\Models\Product3DAsset;
use App\Models\UserBodyProfile;
use App\Models\VirtualTryOnResult;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use Illuminate\Config\Repository;

final readonly class VirtualTryOn3DService
{
    use WithAuditLogging;
    private const CACHE_TTL_MINUTES = 60;
    private const MAX_SCENE_COMPLEXITY = 50000;
    private const TARGET_FPS = 60;
    private const MAX_TEXTURE_SIZE = 4096;
    private const STANDARD_AVATAR_HEIGHT_CM = 175.0;
    private const DEFAULT_IMAGE_WIDTH = 1920;
    private const DEFAULT_IMAGE_HEIGHT = 1080;
    private const MOBILE_MAX_POLYGONS = 30000;
    private const MOBILE_TEXTURE_SIZE = 2048;
    private const TABLET_MAX_POLYGONS = 50000;
    private const TABLET_TEXTURE_SIZE = 4096;
    private const DESKTOP_MAX_POLYGONS = 100000;

    public function __construct(
        private readonly MaterialAllergyService $allergyService,
        private readonly SizeRecommendationService $sizeService,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
        private readonly FilesystemManager $storage,
        private readonly Repository $config,
        private readonly AuditService $auditService,
    ) {}

    public function getTryOnScene(
        int $userId,
        int $productId,
        string $productType,
        ?int $variantId = null,
        array $options = []
    ): array {
        $cacheKey = "3d_try_on_scene:{$userId}:{$productId}:{$variantId}:" . md5(serialize($options));

        return $this->cache->remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($userId, $productId, $productType, $variantId, $options) {
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

                $asset3D = $this->get3DAsset($product, $variant, $productType);
                if (!$asset3D || $asset3D->processing_status !== Product3DAsset::PROCESSING_STATUS_COMPLETED) {
                    return [
                        'success' => false,
                        'error' => '3D asset not ready',
                        'asset_status' => $asset3D?->processing_status ?? 'not_found',
                    ];
                }

                $compatibilityCheck = $this->checkCompatibility($userProfile, $asset3D);
                if (!$compatibilityCheck['compatible']) {
                    return [
                        'success' => false,
                        'error' => 'Product not compatible with user profile',
                        'reasons' => $compatibilityCheck['reasons'],
                    ];
                }

                $sizeRecommendation = $this->sizeService->recommendSizeForProduct($userId, $productId, $productType);

                $scene = $this->build3DScene($userProfile, $asset3D, $variant, $options);

                return [
                    'success' => true,
                    'scene' => $scene,
                    'avatar_url' => $userProfile->profile_photo_3d_avatar_url ?? $this->generateDefaultAvatar($userProfile),
                    'garment_url' => $asset3D->file_url,
                    'garment_low_poly' => $asset3D->low_poly_url,
                    'material_maps' => $this->getMaterialMaps($asset3D),
                    'lighting' => $options['lighting'] ?? 'studio',
                    'background' => $options['background'] ?? 'neutral',
                    'camera_preset' => $options['camera'] ?? 'default',
                    'animation_presets' => $asset3D->animation_presets ?? [],
                    'recommended_size' => $sizeRecommendation['recommended_size'] ?? null,
                    'allergy_warnings' => $compatibilityCheck['allergy_warnings'] ?? [],
                    'performance' => [
                        'target_fps' => self::TARGET_FPS,
                        'polygon_count' => $asset3D->polygon_count,
                        'texture_resolution' => $asset3D->texture_resolution,
                        'has_lod' => $asset3D->has_lod,
                        'lod_count' => $asset3D->lod_count,
                    ],
                ];

            } catch (\Exception $e) {
                $this->logger->error('3D try-on scene generation failed', [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to generate 3D scene',
                    'details' => $this->config->get('app.debug') ? $e->getMessage() : null,
                ];
            }
        });
    }

    public function generateTryOnFromScene(
        int $userId,
        int $productId,
        string $productType,
        ?int $variantId = null,
        array $sceneOptions = []
    ): array {
        $startTime = microtime(true);

        try {
            $scene = $this->getTryOnScene($userId, $productId, $productType, $variantId, $sceneOptions);

            if (!$scene['success']) {
                return $scene;
            }

            $userProfile = $this->getOrFetchUserProfile($userId);
            $product = $this->fetchProduct($productId, $productType);
            $variant = $variantId ? $this->fetchVariant($variantId, $productType) : null;
            $asset3D = $this->get3DAsset($product, $variant, $productType);

            $renderResult = $this->renderScene($scene, $sceneOptions);

            $processingTime = round((microtime(true) - $startTime) * 1000);

            $result = VirtualTryOnResult::create([
                'user_id' => $userId,
                'user_profile_id' => $userProfile->id,
                'user_profile_type' => get_class($userProfile),
                'product_id' => $product->id,
                'product_type' => get_class($product),
                'variant_id' => $variant?->id,
                'variant_type' => $variant ? get_class($variant) : null,
                'mode' => VirtualTryOnResult::MODE_3D,
                'result_3d_scene_url' => $renderResult['scene_url'],
                'thumbnail_url' => $renderResult['thumbnail_url'],
                'pose' => $sceneOptions['pose'] ?? 'default',
                'lighting' => $sceneOptions['lighting'] ?? 'studio',
                'background' => $sceneOptions['background'] ?? 'neutral',
                'camera_angle' => $sceneOptions['camera'] ?? 'default',
                'confidence_score' => $renderResult['confidence'] ?? 0.90,
                'quality_score' => $renderResult['quality'] ?? 0.95,
                'fit_score' => $this->calculate3DFitScore($userProfile, $asset3D),
                'size_recommendation' => $scene['recommended_size'],
                'size_accuracy' => 'high',
                'color_accuracy' => 0.95,
                'texture_quality' => 0.90,
                'processing_time_ms' => $processingTime,
                'generation_method' => '3d_rendering',
                'is_saved' => $sceneOptions['auto_save'] ?? false,
                'tenant_id' => tenant()->id,
            ]);

            return [
                'success' => true,
                'result_id' => $result->id,
                'result_url' => $result->result_3d_scene_url,
                'thumbnail_url' => $result->thumbnail_url,
                'processing_time_ms' => $processingTime,
            ];

        } catch (\Exception $e) {
            $this->logger->error('3D try-on generation failed', [
                'user_id' => $userId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate 3D try-on',
            ];
        }
    }

    public function getARExperience(
        int $userId,
        int $productId,
        string $productType,
        ?int $variantId = null
    ): array {
        $cacheKey = "ar_experience:{$userId}:{$productId}:{$variantId}";

        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($userId, $productId, $productType, $variantId) {
            $scene = $this->getTryOnScene($userId, $productId, $productType, $variantId, ['mode' => 'ar']);

            if (!$scene['success']) {
                return $scene;
            }

            return [
                'success' => true,
                'ar_config' => [
                    'type' => 'webxr',
                    'mode' => 'ar',
                    'required_features' => ['hit-test', 'anchors'],
                    'optional_features' => ['dom-overlay', 'light-estimation'],
                ],
                'scene' => $scene['scene'],
                'tracking_config' => [
                    'tracking_method' => 'plane-detection',
                    'tracking_quality' => 'high',
                    'max_distance' => 10.0,
                    'min_distance' => 0.5,
                ],
                'ui_config' => [
                    'show_measurements' => true,
                    'show_size_info' => true,
                    'enable_rotation' => true,
                    'enable_scaling' => false,
                    'max_scale' => 1.5,
                    'min_scale' => 0.5,
                ],
            ];
        });
    }

    public function optimizeSceneForDevice(array $scene, string $deviceType): array
    {
        $optimizations = [
            'mobile' => [
                'max_polygons' => self::MOBILE_MAX_POLYGONS,
                'texture_size' => self::MOBILE_TEXTURE_SIZE,
                'enable_shadows' => false,
                'enable_reflections' => false,
                'lod_level' => 2,
            ],
            'tablet' => [
                'max_polygons' => self::TABLET_MAX_POLYGONS,
                'texture_size' => self::TABLET_TEXTURE_SIZE,
                'enable_shadows' => true,
                'enable_reflections' => false,
                'lod_level' => 1,
            ],
            'desktop' => [
                'max_polygons' => self::DESKTOP_MAX_POLYGONS,
                'texture_size' => self::MAX_TEXTURE_SIZE,
                'enable_shadows' => true,
                'enable_reflections' => true,
                'lod_level' => 0,
            ],
        ];

        $config = $optimizations[$deviceType] ?? $optimizations['desktop'];

        $scene['performance']['target_polygons'] = min($scene['performance']['polygon_count'], $config['max_polygons']);
        $scene['performance']['optimized_texture_size'] = min($scene['performance']['texture_resolution'], $config['texture_size']);
        $scene['rendering'] = [
            'enable_shadows' => $config['enable_shadows'],
            'enable_reflections' => $config['enable_reflections'],
            'lod_level' => $config['lod_level'],
        ];

        return $scene;
    }

    public function exportSceneImage(int $resultId, array $options = []): ?string
    {
        $result = VirtualTryOnResult::find($resultId);

        if (!$result || $result->mode !== VirtualTryOnResult::MODE_3D) {
            return null;
        }

        $exportService = app(\App\Services\3D\SceneExportService::class);

        $imageData = $exportService->exportImage($result->result_3d_scene_url, [
            'width' => $options['width'] ?? self::DEFAULT_IMAGE_WIDTH,
            'height' => $options['height'] ?? self::DEFAULT_IMAGE_HEIGHT,
            'quality' => $options['quality'] ?? 95,
            'format' => $options['format'] ?? 'png',
        ]);

        $filename = "try_on_export/{$result->uuid}.png";
        $this->storage->disk('cdn')->put($filename, $imageData);

        return $this->storage->disk('cdn')->url($filename);
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

    private function get3DAsset(object $product, ?object $variant, string $productType): ?Product3DAsset
    {
        $query = Product3DAsset::where('product_id', $product->id)
            ->where('product_type', get_class($product))
            ->completed();

        if ($variant) {
            $query->where('variant_id', $variant->id)
                ->where('variant_type', get_class($variant));
        }

        return $query->first();
    }

    private function checkCompatibility(UserBodyProfile $userProfile, Product3DAsset $asset): array
    {
        $reasons = [];
        $allergyWarnings = [];

        if (!$asset->isCompatibleWithBodyType($userProfile->body_type)) {
            $reasons[] = "Asset not optimized for body type: {$userProfile->body_type}";
        }

        if ($asset->polygon_count > self::MAX_SCENE_COMPLEXITY) {
            $reasons[] = "Scene complexity exceeds recommended limit";
        }

        if (!$userProfile->profile_photo_3d_avatar_url) {
            $reasons[] = "3D avatar not available, using default";
        }

        return [
            'compatible' => empty($reasons),
            'reasons' => $reasons,
            'allergy_warnings' => $allergyWarnings,
        ];
    }

    private function build3DScene(
        UserBodyProfile $userProfile,
        Product3DAsset $asset,
        ?object $variant,
        array $options
    ): array {
        $sceneBuilder = app(\App\Services\3D\ThreeJSSceneBuilder::class);

        $bodyMeasurements = [
            'height_cm' => $userProfile->height_cm,
            'chest_cm' => $userProfile->chest_circumference_cm,
            'waist_cm' => $userProfile->waist_circumference_cm,
            'hip_cm' => $userProfile->hip_circumference_cm,
            'shoulder_width_cm' => $userProfile->shoulder_width_cm,
            'body_type' => $userProfile->body_type,
            'gender' => $userProfile->gender,
        ];

        $sceneConfig = [
            'avatar' => [
                'url' => $userProfile->profile_photo_3d_avatar_url ?? $this->generateDefaultAvatar($userProfile),
                'scale' => $this->calculateAvatarScale($userProfile),
                'position' => [0, 0, 0],
                'rotation' => [0, 0, 0],
            ],
            'garment' => [
                'url' => $asset->file_url,
                'low_poly_url' => $asset->low_poly_url,
                'scale' => $asset->scale_factor ?? 1.0,
                'position' => $asset->pivot_point ?? [0, 0, 0],
                'rotation' => $asset->rotation_offset ?? [0, 0, 0],
                'is_rigged' => $asset->is_rigged,
            ],
            'materials' => $this->getMaterialMaps($asset),
            'lighting' => $this->getLightingConfig($options['lighting'] ?? 'studio'),
            'camera' => $this->getCameraConfig($options['camera'] ?? 'default'),
            'environment' => [
                'background' => $options['background'] ?? 'neutral',
                'ambient_occlusion' => true,
                'tone_mapping' => 'aces',
                'exposure' => 1.0,
            ],
            'animations' => $asset->animation_presets ?? [],
            'pose' => $options['pose'] ?? 'default',
            'performance' => [
                'target_fps' => self::TARGET_FPS,
                'enable_lod' => $asset->has_lod,
                'lod_levels' => $asset->lod_levels ?? [],
            ],
        ];

        if ($variant) {
            $sceneConfig['garment']['color'] = $variant->color ?? null;
            $sceneConfig['garment']['color_code'] = $variant->color_code ?? null;
        }

        return $sceneBuilder->build($sceneConfig, $bodyMeasurements);
    }

    private function getMaterialMaps(Product3DAsset $asset): array
    {
        $maps = [];

        if ($asset->albedo_map) $maps['albedo'] = $asset->albedo_map;
        if ($asset->normal_map) $maps['normal'] = $asset->normal_map;
        if ($asset->roughness_map) $maps['roughness'] = $asset->roughness_map;
        if ($asset->metallic_map) $maps['metallic'] = $asset->metallic_map;
        if ($asset->ao_map) $maps['ao'] = $asset->ao_map;
        if ($asset->emissive_map) $maps['emissive'] = $asset->emissive_map;
        if ($asset->opacity_map) $maps['opacity'] = $asset->opacity_map;
        if ($asset->displacement_map) $maps['displacement'] = $asset->displacement_map;

        return $maps;
    }

    private function getLightingConfig(string $lightingType): array
    {
        return match($lightingType) {
            'studio' => [
                'type' => 'studio',
                'intensity' => 1.0,
                'ambient_intensity' => 0.4,
                'shadows' => true,
                'shadow_quality' => 'high',
            ],
            'outdoor' => [
                'type' => 'outdoor',
                'intensity' => 1.2,
                'ambient_intensity' => 0.6,
                'sun_position' => [45, 90],
                'shadows' => true,
                'shadow_quality' => 'medium',
            ],
            'neutral' => [
                'type' => 'neutral',
                'intensity' => 0.8,
                'ambient_intensity' => 0.5,
                'shadows' => false,
            ],
            default => [
                'type' => 'default',
                'intensity' => 1.0,
                'ambient_intensity' => 0.5,
                'shadows' => true,
            ],
        };
    }

    private function getCameraConfig(string $cameraType): array
    {
        return match($cameraType) {
            'front' => [
                'position' => [0, 1.6, 3],
                'target' => [0, 1, 0],
                'fov' => 45,
            ],
            'side' => [
                'position' => [3, 1.6, 0],
                'target' => [0, 1, 0],
                'fov' => 45,
            ],
            'back' => [
                'position' => [0, 1.6, -3],
                'target' => [0, 1, 0],
                'fov' => 45,
            ],
            'top' => [
                'position' => [0, 4, 0],
                'target' => [0, 0, 0],
                'fov' => 60,
            ],
            default => [
                'position' => [0, 1.6, 2.5],
                'target' => [0, 1, 0],
                'fov' => 50,
            ],
        };
    }

    private function calculateAvatarScale(UserBodyProfile $profile): float
    {
        if ($profile->height_cm) {
            return $profile->height_cm / self::STANDARD_AVATAR_HEIGHT_CM;
        }
        return 1.0;
    }

    private function generateDefaultAvatar(UserBodyProfile $profile): string
    {
        $avatarService = app(\App\Services\3D\AvatarGeneratorService::class);

        return $avatarService->generateDefault([
            'gender' => $profile->gender,
            'height_cm' => $profile->height_cm ?? 175,
            'body_type' => $profile->body_type ?? 'regular',
            'skin_tone' => $profile->skin_tone ?? 'medium',
        ]);
    }

    private function renderScene(array $scene, array $options): array
    {
        $renderer = app(\App\Services\3D\SceneRenderer::class);

        return $renderer->render($scene, [
            'width' => $options['width'] ?? self::DEFAULT_IMAGE_WIDTH,
            'height' => $options['height'] ?? self::DEFAULT_IMAGE_HEIGHT,
            'quality' => $options['quality'] ?? 'high',
            'format' => $options['format'] ?? 'png',
        ]);
    }

    private function calculate3DFitScore(UserBodyProfile $userProfile, Product3DAsset $asset): float
    {
        $score = 0.85;

        if ($asset->is_rigged) {
            $score += 0.10;
        }

        if ($asset->isCompatibleWithBodyType($userProfile->body_type)) {
            $score += 0.05;
        }

        if ($userProfile->is_verified) {
            $score += 0.05;
        }

        return min(0.99, $score);
    }

    public function clearUserCache(int $userId): void
    {
        $this->cache->forget("user_profile:{$userId}");
        $this->cache->tags(["3d_try_on_scene:{$userId}"])->flush();
        $this->cache->tags(["ar_experience:{$userId}"])->flush();
    }
}
