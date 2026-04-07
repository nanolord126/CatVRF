<?php declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Livewire\Component;

final class Room3DTour extends Component
{
    private int $roomId;
    private string $hotelId;
    private array $roomData = [];
    private array $currentView = [];
    private int $currentFloorIndex = 0;
    private bool $showFloorPlan = false;

    public function mount(int $roomId, string $hotelId): void
    {
        $this->roomId = $roomId;
        $this->hotelId = $hotelId;
        $this->loadRoom3D();
    }

    public function loadRoom3D(): void
    {
        // Load room 3D visualization
        $this->roomData = [
            'id' => $this->roomId,
            'type' => 'deluxe',
            'dimensions' => ['length' => 5, 'width' => 4, 'height' => 2.8],
            'furniture' => ['bed', 'sofa', 'desk', 'chair'],
        ];

        $this->currentView = [
            'position' => [0, 1.5, 0],
            'target' => [0, 1, 0],
            'fov' => 75,
        ];
    }

    public function viewFrom(string $angle): void
    {
        $this->currentView = match ($angle) {
            'window' => ['position' => [0, 1.5, -3], 'target' => [0, 1, 1]],
            'door' => ['position' => [3, 1.5, 0], 'target' => [-1, 1, 0]],
            'full' => ['position' => [2, 2, 2], 'target' => [0, 1, 0]],
            default => $this->currentView,
        };
    }

    public function toggleFloorPlan(): void
    {
        $this->showFloorPlan = !$this->showFloorPlan;
    }

    public function render()
    {
        return view('livewire.three-d.room-3d-tour');
    }
}
