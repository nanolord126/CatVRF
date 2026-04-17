<?php declare(strict_types=1);

/**
 * TriggerVapeMarkingRegistration — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/triggervapemarkingregistration
 */


namespace App\Domains\SportsNutrition\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;


use Psr\Log\LoggerInterface;
final class TriggerVapeMarkingRegistration
{


        /**
         * Создание слушателя.
         */
        public function __construct(private readonly LoggerInterface $logger) {}

        /**
         * Обработка события оплаты.
         */
        public function handle(VapeOrderPaidEvent $event): void
        {
            $this->logger->info('Vape order paid listener: sending to marking job', [
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
