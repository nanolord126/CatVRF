<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления свадебными группами.
 * Реализует специфическую логику штрафов и предоплат Beauty 2026.
 */
final readonly class WeddingGroupService
{
    /**
     * Рассчитывает параметры штрафов и предоплат для свадебной группы.
     * 
     * @param Appointment \
     * @param string \ ('cancel', 'reschedule', 'prepayment')
     * @return array ['multiplier' => float, 'fee_percent' => int, 'prepayment' => int]
     */
    public function calculateWeddingFees(Appointment \, string \): array
    {
        if (!\->is_wedding_group) {
            return [
                'multiplier' => 1.0,
                'fee_percent' => 0,
                'prepayment' => 0
            ];
        }

        \ = Carbon::now();
        \ = Carbon::parse(\->datetime_start);
        \ = \->diffInDays(\, false);
        \ = \->diffInHours(\, false);

        // Базовые правила штрафов для свадеб (Wedding Canon 2026)
        \ = match (true) {
            \ > 14 => 0,
            \ >= 7 => 25,
            \ >= 3 => 50,
            \ >= 72 => 75,
            default => 100, // < 72 часов - полный штраф
        };

        // Увеличение штрафа при использовании AI Look (+15% к итоговому проценту)
        if (isset(\->metadata['ai_look_id']) || (\->tags['ai_generated'] ?? false)) {
            \ = min(100, \ + 15);
        }

        // Групповой коэффициент (из GroupBookingService или фиксированный для свадеб)
        \ = match (true) {
            \->group_size >= 10 => 1.8,
            \->group_size >= 5  => 1.5,
            default => 1.2,
        };

        // Логика предоплат для свадебных групп
        \ = match (true) {
            (\->group_size ?? 0) >= 10 => 100, // Свадьба > 10 чел - 100% предоплата
            (\->group_size ?? 0) >= 5  => 75,
            default => 50,
        };

        Log::channel('audit')->info('Wedding fees calculated', [
            'appointment_id' => \->id,
            'action'         => \,
            'fee_percent'    => \,
            'multiplier'     => \,
            'prepayment'     => \,
            'correlation_id' => \->correlation_id,
        ]);

        return [
            'multiplier'  => \,
            'fee_percent' => \,
            'prepayment'  => \,
        ];
    }
}
