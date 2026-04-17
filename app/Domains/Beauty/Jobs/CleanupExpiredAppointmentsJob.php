<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * CleanupExpiredAppointmentsJob — очистка просроченных записей.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Запускается каждую минуту через Scheduler.
 * Отменяет записи, резерв которых истёк (20 минут по канону корзин).
 * Логирует все изменения через AuditService + correlation_id.
 */
final class CleanupExpiredAppointmentsJob implements ShouldQueue
{

    /**
     * Максимальное количество попыток.
     */
    public int $tries = 3;

    /**
     * Таймаут (секунды).
     */
    public int $timeout = 120;

    public function __construct(
        private readonly string $correlationId,
    ) {}

    /**
     * Выполнение задания.
     */
    public function handle(
        LogManager $logger,
        AuditService $audit,
    ): void {
        $expiredAppointments = Appointment::query()
            ->where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes(20))
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

        $logger->channel('audit')->info('Expired appointments cleanup completed', [
            'correlation_id'  => $this->correlationId,
            'cancelled_count' => $cancelledCount,
        ]);
    }

    /**
     * Обработка провала задания.
     */
    public function failed(\Throwable $exception): void
    {
        app(LogManager::class)->channel('audit')->error('CleanupExpiredAppointmentsJob failed', [
            'correlation_id' => $this->correlationId,
            'error'          => $exception->getMessage(),
        ]);
    }
}




