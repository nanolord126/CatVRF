<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Events;

use App\Domains\Vapes\Models\VapeOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * VapeOrderPaidEvent — Production Ready 2026
 * 
 * Событие успешной оплаты заказа вейп-вертикали.
 * Инициирует запуск Job'а в Честный ЗНАК.
 * Канон 2026: correlation_id в конструкторе.
 */
class VapeOrderPaidEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $orderId;
    public string $correlationId;

    /**
     * Создание события.
     */
    public function __construct(VapeOrder $order, string $correlationId = null)
    {
        $this->orderId = $order->id;
        $this->correlationId = $correlationId ?? (string) Str::uuid();

        Log::channel('audit')->info('Vape order PAID event fired', [
            'order_id' => $this->orderId,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
