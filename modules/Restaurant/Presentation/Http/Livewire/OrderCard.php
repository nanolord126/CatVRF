<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Http\Livewire;

use Livewire\Component;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;

final class OrderCard extends Component
{
    public OrderKitchenStatus $orderStatus;
    public bool $showDetails = false;
    public string $problemComment = '';

    public function mount(OrderKitchenStatus $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    public function render()
    {
        $order = \App\Models\Order::with(['items', 'user'])->find($this->orderStatus->orderId);
        
        return view('restaurant::livewire.order-card', [
            'order' => $order,
            'status' => $this->orderStatus,
            'timeRemaining' => $this->orderStatus->getTimeRemaining(),
            'elapsedMinutes' => $this->orderStatus->getElapsedMinutes(),
            'progress' => $this->orderStatus->getProgress(),
            'isOverdue' => $this->orderStatus->isOverdue(),
        ]);
    }

    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    public function startOrder(): void
    {
        $this->dispatch('start-order', orderStatusId: $this->orderStatus->id);
    }

    public function completeOrder(): void
    {
        $this->dispatch('complete-order', orderStatusId: $this->orderStatus->id);
    }

    public function serveOrder(): void
    {
        $this->dispatch('serve-order', orderStatusId: $this->orderStatus->id);
    }

    public function cancelOrder(): void
    {
        $this->dispatch('cancel-order', orderStatusId: $this->orderStatus->id);
    }

    public function submitProblem(): void
    {
        $this->validate([
            'problemComment' => 'required|string|min:3|max:500',
        ]);

        $this->dispatch('report-problem', 
            orderStatusId: $this->orderStatus->id,
            comment: $this->problemComment
        );
        
        $this->problemComment = '';
        $this->showDetails = false;
    }

    public function setPriority(string $priority): void
    {
        $this->dispatch('set-priority',
            orderStatusId: $this->orderStatus->id,
            priority: $priority
        );
    }

    public function getStatusColor(): string
    {
        return $this->orderStatus->status->color();
    }

    public function getPriorityColor(): string
    {
        return $this->orderStatus->priority->color();
    }

    public function getTimerColor(): string
    {
        if ($this->orderStatus->isOverdue()) {
            return 'text-red-600 bg-red-50';
        }
        
        $progress = $this->orderStatus->getProgress();
        
        if ($progress > 80) {
            return 'text-orange-600 bg-orange-50';
        }
        
        if ($progress > 50) {
            return 'text-yellow-600 bg-yellow-50';
        }
        
        return 'text-green-600 bg-green-50';
    }
}
