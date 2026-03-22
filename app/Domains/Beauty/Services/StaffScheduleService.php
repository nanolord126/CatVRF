<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Master;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис для управления графиком мастеров.
 * Production 2026.
 */
final class StaffScheduleService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}
    /**
     * Автоматически сгенерировать граф ик мастера на основе правил.
     */
    public function generateSchedule(Master $master, Carbon $from, Carbon $to, string $correlationId = ''): Collection
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId
        );

        try {
            Log::channel('audit')->info('Generating master schedule', [
                'master_id' => $master->id,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'correlation_id' => $correlationId,
            ]);
            // Возвращает коллекцию доступных слотов

            $slots = collect();

            // Пример: генерируем слоты по 30 минут с 10:00 до 18:00
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
            Log::channel('audit')->error('Schedule generation failed', [
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
    public function getAvailableSlots(Master $master, Carbon $date, string $correlationId = ''): Collection
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId
        );

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        return $this->generateSchedule($master, $start, $end, $correlationId);
    }
}
