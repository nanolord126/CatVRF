<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
final readonly class Jewelry3DService
{

    public function __construct(private readonly FilesystemManager $storage, private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function uploadModel(array $data): Jewelry3DModel
        {

            $this->logger->info('Jewelry3DService: Uploading 3D model', [
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'jewelry_item_id' => $data['jewelry_item_id'],
                'tenant_id' => tenant()->id,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($data) {
                $modelFile = $data['model_file'];
                $textureFile = $data['texture_file'] ?? null;
                $previewFile = $data['preview_file'] ?? null;

                $modelPath = $this->storage->disk('public')->putFile('jewelry/3d-models', $modelFile);
                $texturePath = $textureFile ? $this->storage->disk('public')->putFile('jewelry/textures', $textureFile) : null;
                $previewPath = $previewFile ? $this->storage->disk('public')->putFile('jewelry/previews', $previewFile) : null;

                return Jewelry3DModel::create([
                    'uuid' => Str::uuid(),
                    'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'jewelry_item_id' => $data['jewelry_item_id'],
                    'model_url' => $this->storage->disk('public')->url($modelPath),
                    'texture_url' => $texturePath ? $this->storage->disk('public')->url($texturePath) : null,
                    'material_type' => $data['material_type'] ?? 'gold',
                    'dimensions' => $data['dimensions'] ?? [],
                    'weight_grams' => $data['weight_grams'] ?? 0,
                    'preview_image_url' => $previewPath ? $this->storage->disk('public')->url($previewPath) : null,
                    'ar_compatible' => $data['ar_compatible'] ?? true,
                    'vr_compatible' => $data['vr_compatible'] ?? true,
                    'file_size_mb' => $modelFile->getSize() / 1024 / 1024,
                    'format' => $data['format'] ?? 'glb',
                    'status' => 'uploaded',
                    'tags' => $data['tags'] ?? [],
                ]);
            });
        }

        public function generateARView(int $modelId): string
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            $this->logger->info('Jewelry3DService: Generating AR view', [
                'model_id' => $modelId,
                'jewelry_item_id' => $model->jewelry_item_id,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            if (!$model->ar_compatible) {
                throw new \RuntimeException('This model is not AR compatible');
            }

            // Generate AR-compatible URL with viewer parameters
            return route('jewelry.ar-view', [
                'model_id' => $modelId,
                'format' => 'usdz',
            ]);
        }

        public function generateVRView(int $modelId): string
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            $this->logger->info('Jewelry3DService: Generating VR view', [
                'model_id' => $modelId,
                'jewelry_item_id' => $model->jewelry_item_id,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            if (!$model->vr_compatible) {
                throw new \RuntimeException('This model is not VR compatible');
            }

            // Generate VR-compatible viewer URL
            return route('jewelry.vr-view', [
                'model_id' => $modelId,
                'format' => 'gltf',
            ]);
        }

        public function getEmbeddedViewer(int $modelId, string $viewerType = 'web'): string
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            $embedUrl = "https://viewer.example.com/embed?model=" . urlencode($model->model_url);

            if ($viewerType === 'ar') {
                $embedUrl .= '&ar=true&format=usdz';
            } elseif ($viewerType === 'vr') {
                $embedUrl .= '&vr=true&format=gltf';
            }

            return $embedUrl;
        }

        public function rotate3DModel(int $modelId, float $rotationX, float $rotationY, float $rotationZ): array
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            return [
                'model_id' => $modelId,
                'rotationX' => $rotationX,
                'rotationY' => $rotationY,
                'rotationZ' => $rotationZ,
                'model_url' => $model->model_url,
            ];
        }

        public function zoomModel(int $modelId, float $zoomLevel): array
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            // Clamp zoom between 0.1 and 10
            $zoomLevel = max(0.1, min(10, $zoomLevel));

            return [
                'model_id' => $modelId,
                'zoom' => $zoomLevel,
                'model_url' => $model->model_url,
            ];
        }

        public function changeMetalType(int $modelId, string $metalType): Jewelry3DModel
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            $this->logger->info('Jewelry3DService: Changing metal type', [
                'model_id' => $modelId,
                'from' => $model->material_type,
                'to' => $metalType,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($model, $metalType) {
                $model->update(['material_type' => $metalType]);
                return $model;
            });
        }

        public function downloadModel(int $modelId, string $format = 'glb'): string
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            $this->logger->info('Jewelry3DService: Downloading model', [
                'model_id' => $modelId,
                'format' => $format,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            // Return download URL
            return $this->storage->disk('public')->url($model->model_url) . "?format={$format}";
        }

        public function createModelPreview(int $modelId, array $angles = []): array
        {

            $model = Jewelry3DModel::findOrFail($modelId);

            $defaultAngles = [
                ['x' => 0, 'y' => 0, 'z' => 0, 'name' => 'front'],
                ['x' => 0, 'y' => 90, 'z' => 0, 'name' => 'side'],
                ['x' => 90, 'y' => 0, 'z' => 0, 'name' => 'top'],
            ];

            $angles = !empty($angles) ? $angles : $defaultAngles;

            return [
                'model_id' => $modelId,
                'previews' => collect($angles)->map(fn ($angle) => [
                    'angle' => $angle,
                    'url' => $model->preview_image_url,
                ])->toArray(),
            ];
        }
}
