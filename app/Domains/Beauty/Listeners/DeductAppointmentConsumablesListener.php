<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Domain\Services\ConsumableDeductionServiceInterface;
use App\Domains\Beauty\Events\AppointmentCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
final class DeductAppointmentConsumablesListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Очередь для обработки — изолированная от основного потока.
     */
    public string $queue = 'beauty_consumables';

    /**
     * Количество попыток перед сдачей в failedJobs.
     */
    public int $tries = 3;

    /**
     * Задержка между повторными попытками (секунды).
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private ConsumableDeductionServiceInterface $deductionService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Обработать событие — списать расходники после завершения визита.
     */
    public function handle(AppointmentCompleted $event): void
    {
        $this->logger->info('Beauty: начало списания расходников', [
            'appointment_id' => $event->appointmentId,
            'service_id'     => $event->serviceId,
            'correlation_id' => $event->correlationId,
        ]);

        try {
            $this->deductionService->deductForAppointment(
                appointmentId: $event->appointmentId,
                serviceId: $event->serviceId,
                correlationId: $event->correlationId,
            );

            $this->logger->info('Beauty: расходники списаны', [
                'appointment_id' => $event->appointmentId,
                'service_id'     => $event->serviceId,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Beauty: ошибка списания расходников', [
                'appointment_id' => $event->appointmentId,
                'service_id'     => $event->serviceId,
                'correlation_id' => $event->correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Обработать окончательный сбой (после всех попыток).
     */
    public function failed(AppointmentCompleted $event, \Throwable $exception): void
    {
        $this->logger->critical('Beauty: списание расходников провалилось (все попытки)', [
            'appointment_id' => $event->appointmentId,
            'service_id'     => $event->serviceId,
            'correlation_id' => $event->correlationId,
            'error'          => $exception->getMessage(),
        ]);
    }
}
