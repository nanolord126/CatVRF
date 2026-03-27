<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Listeners;

use App\Domains\Vapes\Events\VapeOrderPaidEvent;
use App\Domains\Vapes\Jobs\VapeMarkingRegistrationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * TriggerVapeMarkingRegistration — Production Ready 2026
 * 
 * Слушатель события оплаты заказа. Автоматически запускает 
 * Job регистрации выбытия в системе "Честный ЗНАК".
 */
class TriggerVapeMarkingRegistration implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Создание слушателя.
     */
    public function __construct() {}

    /**
     * Обработка события оплаты.
     */
    public function handle(VapeOrderPaidEvent $event): void
    {
        Log::channel('audit')->info('Vape order paid listener: sending to marking job', [
            'order_id' => $event->orderId,
            'correlation_id' => $event->correlationId,
        ]);

        // Диспетчеризация задачи на регистрацию выбытия
        VapeMarkingRegistrationJob::dispatch(
            orderId: $event->orderId,
            correlationId: $event->correlationId,
        )->onQueue('low_stock'); // Очередь низкого приоритета (ГИС МТ)
    }
}
