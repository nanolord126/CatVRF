<?php declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Livewire\Component;

/**
 * Class ProductCard
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 *
 * @package App\Livewire\Marketplace
 */
final class ProductCard extends Component
{
    private int $productId;
        private string $productName;
        private int $price;
        private float $rating;
        private string $imageUrl;
        private string $vertical;

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
