<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Listeners;

use Modules\Flowers\Domain\Events\OrderCreated;
use Modules\Flowers\Domain\Events\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendOrderConfirmationNotification implements ShouldQueue
{
    public function handle(OrderCreated|OrderStatusChanged $event): void
    {
        $order = $event->order;
        
        Log::info('Sending order notification', [
            'order_id' => $order->id,
            'order_number' => $order->orderNumber,
            'status' => $order->status->value,
        ]);

        // Send email notification
        // Mail::to($order->client->email)->send(new OrderConfirmationMail($order));
        
        // Send SMS notification
        // SMS::send($order->client->phone, new OrderConfirmationSMS($order));
    }
}
