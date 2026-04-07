<?php declare(strict_types=1);

namespace App\Services\ThreeD;

use Illuminate\Support\Str;

final class FurnitureARService
{
    public function generateFurniture3DModel(array $furnitureData): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'product_id' => $furnitureData['product_id'],
            'type' => $furnitureData['type'] ?? 'chair', // chair, table, sofa, bed, cabinet
            'dimensions' => [
                'width' => $furnitureData['width'] ?? 80,
                'height' => $furnitureData['height'] ?? 90,
                'depth' => $furnitureData['depth'] ?? 80,
                'unit' => 'cm',
            ],
            'materials' => $furnitureData['materials'] ?? ['wood', 'fabric'],
            'color_variants' => $furnitureData['colors'] ?? ['brown', 'black', 'grey'],
            'ar_placement_enabled' => true,
            'model_url' => $this->getFurnitureModelPath($furnitureData),
            'scale_range' => [0.5, 2.0],
        ];
    }

    public function roomPlacementVisualization(array $roomData, array $furnitureData): array
    {
        return [
            'room_dimensions' => [
                'width' => $roomData['width'] ?? 400,
                'height' => $roomData['height'] ?? 280,
                'depth' => $roomData['depth'] ?? 350,
                'unit' => 'cm',
            ],
            'furniture' => $furnitureData,
            'placement_suggestions' => $this->suggestPlacements($roomData, $furnitureData),
            'available_space' => $this->calculateAvailableSpace($roomData),
        ];
    }

    private function suggestPlacements(array $roomData, array $furnitureData): array
    {
        return [
            'corner' => ['x' => 0, 'y' => 0, 'rotation' => 0],
            'center' => ['x' => $roomData['width'] / 2, 'y' => $roomData['depth'] / 2, 'rotation' => 0],
            'against_wall' => ['x' => 0, 'y' => $roomData['depth'] / 2, 'rotation' => 0],
        ];
    }

    private function calculateAvailableSpace(array $roomData): int
    {
        return ($roomData['width'] ?? 400) * ($roomData['depth'] ?? 350);
    }

    private function getFurnitureModelPath(array $furnitureData): string
    {
        return "/3d-models/furniture/{$furnitureData['type']}/{$furnitureData['sku']}.glb";
    }
}
