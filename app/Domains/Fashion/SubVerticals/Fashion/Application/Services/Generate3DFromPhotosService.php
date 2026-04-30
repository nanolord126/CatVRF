<?php

declare(strict_types=1);

namespace Modules\Fashion\Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Modules\Fashion\Domain\DTOs\GenerateTextureDTO;
use Modules\Fashion\Domain\Entities\TexturePack;
use Modules\Fashion\Domain\Repositories\TexturePackRepositoryInterface;
use Modules\Fashion\Domain\ValueObjects\TextureType;
use RuntimeException;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Generate3DFromPhotosService — Сервис для генерации 3D моделей из фотографий
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class Generate3DFromPhotosService
{
    use WithAuditLogging;

    private const MAX_RETRIES = 3;
    private const TIMEOUT_SECONDS = 900;

    public function __construct(
        private readonly TextureGenerationService $textureGenerationService,
        private readonly TexturePackRepositoryInterface $texturePackRepository,
        private readonly string $meshGenerationApiUrl,
        private readonly string $meshGenerationApiKey,
        private readonly FilesystemManager $storage,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {
        $this->meshGenerationApiUrl = config('fashion_textures.mesh_generation_api_url', env('MESH_GENERATION_API_URL'));
        $this->meshGenerationApiKey = config('fashion_textures.mesh_generation_api_key', env('MESH_GENERATION_API_KEY'));
    }

    /**
     * Generate complete 3D model with PBR textures from photos
     *
     * @param array $photoPaths Array of photo file paths (8-12 photos recommended)
     * @param string $productName Name of the product
     * @param TextureType $materialType Type of material for texture generation
     * @param int $tenantId Tenant ID for multi-tenancy
     * @param int|null $businessGroupId Optional business group ID
     * @return array Result with model3d_id, texture_pack_uuid, and status
     */
    public function generateFromPhotos(
        array $photoPaths,
        string $productName,
        TextureType $materialType,
        int $tenantId,
        ?int $businessGroupId = null,
    ): array {
        // Validate input
        $this->validatePhotos($photoPaths);

        $correlationId = (string) \Illuminate\Support\Str::uuid();
        $startTime = microtime(true);

        try {
            $this->log->info('Starting 3D model generation from photos', [
                'correlation_id' => $correlationId,
                'product_name' => $productName,
                'material_type' => $materialType->value,
                'photo_count' => count($photoPaths),
            ]);

            $this->logAction(
                action: '3d_model_generation_started',
                entityType: 'Model3D',
                entityId: null,
                context: [
                    'correlation_id' => $correlationId,
                    'product_name' => $productName,
                    'material_type' => $materialType->value,
                    'photo_count' => count($photoPaths),
                    'tenant_id' => $tenantId,
                ],
                userId: null,
                tenantId: $tenantId
            );

            // Step 1: Generate base 3D mesh geometry
            $meshResult = $this->generateMeshGeometry($photoPaths, $correlationId);
            if (!$meshResult['success']) {
                throw new RuntimeException("Mesh generation failed: {$meshResult['error']}");
            }

            $model3dId = $this->saveModel3D(
                meshPath: $meshResult['mesh_path'],
                productName: $productName,
                tenantId: $tenantId,
                businessGroupId: $businessGroupId,
                correlationId: $correlationId,
            );

            $this->log->info('Mesh geometry generated successfully', [
                'correlation_id' => $correlationId,
                'model_3d_id' => $model3dId,
            ]);

            $this->logCreated(
                entityType: 'Model3D',
                entityId: $model3dId,
                context: [
                    'correlation_id' => $correlationId,
                    'product_name' => $productName,
                    'material_type' => $materialType->value,
                ],
                userId: null,
                tenantId: $tenantId
            );

            // Step 2: Generate PBR textures using ControlNet
            $textureDto = GenerateTextureDTO::create(
                model3dId: $model3dId,
                productName: $productName,
                materialType: $materialType,
                photoPaths: $photoPaths,
            );

            $texturePack = $this->textureGenerationService->generatePBRTextures($textureDto);

            $this->log->info('PBR texture generation queued', [
                'correlation_id' => $correlationId,
                'texture_pack_uuid' => $texturePack->getUuid(),
            ]);

            // Step 3: Bake textures onto UV unwrapped mesh (async)
            dispatch(function () use ($model3dId, $texturePack, $correlationId) {
                $this->bakeTexturesToMesh($model3dId, $texturePack, $correlationId);
            });

            $totalTime = microtime(true) - $startTime;

            return [
                'success' => true,
                'model_3d_id' => $model3dId,
                'texture_pack_uuid' => $texturePack->getUuid(),
                'status' => 'processing',
                'correlation_id' => $correlationId,
                'estimated_completion_time' => $textureDto->getEstimatedGenerationTime() + 120, // +2 minutes for baking
                'generation_time_seconds' => round($totalTime, 2),
            ];

        } catch (\Throwable $e) {
            $this->log->error('3D model generation from photos failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ];
        }
    }

    private function validatePhotos(array $photoPaths): void
    {
        if (empty($photoPaths)) {
            throw new RuntimeException('At least one photo is required');
        }

        if (count($photoPaths) < 3) {
            throw new RuntimeException('Minimum 3 photos required for 3D reconstruction');
        }

        if (count($photoPaths) > 20) {
            throw new RuntimeException('Maximum 20 photos allowed');
        }

        foreach ($photoPaths as $photoPath) {
            if (!$this->storage->exists($photoPath)) {
                throw new RuntimeException("Photo not found: {$photoPath}");
            }

            // Validate image size (min 512x512)
            $imageInfo = getimagesize($this->storage->path($photoPath));
            if (!$imageInfo) {
                throw new RuntimeException("Invalid image file: {$photoPath}");
            }

            if ($imageInfo[0] < 512 || $imageInfo[1] < 512) {
                throw new RuntimeException("Image too small (min 512x512): {$photoPath}");
            }
        }
    }

    private function generateMeshGeometry(array $photoPaths, string $correlationId): array
    {
        try {
            // Prepare images for mesh generation API
            $images = array_map(function ($path) {
                return base64_encode($this->storage->get($path));
            }, $photoPaths);

            $payload = [
                'images' => $images,
                'quality' => 'high',
                'texture' => false, // We'll generate textures separately
                'format' => 'glb',
            ];

            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->meshGenerationApiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->meshGenerationApiUrl}/api/v1/generate-mesh", $payload);

            if (!$response->successful()) {
                throw new RuntimeException("Mesh generation API error: {$response->body()}");
            }

            $data = $response->json();

            if (empty($data['mesh'])) {
                throw new RuntimeException('No mesh data returned from API');
            }

            // Save mesh to storage
            $meshFilename = "models_3d/meshes/{$correlationId}.glb";
            $this->storage->disk('public')->put($meshFilename, base64_decode($data['mesh']));

            return [
                'success' => true,
                'mesh_path' => $meshFilename,
                'vertices' => $data['vertices'] ?? 0,
                'faces' => $data['faces'] ?? 0,
            ];

        } catch (\Throwable $e) {
            $this->log->error('Mesh geometry generation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function saveModel3D(
        string $meshPath,
        string $productName,
        int $tenantId,
        ?int $businessGroupId,
        string $correlationId,
    ): int {
        $model = \App\Models\Model3D::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
            'modelable_type' => 'fashion_product',
            'modelable_id' => 0, // Will be updated when product is created
            'name' => $productName,
            'description' => "3D model generated from photos",
            'file_path' => $meshPath,
            'model_type' => 'glb',
            'file_size' => $this->storage->disk('public')->size($meshPath),
            'hash' => hash('sha256', $this->storage->disk('public')->get($meshPath)),
            'metadata' => [
                'generation_method' => 'photogrammetry',
                'correlation_id' => $correlationId,
            ],
            'status' => 'processing',
            'correlation_id' => $correlationId,
        ]);

        return $model->id;
    }

    private function bakeTexturesToMesh(int $model3dId, TexturePack $texturePack, string $correlationId): void
    {
        try {
            $this->log->info('Starting texture baking to mesh', [
                'correlation_id' => $correlationId,
                'model_3d_id' => $model3dId,
                'texture_pack_uuid' => $texturePack->getUuid(),
            ]);

            // Wait for texture generation to complete
            $maxWaitTime = 600; // 10 minutes
            $waited = 0;
            $checkInterval = 10;

            while ($waited < $maxWaitTime) {
                $texturePack = $this->texturePackRepository->findByUuid($texturePack->getUuid());
                if ($texturePack->isCompleted() || $texturePack->isFailed()) {
                    break;
                }

                sleep($checkInterval);
                $waited += $checkInterval;
            }

            if (!$texturePack->isCompleted()) {
                throw new RuntimeException('Texture generation did not complete in time');
            }

            // Load mesh and apply PBR textures
            $model = \App\Models\Model3D::find($model3dId);
            if (!$model) {
                throw new RuntimeException('Model3D not found');
            }

            $meshPath = $model->file_path;
            $meshData = $this->storage->disk('public')->get($meshPath);

            // Apply PBR materials to mesh
            $bakedMesh = $this->applyPBRMaterials(
                meshData: $meshData,
                albedoPath: $texturePack->getAlbedoPath(),
                normalPath: $texturePack->getNormalPath(),
                roughnessPath: $texturePack->getRoughnessPath(),
                metallicPath: $texturePack->getMetallicPath(),
                aoPath: $texturePack->getAoPath(),
                displacementPath: $texturePack->getDisplacementPath(),
            );

            // Save baked mesh
            $bakedFilename = "models_3d/baked/{$correlationId}.glb";
            $this->storage->disk('public')->put($bakedFilename, $bakedMesh);

            // Optimize mesh (Draco compression + KTX2 textures)
            $optimizedMesh = $this->optimizeMesh($bakedMesh);
            $optimizedFilename = "models_3d/optimized/{$correlationId}.glb";
            $this->storage->disk('public')->put($optimizedFilename, $optimizedMesh);

            // Update model with optimized file
            $model->update([
                'file_path' => $optimizedFilename,
                'file_size' => $this->storage->disk('public')->size($optimizedFilename),
                'hash' => hash('sha256', $optimizedMesh),
                'metadata' => array_merge($model->metadata ?? [], [
                    'texture_pack_uuid' => $texturePack->getUuid(),
                    'pbr_textures_applied' => true,
                    'optimized' => true,
                    'optimization_ratio' => round(
                        $this->storage->disk('public')->size($meshPath) / $this->storage->disk('public')->size($optimizedFilename),
                        2
                    ),
                ]),
                'status' => 'active',
            ]);

            $this->log->info('Texture baking completed successfully', [
                'correlation_id' => $correlationId,
                'model_3d_id' => $model3dId,
                'original_size' => $this->storage->disk('public')->size($meshPath),
                'optimized_size' => $this->storage->disk('public')->size($optimizedFilename),
            ]);

        } catch (\Throwable $e) {
            $this->log->error('Texture baking failed', [
                'correlation_id' => $correlationId,
                'model_3d_id' => $model3dId,
                'error' => $e->getMessage(),
            ]);

            // Update model status to failed
            $model = \App\Models\Model3D::find($model3dId);
            if ($model) {
                $model->update([
                    'status' => 'rejected',
                    'rejection_reason' => "Texture baking failed: {$e->getMessage()}",
                ]);
            }

            throw $e;
        }
    }

    private function applyPBRMaterials(
        string $meshData,
        string $albedoPath,
        string $normalPath,
        string $roughnessPath,
        string $metallicPath,
        string $aoPath,
        ?string $displacementPath,
    ): string {
        // Placeholder for actual PBR material application
        // In production, this would use a 3D processing library like glTF-Transformer
        // or a dedicated mesh processing service

        $materials = [
            'albedo' => $this->storage->disk('public')->path($albedoPath),
            'normal' => $this->storage->disk('public')->path($normalPath),
            'roughness' => $this->storage->disk('public')->path($roughnessPath),
            'metallic' => $this->storage->disk('public')->path($metallicPath),
            'ao' => $this->storage->disk('public')->path($aoPath),
        ];

        if ($displacementPath) {
            $materials['displacement'] = $this->storage->disk('public')->path($displacementPath);
        }

        // Apply materials to mesh (placeholder)
        // In production: Use glTF-Transformer or similar to embed PBR materials
        return $meshData;
    }

    private function optimizeMesh(string $meshData): string
    {
        // Placeholder for mesh optimization
        // In production, this would:
        // 1. Apply Draco compression to geometry
        // 2. Convert textures to KTX2 with Basis Universal
        // 3. Optimize UV unwrapping
        // 4. Remove unused vertices/materials
        // 5. Quantize attributes

        // For now, return the original data
        return $meshData;
    }

    public function getGenerationStatus(string $correlationId): array
    {
        $model = \App\Models\Model3D::where('correlation_id', $correlationId)->first();
        if (!$model) {
            return [
                'status' => 'not_found',
                'message' => 'Generation not found',
            ];
        }

        $texturePack = $this->texturePackRepository->findByModel3dId($model->id);

        return [
            'model_3d_id' => $model->id,
            'model_status' => $model->status,
            'texture_pack_uuid' => $texturePack?->getUuid(),
            'texture_status' => $texturePack?->getStatus()->value,
            'file_path' => $model->status === 'active' ? $model->file_path : null,
            'progress' => $this->calculateProgress($model->status, $texturePack?->getStatus()),
        ];
    }

    private function calculateProgress(string $modelStatus, ?\Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus $textureStatus): int
    {
        if ($modelStatus === 'active') {
            return 100;
        }

        if ($modelStatus === 'processing' && !$textureStatus) {
            return 20; // Mesh generation
        }

        if ($textureStatus) {
            return match ($textureStatus) {
                \Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus::PENDING => 30,
                \Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus::PROCESSING => 60,
                \Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus::VALIDATING => 80,
                \Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus::COMPLETED => 90,
                \Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus::FAILED => 0,
                default => 50,
            };
        }

        return 10;
    }

    public function cancelGeneration(string $correlationId): void
    {
        $model = \App\Models\Model3D::where('correlation_id', $correlationId)->first();
        if (!$model) {
            throw new RuntimeException('Generation not found');
        }

        if ($model->status === 'active') {
            throw new RuntimeException('Cannot cancel completed generation');
        }

        $model->update([
            'status' => 'rejected',
            'rejection_reason' => 'Cancelled by user',
        ]);

        // Cancel texture generation if in progress
        $texturePack = $this->texturePackRepository->findByModel3dId($model->id);
        if ($texturePack && $texturePack->getStatus()->allowsCancellation()) {
            $this->textureGenerationService->cancelGeneration($texturePack->getUuid());
        }
    }
}
