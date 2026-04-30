<?php

declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

final class Property3DViewer extends Component
{
    private readonly int $propertyId;

    private readonly array $propertyData = [];

    private readonly array $currentRoom = [];

    private readonly int $currentFloor = 0;

    private readonly bool $showARView = false;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(int $propertyId): void
    {
        $this->propertyId = $propertyId;
        $this->loadProperty3D();
    }

    public function loadProperty3D(): void
    {
        $this->propertyData = [
            'id' => $this->propertyId,
            'type' => 'apartment',
            'floors' => 2,
            'rooms' => [
                ['id' => 1, 'name' => 'Living Room', 'floor' => 0],
                ['id' => 2, 'name' => 'Kitchen', 'floor' => 0],
                ['id' => 3, 'name' => 'Master Bedroom', 'floor' => 1],
                ['id' => 4, 'name' => 'Bedroom 2', 'floor' => 1],
                ['id' => 5, 'name' => 'Bathroom', 'floor' => 1],
            ],
        ];

        $this->currentRoom = $this->propertyData['rooms'][0];
    }

    public function selectRoom(int $roomIndex): void
    {
        $this->currentRoom = $this->propertyData['rooms'][$roomIndex] ?? [];
        $this->dispatch('room-selected', room: $this->currentRoom);
    }

    public function nextFloor(): void
    {
        if ($this->currentFloor < $this->propertyData['floors'] - 1) {
            $this->currentFloor++;
        }
    }

    public function previousFloor(): void
    {
        if ($this->currentFloor > 0) {
            $this->currentFloor--;
        }
    }

    public function toggleAR(): void
    {
        $this->showARView = ! $this->showARView;
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.three-d.property-3d-viewer');
    }
}
