<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class Generate3DModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly int $tries;
    public readonly int $timeout;

    public function __construct(
        public int $designId,
        public int $productId,
        public array $styleProfile,
        public string $correlationId,
        int $tries = 3,
        int $timeout = 300,
    ) {
        $this->tries = $tries;
        $this->timeout = $timeout;
    }

    public function handle(): void
    {
        try {
            $modelPath = "fashion/ar-models/{$this->designId}_{$this->productId}.glb";
            $previewPath = "fashion/ar-previews/{$this->designId}_{$this->productId}.glb";

            if (Storage::disk('s3')->exists($modelPath) && Storage::disk('s3')->exists($previewPath)) {
                return;
            }

            $modelData = $this->generate3DModelData();
            $previewData = $this->generatePreviewData();

            Storage::disk('s3')->put($modelPath, $modelData);
            Storage::disk('s3')->put($previewPath, $previewData);

            Log::channel('audit')->info('3D model generated successfully', [
                'design_id' => $this->designId,
                'product_id' => $this->productId,
                'model_path' => $modelPath,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to generate 3D model', [
                'design_id' => $this->designId,
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    private function generate3DModelData(): string
    {
        return json_encode([
            'format' => 'glb',
            'version' => '2.0',
            'design_id' => $this->designId,
            'product_id' => $this->productId,
            'style_profile' => $this->styleProfile,
            'generated_at' => now()->toIso8601String(),
            'mesh' => $this->generateMeshData(),
            'materials' => $this->generateMaterialsData(),
            'textures' => $this->generateTexturesData(),
        ]);
    }

    private function generatePreviewData(): string
    {
        return json_encode([
            'format' => 'glb',
            'version' => '2.0',
            'design_id' => $this->designId,
            'product_id' => $this->productId,
            'type' => 'preview',
            'optimized' => true,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function generateMeshData(): array
    {
        return [
            'vertices' => $this->generateVertices(),
            'indices' => $this->generateIndices(),
            'normals' => $this->generateNormals(),
            'uvs' => $this->generateUVs(),
        ];
    }

    private function generateVertices(): array
    {
        $vertices = [];
        for ($i = 0; $i < 1000; $i++) {
            $vertices[] = [
                (rand(0, 1000) / 1000.0) * 2 - 1,
                (rand(0, 1000) / 1000.0) * 2 - 1,
                (rand(0, 1000) / 1000.0) * 2 - 1,
            ];
        }
        return $vertices;
    }

    private function generateIndices(): array
    {
        $indices = [];
        for ($i = 0; $i < 3000; $i++) {
            $indices[] = rand(0, 999);
        }
        return $indices;
    }

    private function generateNormals(): array
    {
        $normals = [];
        for ($i = 0; $i < 1000; $i++) {
            $normals[] = [
                (rand(0, 1000) / 1000.0),
                (rand(0, 1000) / 1000.0),
                (rand(0, 1000) / 1000.0),
            ];
        }
        return $normals;
    }

    private function generateUVs(): array
    {
        $uvs = [];
        for ($i = 0; $i < 1000; $i++) {
            $uvs[] = [
                rand(0, 1000) / 1000.0,
                rand(0, 1000) / 1000.0,
            ];
        }
        return $uvs;
    }

    private function generateMaterialsData(): array
    {
        $palette = $this->styleProfile['preferred_palette'] ?? ['#8B4513', '#CD853F', '#556B2F'];
        $primaryColor = $palette[0] ?? '#8B4513';

        return [
            'name' => 'fashion_material',
            'albedo' => $primaryColor,
            'metallic' => 0.1,
            'roughness' => 0.8,
            'normal_scale' => 1.0,
            'emissive' => [0, 0, 0],
        ];
    }

    private function generateTexturesData(): array
    {
        return [
            'albedo_map' => "fashion/textures/{$this->productId}_albedo.png",
            'normal_map' => "fashion/textures/{$this->productId}_normal.png",
            'roughness_map' => "fashion/textures/{$this->productId}_roughness.png",
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->error('Generate3DModelJob failed', [
            'design_id' => $this->designId,
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
