<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * MasterAvailabilityService — расчёт доступных слотов мастера.
 *
 * Проверяет расписание мастера, занятые записи и заблокированные часы.
 * Нарезает временну́ю шкалу по 30 мин и возвращает свободные окна.
 */
final readonly class MasterAvailabilityService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Получить доступные слоты для образа.
     *
     * @param array<string, mixed> $look Данные образа (содержит makeup_style)
     * @return Collection<int, string> Коллекция свободных временных меток (HH:MM)
     */
    public function getAvailableSlotsForLook(BeautyMaster $master, array $look, Carbon $date): Collection
    {
        $duration = $this->calculateDuration($look);

        $appointments = BeautyAppointment::where('master_id', $master->id)
            ->whereDate('start_at', $date)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->get(['start_at', 'end_at']);

        $schedule = $master->schedule ?? [];

        if (empty($schedule)) {
            return collect();
        }

        $availableSlots = collect();

        foreach ($schedule as $workingRange) {
            $start = Carbon::parse($date->toDateString() . ' ' . ($workingRange['start'] ?? '10:00'));
            $end = Carbon::parse($date->toDateString() . ' ' . ($workingRange['end'] ?? '18:00'));

            while ($start->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $start->copy()->addMinutes($duration);

                if (!$this->isSlotBusy($start, $slotEnd, $appointments)) {
                    $availableSlots->push($start->format('H:i'));
                }

                $start->addMinutes(30);
            }
        }

        $this->logger->debug('Available slots calculated', [
            'master_id' => $master->id,
            'date' => $date->toDateString(),
            'slots_count' => $availableSlots->count(),
        ]);

        return $availableSlots;
    }

    /**
     * Рассчитать длительность услуги по стилю макияжа (минуты).
     */
    private function calculateDuration(array $look): int
    {
        $style = $look['data']['makeup_style'] ?? 'daily';

        return match ($style) {
            'wedding', 'evening' => 120,
            'party' => 90,
            default => 60,
        };
    }

    /**
     * Проверить, занят ли слот записями.
     */
    private function isSlotBusy(Carbon $start, Carbon $end, Collection $busySlots): bool
    {
        foreach ($busySlots as $appointment) {
            $appStart = Carbon::parse($appointment->start_at);
            $appEnd = $appointment->end_at
                ? Carbon::parse($appointment->end_at)
                : $appStart->copy()->addMinutes(60);

            if ($start->lt($appEnd) && $end->gt($appStart)) {
                return true;
            }
        }

        return false;
    }
}
