<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\ConsumablesDepleted;
use App\Domains\Beauty\Jobs\NotifyLowConsumablesJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * HandleConsumablesDepletedListener — CatVRF 2026.
 *
 * Диспатчит уведомление при исчерпании расходников.
 * Runs asynchronously via queue (ShouldQueue).
 * Maintains correlation_id chain.
 *
 * @package App\Domains\Beauty\Listeners
 */
final class HandleConsumablesDepletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private Dispatcher $bus,
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(ConsumablesDepleted $event): void
    {
        $this->bus->dispatch(new NotifyLowConsumablesJob($event->correlationId));

        $this->auditLogger->warning('Consumable depleted below threshold.', [
            'consumable_id'  => $event->consumableId,
            'name'           => $event->consumableName,
            'quantity'       => $event->quantity,
            'tenant_id'      => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(ConsumablesDepleted $event, \Throwable $exception): void
    {
        $this->auditLogger->error('HandleConsumablesDepletedListener failed.', [
            'consumable_id'  => $event->consumableId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(ConsumablesDepleted $event): bool
    {
        return $event->consumableId > 0;
    }

    /**
     * Очередь для обработки события.
     */
    public function viaQueue(): string
    {
        return 'beauty-inventory';
    }
}
