<?php

declare(strict_types=1);


namespace App\Livewire\Marketplace;

use Livewire\Component;
use Illuminate\View\View;

final /**
 * ProductCard
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ProductCard extends Component
{
    public int $productId;
    public string $productName;
    public int $price;
    public float $rating;
    public string $imageUrl;
    public string $vertical;

    public function mount(int $productId, string $productName, int $price, float $rating, string $imageUrl, string $vertical): void
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->price = $price;
        $this->rating = $rating;
        $this->imageUrl = $imageUrl;
        $this->vertical = $vertical;
    }

    public function addToCart(): void
    {
        $cart = session()->get('cart', []);
        $key = "{$this->vertical}-{$this->productId}";
        
        if (isset($cart[$key])) {
            $cart[$key]['quantity']++;
        } else {
            $cart[$key] = [
                'product_id' => $this->productId,
                'name' => $this->productName,
                'price' => $this->price,
                'quantity' => 1,
                'vertical' => $this->vertical,
            ];
        }

        session()->put('cart', $cart);
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.marketplace.product-card');
    }
}
