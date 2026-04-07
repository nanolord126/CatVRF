<?php

declare(strict_types=1);

namespace App\Livewire\Food\B2C;

use App\Domains\Food\Application\B2C\DataTransferObjects\CartDto;
use App\Domains\Food\Application\B2C\DataTransferObjects\CartItemDto;
use App\Domains\Food\Application\B2C\UseCases\PlaceOrderUseCase;
use App\Domains\Food\Infrastructure\Persistence\Eloquent\Models\DishModel;
use Illuminate\Contracts\View\View;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class ShoppingCart extends Component
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly Guard $guard,
    ) {}

    private array $items = [];
    private ?string $restaurantId = null;
    private float $subtotal = 0.0;
    private float $deliveryFee = 5.0; // Example fee
    private float $total = 0.0;

    protected $listeners = ['addToCart' => 'addItem'];

    public function mount(?string $restaurantId = null): void
    {
        $this->restaurantId = $restaurantId;
        $this->loadCart();
        $this->calculateTotals();
    }

    public function addItem(string $dishId, int $quantity = 1): void
    {
        if (!$this->restaurantId) {
            $this->dispatch('error', 'Please select a restaurant first.');
            return;
        }

        $dish = DishModel::find($dishId);
        if (!$dish) {
            return;
        }

        if (isset($this->items[$dishId])) {
            $this->items[$dishId]['quantity'] += $quantity;
        } else {
            $this->items[$dishId] = [
                'dish_id' => $dish->id,
                'name' => $dish->name,
                'price' => $dish->price_amount / 100,
                'quantity' => $quantity,
            ];
        }

        $this->saveCart();
        $this->calculateTotals();
        $this->dispatch('cartUpdated', count($this->items));
    }

    public function updateQuantity(string $dishId, int $quantity): void
    {
        if (isset($this->items[$dishId])) {
            if ($quantity > 0) {
                $this->items[$dishId]['quantity'] = $quantity;
            } else {
                unset($this->items[$dishId]);
            }
            $this->saveCart();
            $this->calculateTotals();
        }
    }

    public function removeItem(string $dishId): void
    {
        unset($this->items[$dishId]);
        $this->saveCart();
        $this->calculateTotals();
        $this->dispatch('cartUpdated', count($this->items));
    }

    public function clearCart(): void
    {
        $this->items = [];
        session()->forget('food_cart');
        $this->calculateTotals();
        $this->dispatch('cartUpdated', 0);
    }

    public function checkout(PlaceOrderUseCase $placeOrderUseCase): void
    {
        if (empty($this->items)) {
            $this->dispatch('error', 'Your cart is empty.');
            return;
        }

        try {
            $cartItems = [];
            foreach ($this->items as $item) {
                $cartItems[] = new CartItemDto(
                    dishId: Str::uuid($item['dish_id']),
                    quantity: $item['quantity'],
                    modifiers: [] // Modifiers not implemented in this simplified example
                );
            }

            $cartDto = new CartDto(
                restaurantId: Str::uuid($this->restaurantId),
                items: collect($cartItems)
            );

            // This assumes the user is authenticated and we can get their ID.
            $clientId = $this->guard->id() ? Str::uuid($this->guard->id()) : Str::uuid();
            $correlationId = Str::uuid();

            $orderResult = $placeOrderUseCase->execute($cartDto, $clientId, $correlationId);

            $this->clearCart();
            $this->dispatch('orderPlaced', $orderResult->id->toString());
            // Redirect to order confirmation page
            // return redirect()->route('food.order.confirmation', ['orderId' => $orderResult->id->toString()]);

        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Failed to place order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('error', 'Something went wrong. Please try again.');
        }
    }

    public function render(): View
    {
        return view('livewire.food.b2c.shopping-cart');
    }

    private function calculateTotals(): void
    {
        $this->subtotal = 0;
        foreach ($this->items as $item) {
            $this->subtotal += $item['price'] * $item['quantity'];
        }
        $this->total = $this->subtotal + $this->deliveryFee;
    }

    private function saveCart(): void
    {
        session(['food_cart' => [
            'restaurant_id' => $this->restaurantId,
            'items' => $this->items,
        ]]);
    }

    private function loadCart(): void
    {
        $cart = session('food_cart', []);
        if (!empty($cart) && $cart['restaurant_id'] === $this->restaurantId) {
            $this->items = $cart['items'];
        }
    }
}
