<?php declare(strict_types=1);

namespace App\Http\Livewire\Food;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderTrackingComponent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public int $orderId = 0;
        public ?RestaurantOrder $order = null;
        public array $timelineEvents = [];
        public bool $isLoading = true;

        protected OrderService $orderService;

        public function mount(OrderService $orderService): void
        {
            $this->orderService = $orderService;
        }

        public function loadOrder(): void
        {
            try {
                $this->isLoading = true;

                $this->order = RestaurantOrder::where('id', $this->orderId)
                    ->where('client_id', auth()->user()->id)
                    ->firstOrFail();

                $this->timelineEvents = [
                    [
                        'status' => 'pending',
                        'title' => 'Заказ получен',
                        'completed' => in_array($this->order->status, ['pending', 'cooking', 'ready', 'delivered']),
                        'time' => $this->order->created_at->format('H:i'),
                    ],
                    [
                        'status' => 'cooking',
                        'title' => 'Готовится',
                        'completed' => in_array($this->order->status, ['cooking', 'ready', 'delivered']),
                        'time' => $this->order->cooking_started_at?->format('H:i') ?? '--',
                    ],
                    [
                        'status' => 'ready',
                        'title' => 'Готово к подаче',
                        'completed' => in_array($this->order->status, ['ready', 'delivered']),
                        'time' => $this->order->ready_at?->format('H:i') ?? '--',
                    ],
                    [
                        'status' => 'delivered',
                        'title' => 'Доставлено',
                        'completed' => $this->order->status === 'delivered',
                        'time' => $this->order->delivered_at?->format('H:i') ?? '--',
                    ],
                ];

            } catch (\Exception $e) {
                \Log::channel('error')->error('Failed to load order', [
                    'order_id' => $this->orderId,
                    'exception' => $e->getMessage(),
                    'correlation_id' => (string) Str::uuid(),
                ]);
            } finally {
                $this->isLoading = false;
            }
        }

        public function cancelOrder(): void
        {
            if ($this->order && $this->order->status === 'pending') {
                try {
                    $this->orderService->cancelOrder($this->order, [
                        'correlation_id' => (string) Str::uuid(),
                    ]);

                    $this->emit('orderCancelled');

                    \Log::channel('audit')->info('Order cancelled', [
                        'order_id' => $this->order->id,
                        'user_id' => auth()->user()->id,
                    ]);

                } catch (\Exception $e) {
                    \Log::channel('error')->error('Failed to cancel order', [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }

        public function render()
        {
            return view('livewire.food.order-tracking', [
                'timelineEvents' => $this->timelineEvents,
            ]);
        }
}
