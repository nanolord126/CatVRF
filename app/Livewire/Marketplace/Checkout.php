<?php declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Illuminate\View\View;
use Livewire\Component;

final class Checkout extends Component
{
    private array $cart = [];
    private int $totalPrice = 0;
    private string $deliveryType = 'standard';
    private int $deliveryPrice = 0;
    private string $paymentMethod = 'card';

    public function mount(): void
    {
        $this->cart = session()->get('cart', []);
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $subtotal = collect($this->cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $this->deliveryPrice = match ($this->deliveryType) {
            'same_day' => 100000,
            default => 0,
        };
        $this->totalPrice = $subtotal + $this->deliveryPrice;
    }

    public function setDeliveryType(string $type): void
    {
        $this->deliveryType = $type;
        $this->calculateTotals();
    }

    public function setPaymentMethod(string $method): void
    {
        $this->paymentMethod = $method;
    }

    public function processPayment(): void
    {
        $this->validate([
            'paymentMethod' => 'required|in:card,wallet,bank_transfer',
        ]);

        session()->put('order_data', [
            'cart' => $this->cart,
            'total_price' => $this->totalPrice,
            'delivery_type' => $this->deliveryType,
            'payment_method' => $this->paymentMethod,
            'created_at' => now(),
        ]);

        $this->dispatch('order-created');
        $this->redirect('/marketplace/order-confirmation');
    }

    public function render(): View
    {
        return view('livewire.marketplace.checkout');
    }
}
