<?php

declare(strict_types=1);

namespace App\Livewire\Food;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

/**
 * Class FoodOrderTracker
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 */
final class FoodOrderTracker extends Component
{
    private readonly string $orderId;

    private readonly string $status = 'pending';

    private readonly string $estimatedTime = '';

    private readonly array $timeline = [];

    /**
     * Handle mount operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(string $orderId): void
    {
        $this->orderId = $orderId;
        $this->loadOrderStatus();
    }

    /**
     * Handle loadOrderStatus operation.
     *
     * @throws \DomainException
     */
    public function loadOrderStatus(): void
    {
        // In real app, fetch from database
        $this->status = 'cooking';
        $this->estimatedTime = '15 min';
        $this->timeline = [
            ['status' => 'pending', 'time' => '14:00', 'completed' => true],
            ['status' => 'cooking', 'time' => '14:05', 'completed' => true],
            ['status' => 'ready', 'time' => '14:20', 'completed' => false],
            ['status' => 'delivering', 'time' => '14:25', 'completed' => false],
        ];
    }

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): View
    {
        return $this->viewFactory->make('livewire.food.order-tracker');
    }
}
