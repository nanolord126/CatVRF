<?php

declare(strict_types=1);

namespace Modules\Flowers\Livewire;

use Livewire\Component;
use Modules\Flowers\Domain\Entities\Order;
use Modules\Flowers\Domain\Enums\OrderStatus;

final class OrderCard extends Component
{
    public Order $order;
    public bool $showDetails = false;

    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    public function getStatusColor(): string
    {
        return match ($this->order->status) {
            OrderStatus::PENDING => 'gray',
            OrderStatus::CONFIRMED => 'blue',
            OrderStatus::IN_ASSEMBLY => 'warning',
            OrderStatus::ASSEMBLED => 'success',
            OrderStatus::QUALITY_CHECKED => 'info',
            OrderStatus::READY_FOR_DELIVERY => 'primary',
            OrderStatus::OUT_FOR_DELIVERY => 'cyan',
            OrderStatus::DELIVERED, OrderStatus::PICKED_UP => 'green',
            OrderStatus::CANCELLED, OrderStatus::REFUNDED => 'danger',
        };
    }

    public function isUrgent(): bool
    {
        return $this->order->isUrgent;
    }

    public function getAssemblyTime(): ?string
    {
        $duration = $this->order->getAssemblyDuration();
        return $duration ? $duration . ' min' : null;
    }

    public function render()
    {
        return view('flowers::livewire.order-card');
    }
}
