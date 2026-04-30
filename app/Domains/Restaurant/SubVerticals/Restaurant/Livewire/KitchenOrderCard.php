<?php

declare(strict_types=1);

namespace Modules\Restaurant\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Modules\Restaurant\Models\Order;

/**
 * KitchenOrderCard — карточка заказа для KDS с анимированными статусами.
 * Использует компонент status-badge для визуализации статусов.
 */
final class KitchenOrderCard extends Component
{
    public Order $order;

    #[Reactive]
    public bool $highlight = false;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function render(): View
    {
        return view('restaurant::livewire.kitchen-order-card');
    }
}
