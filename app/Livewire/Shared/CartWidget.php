<?php declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\CartService;
use Illuminate\Auth\AuthManager;

/**
 * CartWidget — мини-корзина в хедере.
 * Показывает: кол-во позиций, суммарную стоимость, ссылку на корзину.
 * Обновляется при событии cart-updated (dispatched из add-to-cart, remove-from-cart и т.д.)
 *
 * @see resources/views/livewire/shared/cart-widget.blade.php
 */
final class CartWidget extends Component
{
    public int    $itemCount      = 0;
    public int    $totalAmountKop = 0; // в копейках
    public bool   $isB2B          = false;
    public string $correlationId  = '';

    public function __construct(
        private readonly CartService    $cartService,
        private readonly AuthManager   $auth,
    ) {}

    public function mount(): void
    {
        $this->correlationId = (string) \Illuminate\Support\Str::uuid();
        $this->isB2B         = request()->has('inn') && request()->has('business_card_id');
        $this->refreshCart();
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $user = $this->auth->user();
        if (!$user) {
            $this->itemCount      = 0;
            $this->totalAmountKop = 0;
            return;
        }

        $summary = $this->cartService->getSummaryForUser($user->id, $this->correlationId);

        $this->itemCount      = (int) ($summary['item_count']   ?? 0);
        $this->totalAmountKop = (int) ($summary['total_amount'] ?? 0); // копейки
    }

    public function render(): View
    {
        return view('livewire.shared.cart-widget');
    }
}
