<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления групповыми бронированиями (Vertical Beauty 2026).
 * 
 * Правила:
 * - 2-3 чел: +10% к базовому штрафу.
 * - 4-6 чел: +25% к базовому штрафу, 30% обязательная предоплата.
 * - 7-9 чел: +40% к базовому штрафу, 50% обязательная предоплата.
 * - 10-12 чел: +60% к базовому штрафу, 75% обязательная предоплата.
 * - 13+ чел: +100% к базовому штрафу (полная невозвратность при поздней отмене), 100% предоплата.
 */
final readonly class GroupBookingService
{
    /**
     * Рассчитать групповые параметры (штрафы и предоплату).
     * 
     * @param Appointment $appointment
     * @param string $action 'cancel' | 'reschedule' | 'book'
     * @return array
     */
    public function calculateGroupFees(Appointment $appointment, string $action): array
    {
        if (!$appointment->is_group) {
            return [
                'is_group' => false,
                'penalty_multiplier' => 1.0,
                'prepayment_percent' => 0,
                'is_prepayment_required' => false,
            ];
        }

        $size = $appointment->group_size ?? 1;

        $rules = match (true) {
            $size >= 13 => ['multiplier' => 2.0, 'prepayment' => 100],
            $size >= 10 => ['multiplier' => 1.6, 'prepayment' => 75],
            $size >= 7  => ['multiplier' => 1.4, 'prepayment' => 50],
            $size >= 4  => ['multiplier' => 1.25, 'prepayment' => 30],
            $size >= 2  => ['multiplier' => 1.1, 'prepayment' => 0],
            default    => ['multiplier' => 1.0, 'prepayment' => 0],
        };

        $result = [
            'is_group' => true,
            'group_size' => $size,
            'penalty_multiplier' => $rules['multiplier'],
            'prepayment_percent' => $rules['prepayment'],
            'is_prepayment_required' => $rules['prepayment'] > 0,
            'correlation_id' => $appointment->correlation_id,
        ];

        Log::channel('audit')->info('Group fees calculated', $result);

        return $result;
    }

    /**
     * Проверка необходимости предоплаты для группы.
     */
    public function requiresPrepayment(int $groupSize): bool
    {
        return $groupSize >= 4;
    }

    /**
     * Получить сумму предоплаты в копейках.
     */
    public function getPrepaymentAmount(int $totalPriceCents, int $groupSize): int
    {
        $percent = match (true) {
            $groupSize >= 13 => 100,
            $groupSize >= 10 => 75,
            $groupSize >= 7  => 50,
            $groupSize >= 4  => 30,
            default         => 0,
        };

        return (int)($totalPriceCents * ($percent / 100));
    }
}
