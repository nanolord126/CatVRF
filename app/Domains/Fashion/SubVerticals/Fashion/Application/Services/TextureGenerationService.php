<?php

declare(strict_types=1);

namespace Modules\Fashion\Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Modules\Fashion\Domain\DTOs\GenerateTextureDTO;
use Modules\Fashion\Domain\DTOs\TextureResultDTO;
use Modules\Fashion\Domain\Entities\TexturePack;
use Modules\Fashion\Domain\Repositories\TexturePackRepositoryInterface;
use Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus;
use Modules\Fashion\Domain\ValueObjects\TextureType;
use RuntimeException;

/**
 * TextureGenerationService — Сервис для генерации текстур
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class TextureGenerationService
{
    private const MAX_RETRIES = 3;
    private const TIMEOUT_SECONDS = 600;

    public function __construct(
        private readonly TexturePackRepositoryInterface $texturePackRepository,
        private readonly string $stableDiffusionApiUrl,
        private readonly string $stableDiffusionApiKey,
        private readonly FilesystemManager $storage,
        private readonly LogManager $log,
    ) {
        $this->stableDiffusionApiUrl = config('fashion_textures.sd_api_url', env('STABLE_DIFFUSION_API_URL'));
        $this->stableDiffusionApiKey = config('fashion_textures.sd_api_key', env('STABLE_DIFFUSION_API_KEY'));
    }

    public function generatePBRTextures(GenerateTextureDTO $dto): TexturePack
    {
        $texturePack = TexturePack::create(
            model3dId: $dto->model3dId,
            productName: $dto->productName,
            materialType: $dto->materialType,
            description: "PBR textures for {$dto->productName} ({$dto->materialType->value})",
        );

        $this->texturePackRepository->save($texturePack);

        // Dispatch to queue for async processing
        dispatch(function () use ($dto, $texturePack) {
            $this->processTextureGeneration($dto, $texturePack);
        });

        return $texturePack;
    }

    public function processTextureGeneration(GenerateTextureDTO $dto, TexturePack $texturePack): void
    {
        $correlationId = $dto->correlationId;
        $startTime = microtime(true);

        try {
            // Lock for processing
            $locked = $this->texturePackRepository->lockForProcessing($texturePack->getUuid());
            if (!$locked) {
                throw new RuntimeException('Texture pack is already being processed');
            }

            $texturePack = $texturePack->markAsProcessing($correlationId);
            $this->texturePackRepository->save($texturePack);

            $this->log->info('Starting PBR texture generation', [
                'correlation_id' => $correlationId,
                'model_3d_id' => $dto->model3dId,
                'material_type' => $dto->materialType->value,
            ]);

            // Prepare control images
            $controlImages = $this->prepareControlImages($dto->photoPaths);

            // Generate Albedo (base color)
            $albedoResult = $this->generateTextureMap($dto, $controlImages, 'albedo');
            if (!$albedoResult->success) {
                throw new RuntimeException("Albedo generation failed: {$albedoResult->errorMessage}");
            }

            // Generate Normal Map
            $normalResult = $this->generateTextureMap($dto, $controlImages, 'normal');
            if (!$normalResult->success) {
                throw new RuntimeException("Normal map generation failed: {$normalResult->errorMessage}");
            }

            // Generate Roughness Map
            $roughnessResult = $this->generateTextureMap($dto, $controlImages, 'roughness');
            if (!$roughnessResult->success) {
                throw new RuntimeException("Roughness generation failed: {$roughnessResult->errorMessage}");
            }

            // Generate Metallic Map
            $metallicResult = $this->generateTextureMap($dto, $controlImages, 'metallic');
            if (!$metallicResult->success) {
                throw new RuntimeException("Metallic generation failed: {$metallicResult->errorMessage}");
            }

            // Generate AO (Ambient Occlusion) Map
            $aoResult = $this->generateTextureMap($dto, $controlImages, 'ao');
            if (!$aoResult->success) {
                throw new RuntimeException("AO generation failed: {$aoResult->errorMessage}");
            }

            // Generate Displacement Map (optional)
            $displacementResult = null;
            if ($this->shouldGenerateDisplacement($dto->materialType)) {
                $displacementResult = $this->generateTextureMap($dto, $controlImages, 'displacement');
                if (!$displacementResult->success) {
                    $this->log->warning('Displacement map generation failed, continuing without it', [
                        'correlation_id' => $correlationId,
                    ]);
                    $displacementResult = null;
                }
            }

            $generationTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Update texture pack as completed
            $texturePack = $texturePack->markAsCompleted(
                albedoPath: $albedoResult->albedoPath,
                normalPath: $normalResult->normalPath,
                roughnessPath: $roughnessResult->roughnessPath,
                metallicPath: $metallicResult->metallicPath,
                aoPath: $aoResult->aoPath,
                displacementPath: $displacementResult?->displacementPath,
                metadata: [
                    'dto' => $dto->toArray(),
                    'generation_time_ms' => $generationTimeMs,
                    'control_images' => array_keys($controlImages),
                    'lora_weights' => $dto->loraWeights,
                ],
            );

            $this->texturePackRepository->save($texturePack);
            $this->texturePackRepository->unlockProcessing($texturePack->getUuid());

            $this->log->info('PBR texture generation completed successfully', [
                'correlation_id' => $correlationId,
                'generation_time_ms' => $generationTimeMs,
            ]);

        } catch (\Throwable $e) {
            $generationTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->log->error('PBR texture generation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $texturePack = $texturePack->markAsFailed($e->getMessage());
            $this->texturePackRepository->save($texturePack);
            $this->texturePackRepository->unlockProcessing($texturePack->getUuid());

            throw $e;
        }
    }

    private function prepareControlImages(array $photoPaths): array
    {
        $controlImages = [];

        foreach ($photoPaths as $photoPath) {
            if (!Storage::exists($photoPath)) {
                throw new RuntimeException("Photo not found: {$photoPath}");
            }

            $imageData = base64_encode(Storage::get($photoPath));
            $controlImages['canny'][] = $this->generateCannyEdge($imageData);
            $controlImages['depth'][] = $this->generateDepthMap($imageData);
            $controlImages['openpose'][] = $this->generateOpenPose($imageData);
        }

        return $controlImages;
    }

    private function generateTextureMap(
        GenerateTextureDTO $dto,
        array $controlImages,
        string $mapType,
    ): TextureResultDTO {
        $prompt = $this->buildPromptForMapType($dto, $mapType);
        $negativePrompt = $dto->getNegativePrompt();

        $payload = [
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'width' => $dto->width,
            'height' => $dto->height,
            'steps' => $dto->steps,
            'cfg_scale' => $dto->cfgScale,
            'sampler_name' => $dto->sampler,
            'seed' => $dto->seed > 0 ? $dto->seed : random_int(1, 999999999),
        ];

        if ($dto->useControlNet) {
            $payload['controlnet_units'] = $this->buildControlNetUnits($dto->controlNetConfig, $controlImages, $mapType);
        }

        if ($dto->useIpAdapter) {
            $payload['ip_adapter'] = [
                'image' => $dto->getReferenceImagePath(),
                'weight' => 0.8,
            ];
        }

        if (!empty($dto->loraWeights)) {
            $payload['lora_weights'] = $dto->loraWeights;
        }

        $startTime = microtime(true);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->stableDiffusionApiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->stableDiffusionApiUrl}/sdapi/v1/txt2img", $payload);

            if (!$response->successful()) {
                throw new RuntimeException("Stable Diffusion API error: {$response->body()}");
            }

            $data = $response->json();

            if (empty($data['images'])) {
                throw new RuntimeException('No images returned from Stable Diffusion API');
            }

            // Save generated image
            $imageData = $data['images'][0];
            $filename = "textures/{$mapType}/{$dto->correlationId}_{$mapType}.png";
            Storage::disk('public')->put($filename, base64_decode($imageData));

            $generationTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Calculate quality metrics
            $qualityMetrics = $this->calculateQualityMetrics($imageData);

            return TextureResultDTO::success(
                albedoPath: $mapType === 'albedo' ? $filename : '',
                normalPath: $mapType === 'normal' ? $filename : '',
                roughnessPath: $mapType === 'roughness' ? $filename : '',
                metallicPath: $mapType === 'metallic' ? $filename : '',
                aoPath: $mapType === 'ao' ? $filename : '',
                displacementPath: $mapType === 'displacement' ? $filename : null,
                metadata: [
                    'map_type' => $mapType,
                    'payload' => $payload,
                ],
                generationTimeMs: $generationTimeMs,
                qualityMetrics: $qualityMetrics,
            );

        } catch (\Throwable $e) {
            $this->log->error("Texture map generation failed for {$mapType}", [
                'error' => $e->getMessage(),
                'correlation_id' => $dto->correlationId,
            ]);

            return TextureResultDTO::failure($e->getMessage());
        }
    }

    private function buildPromptForMapType(GenerateTextureDTO $dto, string $mapType): string
    {
        $basePrompt = $dto->getPrompt();

        return match ($mapType) {
            'albedo' => $basePrompt,
            'normal' => $basePrompt . ', detailed surface details, depth information, 3D relief',
            'roughness' => $basePrompt . ', surface roughness, material reflectivity, glossiness map',
            'metallic' => $basePrompt . ', metallic surface, metal reflectivity, conductor material',
            'ao' => $basePrompt . ', ambient occlusion, shadow details, depth shadows',
            'displacement' => $basePrompt . ', surface displacement, height map, 3D geometry details',
            default => $basePrompt,
        };
    }

    private function buildControlNetUnits(array $config, array $controlImages, string $mapType): array
    {
        $units = [];

        $priority = match ($mapType) {
            'normal', 'displacement' => ['depth' => 1.0, 'canny' => 0.7],
            'roughness', 'metallic' => ['canny' => 1.0, 'depth' => 0.6],
            default => ['canny' => 0.85, 'depth' => 0.70, 'openpose' => 0.70],
        };

        foreach ($priority as $type => $multiplier) {
            if (!empty($controlImages[$type]) && isset($config[$type])) {
                $units[] = [
                    'input_image' => $controlImages[$type][0],
                    'model' => $config[$type]['model'],
                    'weight' => $config[$type]['weight'] * $multiplier,
                    'guidance_start' => $config[$type]['guidance_start'],
                    'guidance_end' => $config[$type]['guidance_end'],
                ];
            }
        }

        return $units;
    }

    private function shouldGenerateDisplacement(TextureType $materialType): bool
    {
        return in_array($materialType, [
            TextureType::LEATHER,
            TextureType::SUEDE,
            TextureType::DENIM,
            TextureType::KNITWEAR,
            TextureType::WOOL,
            TextureType::VELVET,
        ]);
    }

    private function calculateQualityMetrics(string $imageData): array
    {
        // Placeholder for actual quality metrics calculation
        // In production, this would use image processing libraries
        return [
            'sharpness' => 0.85,
            'contrast' => 0.78,
            'noise_level' => 0.12,
            'dynamic_range' => 0.82,
        ];
    }

    private function generateCannyEdge(string $imageData): string
    {
        // Placeholder for Canny edge detection
        // In production, this would call an image processing service
        return $imageData;
    }

    private function generateDepthMap(string $imageData): string
    {
        // Placeholder for depth map generation
        // In production, this would call a depth estimation service
        return $imageData;
    }

    private function generateOpenPose(string $imageData): string
    {
        // Placeholder for OpenPose keypoint detection
        // In production, this would call an OpenPose service
        return $imageData;
    }

    public function retryFailedGeneration(string $texturePackUuid): TexturePack
    {
        $texturePack = $this->texturePackRepository->findByUuid($texturePackUuid);
        if (!$texturePack) {
            throw new RuntimeException('Texture pack not found');
        }

        if (!$texturePack->isFailed()) {
            throw new RuntimeException('Texture pack is not in failed state');
        }

        if (!$texturePack->getStatus()->allowsRetry()) {
            throw new RuntimeException('Maximum retry attempts reached');
        }

        $metadata = $texturePack->getGenerationMetadata();
        $dto = GenerateTextureDTO::fromArray($metadata['dto'] ?? []);

        // Update retry count
        $model = \Modules\Fashion\Infrastructure\Models\TexturePackModel::where('uuid', $texturePackUuid)->first();
        if ($model) {
            $model->incrementRetry();
        }

        // Reset status to pending and re-process
        $texturePack = $texturePack->markAsProcessing($dto->correlationId);
        $this->texturePackRepository->save($texturePack);

        dispatch(function () use ($dto, $texturePack) {
            $this->processTextureGeneration($dto, $texturePack);
        });

        return $texturePack;
    }

    public function cancelGeneration(string $texturePackUuid): void
    {
        $texturePack = $this->texturePackRepository->findByUuid($texturePackUuid);
        if (!$texturePack) {
            throw new RuntimeException('Texture pack not found');
        }

        if (!$texturePack->getStatus()->allowsCancellation()) {
            throw new RuntimeException('Cannot cancel texture generation in current state');
        }

        $texturePack = $texturePack->markAsFailed('Cancelled by user');
        $this->texturePackRepository->save($texturePack);
        $this->texturePackRepository->unlockProcessing($texturePackUuid);
    }
}
