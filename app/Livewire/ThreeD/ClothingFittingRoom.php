<?php declare(strict_types=1);

namespace App\Livewire\ThreeD;

use Livewire\Component;

final class ClothingFittingRoom extends Component
{
    private int $productId;
    private string $selectedSize = 'M';
    private string $selectedColor = 'black';
    private array $availableSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    private array $availableColors = [];
    private bool $showAvatarOptions = false;
    private string $selectedBodyType = 'regular';

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->loadClothingData();
    }

    public function loadClothingData(): void
    {
        // Load clothing colors and variants
        $this->availableColors = ['black', 'white', 'red', 'blue', 'navy', 'grey'];
    }

    public function selectSize(string $size): void
    {
        $this->selectedSize = $size;
        $this->dispatch('size-changed', size: $size);
    }

    public function selectColor(string $color): void
    {
        $this->selectedColor = $color;
        $this->dispatch('color-changed', color: $color);
    }

    public function selectBodyType(string $bodyType): void
    {
        $this->selectedBodyType = $bodyType;
        $this->dispatch('body-type-changed', bodyType: $bodyType);
    }

    public function toggleAvatarOptions(): void
    {
        $this->showAvatarOptions = !$this->showAvatarOptions;
    }

    public function addToCart(): void
    {
        session()->push('cart', [
            'product_id' => $this->productId,
            'size' => $this->selectedSize,
            'color' => $this->selectedColor,
            'quantity' => 1,
        ]);

        $this->dispatch('notification', message: 'Товар добавлен в корзину');
    }

    public function render()
    {
        return view('livewire.three-d.clothing-fitting-room');
    }
}
