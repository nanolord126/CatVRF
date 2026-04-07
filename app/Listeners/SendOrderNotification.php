<?php declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Notifications\OrderStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class SendOrderNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly Dispatcher $notification,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
    {
        // Implementation required by canon
    }

    /**
     * Handle the event
     * @param OrderCreated $event
     * @return void
     */
    public function handle(OrderCreated $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                // Get order with related data
                $order = $event->order->load(['user', 'items', 'tenant']);

                // Log event
                $this->logger->channel('audit')->info('Order notification sent', [
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
            $this->logger->channel('audit')->error('Failed to send order notification', [
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
