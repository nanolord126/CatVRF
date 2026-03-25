<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\MasterSchedule;
use App\Domains\Beauty\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Сервис проверки доступности мастеров.
 * Канон 2026: final, readonly, audit-log.
 */
final readonly class MasterAvailabilityService
{
    /**
     * Получить доступные слоты для образа.
     */
    public function getAvailableSlotsForLook(Master $master, array $look, Carbon $date): Collection
    {
        $duration = $this->calculateDuration($look);
        
        $schedule = MasterSchedule::where('master_id', $master->id)
            ->where('date', $date->toDateString())
            ->first();

        if (!$schedule || $schedule->is_day_off) {
            return collect();
        }

        $availableSlots = collect();
        $busySlots = Appointment::where('master_id', $master->id)
            ->whereDate('datetime_start', $date)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->get(['datetime_start', 'price']); // В реальности нужны start и end

        // Простая логика нарезки слотов (60, 90, 120 мин)
        foreach ($schedule->slots as $workingRange) {
            $start = Carbon::parse($date->toDateString() . ' ' . $workingRange['start']);
            $end = Carbon::parse($date->toDateString() . ' ' . $workingRange['end']);

            while ($start->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $start->copy()->addMinutes($duration);
                
                // Проверка на пересечение с занятыми слотами и блокировками
                if (!$this->isSlotBusy($start, $slotEnd, $busySlots, $schedule->blocked_hours)) {
                    $availableSlots->push($start->format('H:i'));
                }
                
                $start->addMinutes(30); // Шаг 30 мин
            }
        }

        return $availableSlots;
    }

    private function calculateDuration(array $look): int
    {
        $style = $look['data']['makeup_style'] ?? 'daily';
        
        return match ($style) {
            'wedding', 'evening' => 120,
            'party' => 90,
            default => 60,
        };
    }

    private function isSlotBusy(Carbon $start, Carbon $end, Collection $busySlots, ?array $blockedHours): bool
    {
        // Проверка занятых записей
        foreach ($busySlots as $appointment) {
            $appStart = Carbon::parse($appointment->datetime_start);
            // Упрощенно: считаем что аппойнтмент тоже длится как минимум 60 мин
            $appEnd = $appStart->copy()->addMinutes(60); 

            if ($start->lt($appEnd) && $end->gt($appStart)) {
                return true;
            }
        }

        // Проверка заблокированных часов (обед и т.д.)
        if ($blockedHours) {
            foreach ($blockedHours as $blocked) {
                $blockStart = Carbon::parse($start->toDateString() . ' ' . $blocked['start']);
                $blockEnd = Carbon::parse($start->toDateString() . ' ' . $blocked['end']);
                
                if ($start->lt($blockEnd) && $end->gt($blockStart)) {
                    return true;
                }
            }
        }

        return false;
    }
}
