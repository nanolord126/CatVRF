<?php

declare(strict_types=1);

namespace Modules\Analytics\Services;

use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Modules\Analytics\Models\BehavioralEvent;
use Modules\Analytics\Models\CustomerSegment;
use Modules\Common\Services\AbstractTechnicalVerticalService;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final class RFMService extends AbstractTechnicalVerticalService
{
    use WithAuditLogging;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly AuditService $auditService,
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->tenant->settings['rfm_enabled'] ?? true;
    }

    /**
     * Выполняет RFM-анализ для всех пользователей тенанта.
     * Recency: Дней с последнего заказа.
     * Frequency: Количество заказов за период.
     * Monetary: Общий объем трат.
     */
    public function calculateRFM(): void
    {
        // Single query to get all user stats at once (N+1 fix)
        $userStats = BehavioralEvent::whereIn('event_type', ['order_completed', 'booking_confirmed'])
            ->select(
                'user_id',
                $this->db->raw('MAX(occurred_at) as last_order'),
                $this->db->raw('COUNT(*) as frequency'),
                $this->db->raw('SUM(monetary_value) as total_monetary')
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        foreach ($userStats as $userId => $stats) {
            if (! $stats->last_order) {
                continue;
            }

            $recency = now()->diffInDays($stats->last_order);

            // Простейшая логика сегментации (в проде заменить на процентили)
            $segment = 'Regular';
            if ($recency > 30 && $stats->frequency < 2) {
                $segment = 'At-Risk';
            }
            if ($stats->total_monetary > 10000 || $stats->frequency > 20) {
                $segment = 'VIP';
            }
            if ($recency < 7 && $stats->frequency > 5) {
                $segment = 'Loyal';
            }

            CustomerSegment::updateOrCreate(
                ['user_id' => $userId, 'segment_type' => 'rfm'],
                [
                    'value' => $segment,
                    'score' => $this->calculateScore($recency, $stats->frequency, $stats->total_monetary),
                    'metadata' => [
                        'recency_days' => $recency,
                        'frequency' => $stats->frequency,
                        'monetary' => $stats->total_monetary,
                    ],
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
