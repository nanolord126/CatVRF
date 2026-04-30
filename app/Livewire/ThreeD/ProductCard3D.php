<?php

declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

final class ProductCard3D extends Component
{
    private readonly int $productId;

    private readonly string $vertical;

    private readonly array $product = [];

    private readonly array $model3D = [];

    private readonly string $selectedColor = '#000000';

    private readonly float $rotationX = 0;

    private readonly float $rotationY = 0;

    private readonly float $zoom = 1.0;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(int $productId, string $vertical): void
    {
        $this->productId = $productId;
        $this->vertical = $vertical;
        $this->loadProduct3D();
    }

    public function loadProduct3D(): void
    {
        // Load 3D model data from service
        $this->model3D = [
            'url' => "/3d-models/{$this->vertical}/{$this->productId}.glb",
            'scale' => 1.0,
            'position' => [0, 0, 0],
        ];
    }

    public function rotate(string $direction): void
    {
        match ($direction) {
            'right' => $this->rotationY += 15,
            'up' => $this->rotationX += 15,
            'down' => $this->rotationX -= 15,
        };
    }

    public function zoomIn(): void
    {
        $this->zoom = min($this->zoom + 0.1, 3.0);
    }

    public function zoomOut(): void
    {
        $this->zoom = max($this->zoom - 0.1, 0.5);
    }

    public function changeColor(string $color): void
    {
        $this->selectedColor = $color;
        $this->dispatch('color-changed', color: $color);
    }

    public function enableARView(): void
    {
        $this->dispatch('enable-ar', modelUrl: $this->model3D['url']);
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.three-d.product-card-3d');
    }
}
