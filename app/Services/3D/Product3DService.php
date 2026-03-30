<?php declare(strict_types=1);

namespace App\Services\3D;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class Product3DService
{
    private const ALLOWED_FORMATS = ['glb', 'gltf', 'obj', 'fbx', 'usdz'];
    private const STORAGE_PATH = '3d-models';

    public function uploadProduct3DModel(string $filePath, string $productId, string $vertical): array
    {
        $fileName = "{$vertical}-{$productId}-" . Str::uuid()->toString() . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        $storagePath = "'.self::STORAGE_PATH.'/{$vertical}/{$fileName}";

        Storage::disk('public')->put($storagePath, file_get_contents($filePath));

        return [
            'id' => Str::uuid()->toString(),
            'product_id' => $productId,
            'vertical' => $vertical,
            'path' => $storagePath,
            'url' => Storage::disk('public')->url($storagePath),
            'format' => pathinfo($filePath, PATHINFO_EXTENSION),
            'size' => filesize($filePath),
            'uploaded_at' => now(),
        ];
    }

    public function generate3DThumbbnail(string $modelPath): string
    {
        // In production, use Three.js renderer or Babylon.js
        return 'thumbnail-path';
    }

    public function validate3DModel(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, $this::ALLOWED_FORMATS);
    }

    public function getProduct3DModel(int $productId): array
    {
        $record = DB::table('product_3d_models')
            ->where('product_id', $productId)
            ->first();

        if (!$record) {
            throw new \RuntimeException("3D model not found for product {$productId}");
        }

        return [
            'id' => $record->id,
            'product_id' => $record->product_id,
            'path' => $record->path,
            'url' => Storage::url($record->path),
            'format' => $record->format,
            'size' => $record->size,
            'uploaded_at' => $record->uploaded_at,
        ];
    }
}
