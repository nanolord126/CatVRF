<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

/**
 * Class ProductCard
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 */
final class ProductCard extends Component
{
    private readonly int $productId;

    private readonly string $productName;

    private readonly int $price;

    private readonly float $rating;

    private readonly string $imageUrl;

    private readonly string $vertical;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

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
        return $this->viewFactory->make('livewire.marketplace.product-card');
    }
}
