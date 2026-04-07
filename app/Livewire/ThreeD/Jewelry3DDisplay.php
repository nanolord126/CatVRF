<?php declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Livewire\Component;

final class Jewelry3DDisplay extends Component
{
    private int $jewelryId;
    private array $jewelryData = [];
    private float $rotationX = 0;
    private float $rotationY = 0;
    private float $zoom = 1.0;
    private string $selectedMaterial = 'gold';
    private string $selectedSize = 'medium';

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
        return view('livewire.three-d.jewelry-3d-display');
    }
}
