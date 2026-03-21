<?php declare(strict_types=1);

namespace App\Domains\Jewelry\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Jewelry\Models\Jewelry3DModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class Jewelry3DService
{
    public function uploadModel(array $data): Jewelry3DModel
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'uploadModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL uploadModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'uploadModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL uploadModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'uploadModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL uploadModel', ['domain' => __CLASS__]);

        Log::channel('audit')->info('Jewelry3DService: Uploading 3D model', [
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'jewelry_item_id' => $data['jewelry_item_id'],
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return DB::transaction(function () use ($data) {
            $modelFile = $data['model_file'];
            $textureFile = $data['texture_file'] ?? null;
            $previewFile = $data['preview_file'] ?? null;

            $modelPath = Storage::disk('public')->putFile('jewelry/3d-models', $modelFile);
            $texturePath = $textureFile ? Storage::disk('public')->putFile('jewelry/textures', $textureFile) : null;
            $previewPath = $previewFile ? Storage::disk('public')->putFile('jewelry/previews', $previewFile) : null;

            return Jewelry3DModel::create([
                'uuid' => Str::uuid(),
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'tenant_id' => filament()->getTenant()->id,
                'jewelry_item_id' => $data['jewelry_item_id'],
                'model_url' => Storage::url($modelPath),
                'texture_url' => $texturePath ? Storage::url($texturePath) : null,
                'material_type' => $data['material_type'] ?? 'gold',
                'dimensions' => $data['dimensions'] ?? [],
                'weight_grams' => $data['weight_grams'] ?? 0,
                'preview_image_url' => $previewPath ? Storage::url($previewPath) : null,
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'generateARView'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateARView', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'generateARView'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateARView', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'generateARView'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateARView', ['domain' => __CLASS__]);

        $model = Jewelry3DModel::findOrFail($modelId);

        Log::channel('audit')->info('Jewelry3DService: Generating AR view', [
            'model_id' => $modelId,
            'jewelry_item_id' => $model->jewelry_item_id,
        ]);

        if (!$model->ar_compatible) {
            throw new \Exception('This model is not AR compatible');
        }

        // Generate AR-compatible URL with viewer parameters
        return route('jewelry.ar-view', [
            'model_id' => $modelId,
            'format' => 'usdz',
        ]);
    }

    public function generateVRView(int $modelId): string
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'generateVRView'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateVRView', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'generateVRView'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateVRView', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'generateVRView'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateVRView', ['domain' => __CLASS__]);

        $model = Jewelry3DModel::findOrFail($modelId);

        Log::channel('audit')->info('Jewelry3DService: Generating VR view', [
            'model_id' => $modelId,
            'jewelry_item_id' => $model->jewelry_item_id,
        ]);

        if (!$model->vr_compatible) {
            throw new \Exception('This model is not VR compatible');
        }

        // Generate VR-compatible viewer URL
        return route('jewelry.vr-view', [
            'model_id' => $modelId,
            'format' => 'gltf',
        ]);
    }

    public function getEmbeddedViewer(int $modelId, string $viewerType = 'web'): string
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getEmbeddedViewer'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getEmbeddedViewer', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getEmbeddedViewer'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getEmbeddedViewer', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getEmbeddedViewer'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getEmbeddedViewer', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'rotate3DModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL rotate3DModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'rotate3DModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL rotate3DModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'rotate3DModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL rotate3DModel', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'zoomModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL zoomModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'zoomModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL zoomModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'zoomModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL zoomModel', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'changeMetalType'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL changeMetalType', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'changeMetalType'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL changeMetalType', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'changeMetalType'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL changeMetalType', ['domain' => __CLASS__]);

        $model = Jewelry3DModel::findOrFail($modelId);

        Log::channel('audit')->info('Jewelry3DService: Changing metal type', [
            'model_id' => $modelId,
            'from' => $model->material_type,
            'to' => $metalType,
        ]);

        return DB::transaction(function () use ($model, $metalType) {
            $model->update(['material_type' => $metalType]);
            return $model;
        });
    }

    public function downloadModel(int $modelId, string $format = 'glb'): string
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'downloadModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL downloadModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'downloadModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL downloadModel', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'downloadModel'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL downloadModel', ['domain' => __CLASS__]);

        $model = Jewelry3DModel::findOrFail($modelId);

        Log::channel('audit')->info('Jewelry3DService: Downloading model', [
            'model_id' => $modelId,
            'format' => $format,
        ]);

        // Return download URL
        return Storage::url($model->model_url) . "?format={$format}";
    }

    public function createModelPreview(int $modelId, array $angles = []): array
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createModelPreview'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createModelPreview', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createModelPreview'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createModelPreview', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createModelPreview'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createModelPreview', ['domain' => __CLASS__]);

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
