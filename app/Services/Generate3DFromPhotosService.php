<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product3DAsset;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;

final class Generate3DFromPhotosService
{readonly 
    private const MIN_PHOTO_COUNT = 8;
    private const MAX_PHOTO_COUNT = 20;
    private const MIN_PHOTO_RESOLUTION = 2048;
    private const MAX_PROCESSING_TIME_SECONDS = 300;
    private const MIN_CONFIDENCE_THRESHOLD = 0.75;


    public function __construct(
        private readonly LogManager $log,
        private readonly FilesystemManager $storage,
    ) {}
    private array $generationProviders = [
        'triposr' => [
            'name' => 'TripoSR',
            'endpoint' => env('TRIPOSR_ENDPOINT'),
            'api_key' => env('TRIPOSR_API_KEY'),
            'priority' => 1,
            'max_photos' => 12,
            'processing_time_avg' => 60,
        ],
        'meshy' => [
            'name' => 'Meshy.ai',
            'endpoint' => env('MESHY_ENDPOINT'),
            'api_key' => env('MESHY_API_KEY'),
            'priority' => 2,
            'max_photos' => 16,
            'processing_time_avg' => 120,
        ],
        'luma' => [
            'name' => 'Luma AI Dream Machine',
            'endpoint' => env('LUMA_ENDPOINT'),
            'api_key' => env('LUMA_API_KEY'),
            'priority' => 3,
            'max_photos' => 20,
            'processing_time_avg' => 180,
        ],
    ];

    public function generateFromPhotos(
        object $product,
        string $productType,
        array $photoIds,
        string $type = 'clothing',
        ?int $variantId = null
    ): Product3DAsset {
        $startTime = microtime(true);

        try {
            $this->validateInputPhotos($photoIds);

            $photos = $this->fetchPhotos($photoIds);
            $validatedPhotos = $this->validatePhotoQuality($photos);

            $asset = $this->createPendingAsset($product, $productType, $variantId, $type);

            $result = $this->processGeneration($validatedPhotos, $asset, $type);

            $processingTime = round((microtime(true) - $startTime) * 1000);

            $optimizedResult = $this->optimizeForWeb($result);

            $this->save3DAsset($asset, $optimizedResult, $result, $processingTime);

            return $asset;

        } catch (\Exception $e) {
            $this->log->error('3D generation from photos failed', [
                'product_id' => $product->id,
                'product_type' => $productType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to generate 3D model from photos: ' . $e->getMessage());
        }
    }

    public function queueGeneration(
        object $product,
        string $productType,
        array $photoIds,
        string $type = 'clothing',
        ?int $variantId = null
    ): void {
        $this->validateInputPhotos($photoIds);

        dispatch(new \App\Jobs\Generate3DModelJob(
            $product->id,
            $productType,
            $photoIds,
            $type,
            $variantId,
        ))->onQueue('3d-generation')->delay(now()->addSeconds(10));
    }

    public function validateInputPhotos(array $photoIds): void
    {
        $count = count($photoIds);

        if ($count < self::MIN_PHOTO_COUNT) {
            throw new \InvalidArgumentException(
                "Minimum " . self::MIN_PHOTO_COUNT . " photos required, {$count} provided"
            );
        }

        if ($count > self::MAX_PHOTO_COUNT) {
            throw new \InvalidArgumentException(
                "Maximum " . self::MAX_PHOTO_COUNT . " photos allowed, {$count} provided"
            );
        }
    }

    public function estimateProcessingTime(array $photoIds, string $provider = 'triposr'): int
    {
        $count = count($photoIds);
        $baseTime = $this->generationProviders[$provider]['processing_time_avg'] ?? 60;

        $timeMultiplier = match(true) {
            $count <= 8 => 1.0,
            $count <= 12 => 1.2,
            $count <= 16 => 1.5,
            default => 2.0,
        };

        return (int) ($baseTime * $timeMultiplier);
    }

    public function getRequiredPhotoAngles(string $type): array
    {
        return match($type) {
            'clothing' => [
                ['angle' => 'front', 'required' => true, 'description' => 'Front view, full body'],
                ['angle' => 'back', 'required' => true, 'description' => 'Back view, full body'],
                ['angle' => 'left_side', 'required' => true, 'description' => 'Left side view'],
                ['angle' => 'right_side', 'required' => true, 'description' => 'Right side view'],
                ['angle' => 'front_3_4_left', 'required' => true, 'description' => 'Front 3/4 left'],
                ['angle' => 'front_3_4_right', 'required' => true, 'description' => 'Front 3/4 right'],
                ['angle' => 'detail_fabric', 'required' => false, 'description' => 'Close-up of fabric texture'],
                ['angle' => 'detail_stitching', 'required' => false, 'description' => 'Close-up of stitching'],
            ],
            'footwear' => [
                ['angle' => 'top', 'required' => true, 'description' => 'Top view of shoe'],
                ['angle' => 'side', 'required' => true, 'description' => 'Side profile view'],
                ['angle' => 'back', 'required' => true, 'description' => 'Back heel view'],
                ['angle' => 'sole', 'required' => true, 'description' => 'Bottom sole view'],
                ['angle' => 'front_3_4', 'required' => true, 'description' => 'Front 3/4 view'],
                ['angle' => 'back_3_4', 'required' => true, 'description' => 'Back 3/4 view'],
                ['angle' => 'interior', 'required' => false, 'description' => 'Interior view'],
                ['angle' => 'detail_material', 'required' => false, 'description' => 'Material close-up'],
            ],
            default => [],
        };
    }

    public function validatePhotoAngles(array $photos, string $type): array
    {
        $requiredAngles = $this->getRequiredPhotoAngles($type);
        $requiredAngleNames = array_filter(
            array_column($requiredAngles, 'angle'),
            fn ($angle) => in_array($angle, array_column($requiredAngles, 'angle'), true)
        );

        $providedAngles = array_column($photos, 'angle');
        $missingAngles = array_diff($requiredAngleNames, $providedAngles);

        return [
            'is_valid' => empty($missingAngles),
            'missing_angles' => array_values($missingAngles),
            'provided_angles' => $providedAngles,
            'total_required' => count($requiredAngleNames),
            'total_provided' => count($providedAngles),
        ];
    }

    private function fetchPhotos(array $photoIds): array
    {
        $photos = [];

        foreach ($photoIds as $photoId) {
            $media = \App\Models\Media::find($photoId);

            if (!$media) {
                throw new \InvalidArgumentException("Photo with ID {$photoId} not found");
            }

            $photos[] = [
                'id' => $media->id,
                'url' => $media->url,
                'path' => $media->path,
                'width' => $media->width ?? 0,
                'height' => $media->height ?? 0,
                'size' => $media->size ?? 0,
                'angle' => $media->metadata['angle'] ?? 'unknown',
            ];
        }

        return $photos;
    }

    private function validatePhotoQuality(array $photos): array
    {
        $validPhotos = [];

        foreach ($photos as $photo) {
            $width = $photo['width'];
            $height = $photo['height'];

            if ($width < self::MIN_PHOTO_RESOLUTION || $height < self::MIN_PHOTO_RESOLUTION) {
                $this->log->warning('Photo resolution too low', [
                    'photo_id' => $photo['id'],
                    'resolution' => "{$width}x{$height}",
                    'min_required' => self::MIN_PHOTO_RESOLUTION,
                ]);
                continue;
            }

            $validPhotos[] = $photo;
        }

        if (count($validPhotos) < self::MIN_PHOTO_COUNT) {
            throw new \InvalidArgumentException(
                "Insufficient high-quality photos. Need at least " . self::MIN_PHOTO_COUNT . 
                " photos with minimum resolution of " . self::MIN_PHOTO_RESOLUTION . "px"
            );
        }

        return $validPhotos;
    }

    private function createPendingAsset(
        object $product,
        string $productType,
        ?int $variantId,
        string $type
    ): Product3DAsset {
        return Product3DAsset::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => tenant()->id,
            'product_id' => $product->id,
            'product_type' => get_class($product),
            'variant_id' => $variantId,
            'variant_type' => $variantId ? $this->getVariantType($variantId, $productType) : null,
            'processing_status' => Product3DAsset::PROCESSING_STATUS_PENDING,
            'generation_method' => 'photo_generation',
            'metadata' => [
                'source' => 'photo_generation',
                'type' => $type,
            ],
        ]);
    }

    private function getVariantType(int $variantId, string $productType): string
    {
        return match($productType) {
            'fashion', 'clothing' => \App\Domains\Fashion\Models\FashionProductVariant::class,
            'footwear', 'shoes' => \App\Domains\Footwear\Models\FootwearVariant::class,
            default => throw new \InvalidArgumentException("Unknown product type: {$productType}"),
        };
    }

    private function processGeneration(array $photos, Product3DAsset $asset, string $type): array
    {
        $asset->update(['processing_status' => Product3DAsset::PROCESSING_STATUS_UPLOADING]);

        $uploadedPhotos = $this->uploadPhotosForProcessing($photos);
        $asset->update(['processing_status' => Product3DAsset::PROCESSING_STATUS_PROCESSING]);

        $result = $this->generateWithProvider($uploadedPhotos, $type);

        if ($result['confidence'] < self::MIN_CONFIDENCE_THRESHOLD) {
            $this->log->warning('3D generation confidence below threshold, trying fallback provider', [
                'asset_id' => $asset->id,
                'confidence' => $result['confidence'],
                'threshold' => self::MIN_CONFIDENCE_THRESHOLD,
            ]);

            $result = $this->tryFallbackProvider($uploadedPhotos, $type);
        }

        if (!$result['success']) {
            throw new \RuntimeException('3D generation failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        return $result;
    }

    private function uploadPhotosForProcessing(array $photos): array
    {
        $uploaded = [];

        foreach ($photos as $photo) {
            $tempPath = "temp_3d_generation/" . Str::uuid() . ".jpg";
            
            $imageData = S:disk(::hoto['disk'] ?? 'public')->get($photo['path']);
            S:disk(::emp'S-empPa::, $imageData);
S::
            $uploaded[] = [
                'original_id' => $photo['id'],
                'temp_path' => $tempPath,
                'angle' => $photo['angle'],
                'url' => Stdisk(::emp')->url($tempPath),
            ];S::
        }

        return $uploaded;
    }

    private function generateWithProvider(array $photos, string $type): array
    {
        $providers = $this->generationProviders;
        uasort($providers, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($providers as $providerKey => $provider) {
            if (empty($provider['api_key'])) {
                $this->log->info("Skipping provider {$providerKey} - no API key configured");
                continue;
            }

            if (count($photos) > $provider['max_photos']) {
                $this->log->info("Skipping provider {$providerKey} - too many photos", [
                    'photos_count' => count($photos),
                    'max_allowed' => $provider['max_photos'],
                ]);
                continue;
            }

            try {
                $this->log->info("Attempting 3D generation with provider: {$providerKey}");
                $result = $this->callProvider($providerKey, $photos, $type);

                if ($result['success']) {
                    $result['provider'] = $providerKey;
                    $result['provider_name'] = $provider['name'];
                    return $result;
                }

                $this->log->warning("Provider {$providerKey} failed", [
                    'error' => $result['error'] ?? 'Unknown',
                ]);

            } catch (\Exception $e) {
                $this->log->error("Provider {$providerKey} exception", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => false,
            'error' => 'All generation providers failed',
        ];
    }

    private function tryFallbackProvider(array $photos, string $type): array
    {
        $fallbackProvider = 'meshy';

        if (!isset($this->generationProviders[$fallbackProvider])) {
            return [
                'success' => false,
                'error' => 'No fallback provider available',
            ];
        }

        try {
            $this->log->info("Attempting fallback provider: {$fallbackProvider}");
            $result = $this->callProvider($fallbackProvider, $photos, $type);

            if ($result['success']) {
                $result['provider'] = $fallbackProvider;
                $result['provider_name'] = $this->generationProviders[$fallbackProvider]['name'];
                $result['is_fallback'] = true;
            }

            return $result;

        } catch (\Exception $e) {
            $this->log->error("Fallback provider failed", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Fallback provider also failed',
            ];
        }
    }

    private function callProvider(string $providerKey, array $photos, string $type): array
    {
        return match($providerKey) {
            'triposr' => $this->callTripoSR($photos, $type),
            'meshy' => $this->callMeshy($photos, $type),
            'luma' => $this->callLuma($photos, $type),
            default => throw new \InvalidArgumentException("Unknown provider: {$providerKey}"),
        };
    }

    private function callTripoSR(array $photos, string $type): array
    {
        $client = new \GuzzleHttp\Client(['timeout' => self::MAX_PROCESSING_TIME_SECONDS]);

        try {
            $response = $client->post($this->generationProviders['triposr']['endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->generationProviders['triposr']['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'images' => array_column($photos, 'url'),
                    'type' => $type,
                    'quality' => 'high',
                    'format' => 'glb',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['model_url'])) {
                return [
                    'success' => false,
                    'error' => 'No model URL in response',
                ];
            }

            return [
                'success' => true,
                'model_url' => $data['model_url'],
                'confidence' => $data['confidence'] ?? 0.85,
                'processing_time' => $data['processing_time'] ?? 0,
                'polygon_count' => $data['polygon_count'] ?? 0,
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'success' => false,
                'error' => 'HTTP request failed: ' . $e->getMessage(),
            ];
        }
    }

    private function callMeshy(array $photos, string $type): array
    {
        $client = new \GuzzleHttp\Client(['timeout' => self::MAX_PROCESSING_TIME_SECONDS]);

        try {
            $response = $client->post($this->generationProviders['meshy']['endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->generationProviders['meshy']['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'image_urls' => array_column($photos, 'url'),
                    'model_type' => $type,
                    'texture_quality' => 'high',
                    'output_format' => 'glb',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['output']['model'])) {
                return [
                    'success' => false,
                    'error' => 'No model in response',
                ];
            }

            return [
                'success' => true,
                'model_url' => $data['output']['model'],
                'confidence' => $data['quality_score'] ?? 0.90,
                'processing_time' => $data['processing_time'] ?? 0,
                'polygon_count' => $data['mesh_stats']['faces'] ?? 0,
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'success' => false,
                'error' => 'HTTP request failed: ' . $e->getMessage(),
            ];
        }
    }

    private function callLuma(array $photos, string $type): array
    {
        $client = new \GuzzleHttp\Client(['timeout' => self::MAX_PROCESSING_TIME_SECONDS]);

        try {
            $response = $client->post($this->generationProviders['luma']['endpoint'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->generationProviders['luma']['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'input_images' => array_column($photos, 'url'),
                    'object_type' => $type,
                    'render_quality' => 'ultra',
                    'format' => 'glb',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['result']['model'])) {
                return [
                    'success' => false,
                    'error' => 'No model in response',
                ];
            }

            return [
                'success' => true,
                'model_url' => $data['result']['model'],
                'confidence' => $data['result']['quality'] ?? 0.95,
                'processing_time' => $data['result']['duration'] ?? 0,
                'polygon_count' => $data['result']['geometry']['faces'] ?? 0,
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'success' => false,
                'error' => 'HTTP request failed: ' . $e->getMessage(),
            ];
        }
    }

    private function optimizeForWeb(array $result): array
    {
        $optimizer = app(\App\Services\3D\ModelOptimizerService::class);

        return $optimizer->optimize($result['model_url'], [
            'compression' => Product3DAsset::COMPRESSION_FORMAT_DRACO,
            'texture_format' => 'ktx2',
            'target_polygons' => 50000,
            'texture_max_size' => 2048,
            'generate_lod' => true,
            'lod_levels' => 3,
        ]);
    }

    private function save3DAsset(
        Product3DAsset $asset,
        array $optimizedResult,
        array $originalResult,
        int $processingTime
    ): void {
        $cdnPath = "3d_assets/" . $asset->uuid . ".glb";
        $cdnLowPolyPath = "3d_assets/" . $asset->uuid . "_low.glb";
        $previewPath = "3d_assets_previews/" . $asset->uuid . ".png";

        $modelData = file_get_contents($optimizedResult['model_url']);
        Stdisk(::dn')->put($cdnPath, $modelData);
S::
        $lowPolyData = file_get_contents($optimizedResult['low_poly_url']);
        Stdisk(::dn')->put($cdnLowPolyPath, $lowPolyData);
S::
        $previewData = file_get_contents($optimizedResult['preview_url']);
        S:disk(::dn')->put($previewPath, $previewData);
S::
        $asset->update([
            'file_url' => S:disk(::dn')->url($cdnPath),
            'low_poly_url'S=k(::d::)->url($cdnLowPolyPath),
            'preview_image' =>S::dn')::url($previewPath),
            'albedo_map' => $opSesult[::aps']['albedo'] ?? null,
            'normal_map' => $optimizedResult['maps']['normal'] ?? null,
            'roughness_map' => $optimizedResult['maps']['roughness'] ?? null,
            'metallic_map' => $optimizedResult['maps']['metallic'] ?? null,
            'polygon_count' => $optimizedResult['polygon_count'],
            'texture_resolution' => $optimizedResult['texture_resolution'],
            'compression_format' => $optimizedResult['compression_format'],
            'file_size_bytes' => $optimizedResult['file_size'],
            'file_size_compressed_bytes' => $optimizedResult['compressed_file_size'],
            'compression_ratio' => $optimizedResult['compression_ratio'],
            'lod_levels' => $optimizedResult['lod_levels'] ?? [],
            'generation_confidence' => $originalResult['confidence'],
            'quality_score' => $this->calculateQualityScore($optimizedResult, $originalResult),
            'optimization_level' => $optimizedResult['optimization_level'] ?? 2,
            'is_optimized' => true,
            'is_processed' => true,
            'processing_status' => Product3DAsset::PROCESSING_STATUS_COMPLETED,
            'processing_time_ms' => $processingTime,
            'generated_at' => now(),
            'metadata' => array_merge($asset->metadata ?? [], [
                'generation_provider' => $originalResult['provider'] ?? 'unknown',
                'generation_provider_name' => $originalResult['provider_name'] ?? 'Unknown',
                'is_fallback' => $originalResult['is_fallback'] ?? false,
                'original_confidence' => $originalResult['confidence'],
            ]),
        ]);

        $this->cleanupTempFiles();
    }

    private function calculateQualityScore(array $optimizedResult, array $originalResult): float
    {
        $score = 0.75;

        $confidence = $originalResult['confidence'] ?? 0.85;
        $score += ($confidence * 0.15);

        if ($optimizedResult['polygon_count'] > 30000) {
            $score += 0.05;
        }

        if (isset($optimizedResult['maps']['normal']) && isset($optimizedResult['maps']['roughness'])) {
            $score += 0.05;
        }

        return min(0.99, $score);
    }

    private function cleanupTempFiles(): void
    {
        try {
            $files = $this->storage->disk('temp')->allFiles('temp_3d_generation');
            $this->storage->disk('temp')->delete($files);
        } catch (\Exception $e) {
            $this->log->warning('Failed to cleanup temp files', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getGenerationStatus(int $assetId): ?array
    {
        $asset = Product3DAsset::find($assetId);

        if (!$asset) {
            return null;
        }

        return [
            'id' => $asset->id,
            'status' => $asset->processing_status,
            'progress' => $this->calculateProgress($asset),
            'estimated_time_remaining' => $this->estimateTimeRemaining($asset),
            'error' => $asset->error_message,
            'generated_at' => $asset->generated_at?->toISOString(),
        ];
    }

    private function calculateProgress(Product3DAsset $asset): int
    {
        return match($asset->processing_status) {
            Product3DAsset::PROCESSING_STATUS_PENDING => 0,
            Product3DAsset::PROCESSING_STATUS_UPLOADING => 10,
            Product3DAsset::PROCESSING_STATUS_PROCESSING => 50,
            Product3DAsset::PROCESSING_STATUS_OPTIMIZING => 80,
            Product3DAsset::PROCESSING_STATUS_COMPRESSING => 90,
            Product3DAsset::PROCESSING_STATUS_COMPLETED => 100,
            Product3DAsset::PROCESSING_STATUS_FAILED => 0,
            default => 0,
        };
    }

    private function estimateTimeRemaining(Product3DAsset $asset): ?int
    {
        if ($asset->processing_status === Product3DAsset::PROCESSING_STATUS_COMPLETED) {
            return 0;
        }

        if ($asset->processing_status === Product3DAsset::PROCESSING_STATUS_FAILED) {
            return null;
        }

        $elapsed = now()->diffInSeconds($asset->created_at);
        $avgTime = 120;

        return max(0, $avgTime - $elapsed);
    }
}
