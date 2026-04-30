<?php

declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

final class Jewelry3DDisplay extends Component
{
    private readonly int $jewelryId;

    private readonly array $jewelryData = [];

    private readonly float $rotationX = 0;

    private readonly float $rotationY = 0;

    private readonly float $zoom = 1.0;

    private readonly string $selectedMaterial = 'gold';

    private readonly string $selectedSize = 'medium';

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(int $jewelryId): void
    {
        $this->jewelryId = $jewelryId;
        $this->loadJewelryData();
    }

    public function loadJewelryData(): void
    {
        $this->jewelryData = [
            'id' => $this->jewelryId,
            'name' => 'Diamond Ring',
            'type' => 'ring',
            'materials' => ['gold', 'silver', 'platinum', 'rose_gold'],
            'sizes' => ['small', 'medium', 'large'],
            'certificate' => 'GIA',
            'price' => 2500,
        ];
    }

    public function rotate(string $direction): void
    {
        match ($direction) {
            'right' => $this->rotationY += 30,
            'up' => $this->rotationX += 30,
            'down' => $this->rotationX -= 30,
        };
    }

    public function zoomIn(): void
    {
        $this->zoom = min($this->zoom + 0.2, 5.0);
    }

    public function zoomOut(): void
    {
        $this->zoom = max($this->zoom - 0.2, 0.5);
    }

    public function selectMaterial(string $material): void
    {
        $this->selectedMaterial = $material;
        $this->dispatch('material-changed', material: $material);
    }

    public function selectSize(string $size): void
    {
        $this->selectedSize = $size;
        $this->dispatch('size-changed', size: $size);
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.three-d.jewelry-3d-display');
    }
}
