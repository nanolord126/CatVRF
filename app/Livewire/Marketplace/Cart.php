<?php declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Livewire\Component;
use Illuminate\View\View;

final class Cart extends Component
{
    public array $items = [];
    public int $totalPrice = 0;
    public int $itemCount = 0;

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
        $this->totalPrice = collect($this->items)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $this->itemCount = collect($this->items)->sum(fn ($item) => $item['quantity']);
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
        return view('livewire.marketplace.cart');
    }
}
