<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final readonly class OrderNotificationService
{
    public function __construct(
        private NotificationChannelRouter $channelRouter
    ) {}

    public function notify(SupermarketOrder $order, string $eventType): void
    {
        $recipients = $this->getRecipients($order, $eventType);

        foreach ($recipients as $recipient) {
            try {
                $channels = $this->channelRouter->getChannels(
                    vertical: 'supermarket',
                    recipientType: $recipient['type']
                );

                $notification = $this->createNotification($order, $eventType, $recipient['type']);

                if (!empty($channels)) {
                    Notification::send(
                        $recipient['user'],
                        $notification->via($channels)
                    );
                }

                Log::info('Order notification sent', [
                    'order_id' => $order->id,
                    'event_type' => $eventType,
                    'recipient_type' => $recipient['type'],
                    'user_id' => $recipient['user']->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send order notification', [
                    'order_id' => $order->id,
                    'event_type' => $eventType,
                    'recipient_type' => $recipient['type'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function getRecipients(SupermarketOrder $order, string $eventType): array
    {
        return match($eventType) {
            'created' => [
                ['user' => $order->buyer, 'type' => 'buyer'],
                ['user' => $order->tenant->user, 'type' => 'seller'],
            ],
            'confirmed', 'ready_for_delivery' => [
                ['user' => $order->buyer, 'type' => 'buyer'],
                ['user' => $order->tenant->user, 'type' => 'seller'],
            ],
            'in_delivery' => [
                ['user' => $order->buyer, 'type' => 'buyer'],
                // ['user' => $order->courier, 'type' => 'courier'], // TODO: Add courier when implemented
            ],
            'delivered' => [
                ['user' => $order->buyer, 'type' => 'buyer'],
                ['user' => $order->tenant->user, 'type' => 'seller'],
            ],
            'cancelled' => [
                ['user' => $order->buyer, 'type' => 'buyer'],
                ['user' => $order->tenant->user, 'type' => 'seller'],
            ],
            default => [['user' => $order->buyer, 'type' => 'buyer']]
        };
    }

    private function createNotification(SupermarketOrder $order, string $eventType, string $recipientType): Notification
    {
        return match($eventType) {
            'created' => $recipientType === 'buyer'
                ? new \App\Domains\Shared\Notifications\Notifications\BuyerOrderCreated($order)
                : new \App\Domains\Shared\Notifications\Notifications\SellerNewOrder($order),
            'confirmed' => new \App\Domains\Shared\Notifications\Notifications\OrderConfirmed($order, $recipientType),
            'ready_for_delivery' => new \App\Domains\Shared\Notifications\Notifications\OrderReadyForDelivery($order, $recipientType),
            'in_delivery' => new \App\Domains\Shared\Notifications\Notifications\OrderInDelivery($order),
            'delivered' => new \App\Domains\Shared\Notifications\Notifications\OrderDelivered($order, $recipientType),
            'cancelled' => new \App\Domains\Shared\Notifications\Notifications\OrderCancelled($order, $recipientType),
            default => throw new \InvalidArgumentException("Unknown event type: {$eventType}")
        };
    }
}
    /**
     * Отправить уведомление о создании возврата.
     *
     * @param ReturnModel $return Возврат
     * @return void
     */
    public function notifyReturnCreated(ReturnModel $return): void
    {
        try {
            $notification = new \App\Domains\Shared\Notifications\Notifications\ReturnCreated($return);
            
            // Уведомить покупателя
            if ($return->buyer) {
                $channels = $this->channelRouter->getChannels(
                    vertical: 'supermarket',
                    recipientType: 'buyer'
                );
                
                if (!empty($channels)) {
                    Notification::send($return->buyer, $notification->via($channels));
                }
            }

            // Уведомить продавца/модератора
            if ($return->seller) {
                $channels = $this->channelRouter->getChannels(
                    vertical: 'supermarket',
                    recipientType: 'seller'
                );
                
                if (!empty($channels)) {
                    Notification::send($return->seller, $notification->via($channels));
                }
            }

            Log::info('Return created notification sent', [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send return created notification', [
                'return_id' => $return->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление об одобрении возврата.
     *
     * @param ReturnModel $return Возврат
     * @return void
     */
    public function notifyReturnApproved(ReturnModel $return): void
    {
        try {
            $notification = new \App\Domains\Shared\Notifications\Notifications\ReturnApproved($return);
            
            if ($return->buyer) {
                $channels = $this->channelRouter->getChannels(
                    vertical: 'supermarket',
                    recipientType: 'buyer'
                );
                
                if (!empty($channels)) {
                    Notification::send($return->buyer, $notification->via($channels));
                }
            }

            Log::info('Return approved notification sent', [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send return approved notification', [
                'return_id' => $return->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление об отклонении возврата.
     *
     * @param ReturnModel $return Возврат
     * @return void
     */
    public function notifyReturnRejected(ReturnModel $return): void
    {
        try {
            $notification = new \App\Domains\Shared\Notifications\Notifications\ReturnRejected($return);
            
            if ($return->buyer) {
                $channels = $this->channelRouter->getChannels(
                    vertical: 'supermarket',
                    recipientType: 'buyer'
                );
                
                if (!empty($channels)) {
                    Notification::send($return->buyer, $notification->via($channels));
                }
            }

            Log::info('Return rejected notification sent', [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send return rejected notification', [
                'return_id' => $return->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
