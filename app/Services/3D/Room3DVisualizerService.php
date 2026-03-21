<?php declare(strict_types=1);

namespace App\Services\3D;

use Illuminate\Support\Str;

final class Room3DVisualizerService
{
    public function generateRoomVisualization(array $roomData): array
    {
        return [
            'room_id' => $roomData['id'] ?? Str::uuid(),
            'type' => $roomData['type'] ?? 'standard',
            'dimensions' => [
                'length' => $roomData['length'] ?? 5,
                'width' => $roomData['width'] ?? 4,
                'height' => $roomData['height'] ?? 2.8,
            ],
            'furniture' => $roomData['furniture'] ?? [],
            'textures' => [
                'walls' => '#E8E8E8',
                'floor' => '#D4AF8F',
                'ceiling' => '#FFFFFF',
            ],
            'lighting' => [
                'ambient' => 0.7,
                'directional' => 0.5,
            ],
            'models_3d' => $this->generateRoomModels($roomData),
        ];
    }

    public function generatePropertyVisualization(array $propertyData): array
    {
        return [
            'property_id' => $propertyData['id'] ?? Str::uuid(),
            'type' => $propertyData['type'] ?? 'apartment',
            'rooms' => array_map(fn ($room) => $this->generateRoomVisualization($room), $propertyData['rooms'] ?? []),
            'exterior' => [
                'architecture' => 'modern',
                'facade_color' => '#C0C0C0',
                'windows' => $propertyData['windows'] ?? 4,
            ],
            'floor_plan' => $propertyData['floor_plan'] ?? null,
        ];
    }

    private function generateRoomModels(array $roomData): array
    {
        $models = [
            'bed' => ['scale' => 1.0, 'position' => [2, 0, 0]],
            'chair' => ['scale' => 0.5, 'position' => [0, 0, 0]],
            'desk' => ['scale' => 1.2, 'position' => [1, 0, 1]],
        ];

        return collect($models)
            ->filter(fn ($model, $key) => in_array($key, $roomData['furniture'] ?? []))
            ->all();
    }
}
