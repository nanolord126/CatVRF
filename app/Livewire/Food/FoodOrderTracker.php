<?php

declare(strict_types=1);


namespace App\Livewire\Food;

use Livewire\Component;
use Illuminate\View\View;

final /**
 * FoodOrderTracker
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FoodOrderTracker extends Component
{
    public string $orderId;
    public string $status = 'pending';
    public string $estimatedTime = '';
    public array $timeline = [];

    public function mount(string $orderId): void
    {
        $this->orderId = $orderId;
        $this->loadOrderStatus();
    }

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

    public function render(): View
    {
        return view('livewire.food.order-tracker');
    }
}
