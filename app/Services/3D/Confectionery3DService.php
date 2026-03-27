<?php

declare(strict_types=1);


namespace App\Services\ThreeD;

use Illuminate\Support\Str;

final /**
 * Confectionery3DService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Confectionery3DService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function generateProductVisualization(array $productData): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'vertical' => 'Confectionery',
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
        return "/3d-models/Confectionery/" . ($productData['sku'] ?? 'default') . ".glb";
    }

    private function getPreviewPath(array $productData): string
    {
        return "/3d-previews/Confectionery/" . ($productData['id'] ?? 'default') . ".png";
    }
}
