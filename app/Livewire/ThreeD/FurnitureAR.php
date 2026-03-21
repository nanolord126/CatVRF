<?php declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Livewire\Component;

final class FurnitureAR extends Component
{
    public int $productId;
    public array $furnitureData = [];
    public string $selectedColor = 'brown';
    public bool $showARView = false;
    public bool $showPlacementGuide = false;
    public array $roomDimensions = [];

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->loadFurnitureData();
    }

    public function loadFurnitureData(): void
    {
        $this->furnitureData = [
            'id' => $this->productId,
            'type' => 'sofa',
            'name' => 'Modern Comfort Sofa',
            'dimensions' => ['width' => 200, 'height' => 85, 'depth' => 95],
            'colors' => ['brown', 'black', 'grey', 'beige', 'navy'],
            'price' => 1299,
        ];

        $this->roomDimensions = [
            'width' => 400,
            'depth' => 350,
            'height' => 280,
        ];
    }

    public function selectColor(string $color): void
    {
        $this->selectedColor = $color;
        $this->dispatch('furniture-color-changed', color: $color);
    }

    public function enableARView(): void
    {
        $this->showARView = !$this->showARView;
        $this->dispatch('ar-view-toggled', enabled: $this->showARView);
    }

    public function togglePlacementGuide(): void
    {
        $this->showPlacementGuide = !$this->showPlacementGuide;
    }

    public function addToCart(): void
    {
        session()->push('cart', [
            'product_id' => $this->productId,
            'color' => $this->selectedColor,
            'quantity' => 1,
            'price' => $this->furnitureData['price'],
        ]);

        $this->dispatch('notification', message: 'Мебель добавлена в корзину');
    }

    public function render()
    {
        return view('livewire.three-d.furniture-ar');
    }
}
