<?php declare(strict_types=1);

namespace Modules\Analytics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RFMService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Выполняет RFM-анализ для всех пользователей тенанта.
         * Recency: Дней с последнего заказа.
         * Frequency: Количество заказов за период.
         * Monetary: Общий объем трат.
         */
        public function calculateRFM(): void
        {
            $users = User::all();
    
            foreach ($users as $user) {
                $stats = BehavioralEvent::where('user_id', $user->id)
                    ->whereIn('event_type', ['order_completed', 'booking_confirmed'])
                    ->select(
                        DB::raw('MAX(occurred_at) as last_order'),
                        DB::raw('COUNT(*) as frequency'),
                        DB::raw('SUM(monetary_value) as total_monetary')
                    )
                    ->first();
    
                if (!$stats->last_order) continue;
    
                $recency = now()->diffInDays($stats->last_order);
                
                // Простейшая логика сегментации (в проде заменить на процентили)
                $segment = 'Regular';
                if ($recency > 30 && $stats->frequency < 2) $segment = 'At-Risk';
                if ($stats->total_monetary > 10000 || $stats->frequency > 20) $segment = 'VIP';
                if ($recency < 7 && $stats->frequency > 5) $segment = 'Loyal';
    
                CustomerSegment::updateOrCreate(
                    ['user_id' => $user->id, 'segment_type' => 'rfm'],
                    [
                        'value' => $segment,
                        'score' => $this->calculateScore($recency, $stats->frequency, $stats->total_monetary),
                        'metadata' => [
                            'recency_days' => $recency,
                            'frequency' => $stats->frequency,
                            'monetary' => $stats->total_monetary
                        ]
                    ]
                );
            }
        }
    
        private function calculateScore($r, $f, $m): int
        {
            // Упрощенный скоринг 1-5
            return (int) (($f * 2) + ($m / 1000) - ($r / 10));
        }
}
