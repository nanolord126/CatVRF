<?php declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendOrderNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        /**
         * Handle the event
         * @param OrderCreated $event
         * @return void
         */
        public function handle(OrderCreated $event): void
        {
            try {
                DB::transaction(function () use ($event) {
                    // Get order with related data
                    $order = $event->order->load(['user', 'items', 'tenant']);

                    // Log event
                    Log::channel('audit')->info('Order notification sent', [
                        'order_id' => $order->id,
                        'order_uuid' => $order->uuid,
                        'user_id' => $order->user_id,
                        'correlation_id' => $event->correlationId,
                        'tenant_id' => $event->tenantId,
                    ]);

                    // Send notification to user
                    $this->notification->send(
                        $order->user,
                        new OrderStatusNotification(
                            $order,
                            'created',
                            $event->correlationId
                        )
                    );

                    // Send notification to tenant admin (optional)
                    if ($order->tenant?->admin) {
                        $this->notification->send(
                            $order->tenant->admin,
                            new OrderStatusNotification(
                                $order,
                                'created_admin',
                                $event->correlationId
                            )
                        );
                    }
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to send order notification', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
