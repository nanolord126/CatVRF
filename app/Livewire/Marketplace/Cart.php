<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Support\Collection;

use Illuminate\View\View;
use Livewire\Component;

final class Cart extends Component
{
    private readonly array $items = [];

    private readonly int $totalPrice = 0;

    private readonly int $itemCount = 0;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(): void
    {
        $this->loadCart();
    }

    public function loadCart(): void
    {
        $this->items = session()->get('cart', []);
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $this->totalPrice = new Collection($this->items)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $this->itemCount = new Collection($this->items)->sum(fn ($item) => $item['quantity']);
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($key);

            return;
        }

        $this->items[$key]['quantity'] = $quantity;
        session()->put('cart', $this->items);
        $this->calculateTotals();
        $this->dispatch('cart-updated');
    }

    public function removeItem(string $key): void
    {
        unset($this->items[$key]);
        session()->put('cart', $this->items);
        $this->calculateTotals();
        $this->dispatch('cart-updated');
    }

    public function clearCart(): void
    {
        session()->remove('cart');
        $this->items = [];
        $this->calculateTotals();
        $this->dispatch('cart-cleared');
    }

    public function checkout(): void
    {
        $this->redirect('/marketplace/checkout');
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.marketplace.cart');
    }
}
