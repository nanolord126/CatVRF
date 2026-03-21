<?php declare(strict_types=1);

namespace App\ServicesD;

use Illuminate\Support\Str;

final class MeatShops3DService
{
    public function generateProductVisualization(array $productData): array
    {
        return [
            'id' => Str::uuid(),
            'vertical' => 'MeatShops',
            'product_id' => $productData['id'] ?? null,
            'model_url' => $this->getModelPath($productData),
            'preview_url' => $this->getPreviewPath($productData),
            'ar_enabled' => true,
            'camera_angles' => [
                'front' => ['position' => [0, 1.5, 3], 'target' => [0, 0.5, 0]],
                'side' => ['position' => [3, 1.5, 0], 'target' => [0, 0.5, 0]],
                'back' => ['position' => [0, 1.5, -3], 'target' => [0, 0.5, 0]],
            ],
        ];
    }

    private function getModelPath(array $productData): string
    {
        return "/3d-models/MeatShops/" . ($productData['sku'] ?? 'default') . ".glb";
    }

    private function getPreviewPath(array $productData): string
    {
        return "/3d-previews/MeatShops/" . ($productData['id'] ?? 'default') . ".png";
    }
}