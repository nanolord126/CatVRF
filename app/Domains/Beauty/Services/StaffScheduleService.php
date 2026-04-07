<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * StaffScheduleService — генерация и просмотр расписания мастеров.
 *
 * Создаёт временны́е слоты по 30 мин в рабочие часы и проверяет
 * доступность через fraud-check и correlation_id.
 */
final readonly class StaffScheduleService
{
    public function __construct(
        private FraudControlService $fraud,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Автоматически сгенерировать график мастера на основе правил.
     */
    public function generateSchedule(BeautyMaster $master, Carbon $from, Carbon $to, string $correlationId = ''): Collection
    {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_generate_schedule',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $this->logger->info('Generating master schedule', [
                'master_id' => $master->id,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'correlation_id' => $correlationId,
            ]);

            $slots = collect();
            $current = $from->copy()->hour(10)->minute(0)->second(0);
            $endTime = $to->copy()->hour(18)->minute(0)->second(0);

            while ($current->lessThan($endTime)) {
                $slots->push([
                    'start' => $current->copy()->toDateTimeString(),
                    'end' => $current->copy()->addMinutes(30)->toDateTimeString(),
                    'available' => true,
                ]);

                $current->addMinutes(30);
            }

            return $slots;
        } catch (\Throwable $e) {
            $this->logger->error('Schedule generation failed', [
                'master_id' => $master->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Получить доступные слоты мастера на дату.
     */
    public function getAvailableSlots(BeautyMaster $master, Carbon $date, string $correlationId = ''): Collection
    {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_available_slots',
            amount: 0,
            correlationId: $correlationId,
        );

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        return $this->generateSchedule($master, $start, $end, $correlationId);
    }
}
