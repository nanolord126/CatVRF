<?php declare(strict_types=1);

namespace App\Services\ThreeD;

use Illuminate\Support\Str;

final class VehicleVisualizerService
{
    public function generateVehicleVisualization(array $vehicleData): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'vehicle_id' => $vehicleData['id'],
            'type' => $vehicleData['type'] ?? 'car',
            'model_3d_url' => $this->getVehicleModel($vehicleData),
            'exterior' => [
                'color' => $vehicleData['color'] ?? '#000000',
                'paint_type' => $vehicleData['paint_type'] ?? 'metallic',
                'wheels' => $vehicleData['wheels'] ?? 4,
                'wheel_size' => $vehicleData['wheel_size'] ?? '18 inches',
            ],
            'interior' => [
                'seats' => $vehicleData['seats'] ?? 5,
                'upholstery' => $vehicleData['upholstery'] ?? 'leather',
                'dashboard' => $vehicleData['dashboard_color'] ?? 'black',
            ],
            'camera_angles' => [
                'front' => ['position' => [0, 1.5, 3], 'target' => [0, 1, 0]],
                'side' => ['position' => [3, 1.5, 0], 'target' => [0, 1, 0]],
                'top' => ['position' => [0, 4, 0], 'target' => [0, 1, 0]],
                'interior' => ['position' => [0, 1, 0], 'target' => [0, 1, -1]],
            ],
        ];
    }

    private function getVehicleModel(array $vehicleData): string
    {
        return "/3d-models/vehicles/{$vehicleData['brand']}-{$vehicleData['model']}.glb";
    }
}

