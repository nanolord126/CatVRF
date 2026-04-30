<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use LogManager;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Models\Appointment;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

final class CleanupExpiredAppointmentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * CleanupExpiredAppointmentsJob — очистка просроченных записей.
     * Канон CatVRF 2026 — PRODUCTION MANDATORY.
     *
     * Запускается каждую минуту через Scheduler.
     * Отменяет записи, резерв которых истёк (20 минут по канону корзин).
     * Логирует все изменения через AuditService + correlation_id.
     */

    /**
     * Максимальное количество попыток.
     */
    public int $tries = 3;

    /**
     * Таймаут (секунды).
     */
    public int $timeout = 120;

    public array $backoff = [60, 300, 900];

    public function __construct(private readonly LogManager $logManager,
        private readonly LoggerInterface $logger,
        private readonly string $correlationId,) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['beauty', 'cleanup-expired', 'correlation:'.$this->correlationId];
    }

    /**
     * Выполнение задания.
     */
    public function handle(
        LogManager $logger,
        AuditService $audit,
    ): void {
        $expiredAppointments = Appointment::query()
            ->where('status', 'pending')
            ->where('created_at', '<', new DateTime()->subMinutes(20))
            ->get();

        if ($expiredAppointments->isEmpty()) {
            return;
        }

        $cancelledCount = 0;

        foreach ($expiredAppointments as $appointment) {
            $appointment->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'reservation_expired',
            ]);

            $audit->log(
                action: 'appointment_expired',
                subjectType: Appointment::class,
                subjectId: $appointment->getKey(),
                old: ['status' => 'pending'],
                new: ['status' => 'cancelled'],
                correlationId: $this->correlationId,
            );

            $cancelledCount++;
        }

        $logger->channel('audit')->$this->logger->info('Expired appointments cleanup completed', [
            'correlation_id'  => $this->correlationId,
            'cancelled_count' => $cancelledCount,
        ]);
    }

    /**
     * Обработка провала задания.
     */
    public function failed(Exception $exception): void
    {
        $this->logManager /* TODO: inject via constructor DI */ /* TODO: inject via DI */->channel('audit')->error('CleanupExpiredAppointmentsJob failed', [
            'correlation_id' => $this->correlationId,
            'error'          => $exception->getMessage(),
        ]);
    }
}
