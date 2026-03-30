<?php declare(strict_types=1);

namespace App\Livewire\Food;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FoodOrderTracker extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
