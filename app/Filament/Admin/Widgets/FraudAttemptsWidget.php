<?php declare(strict_types=1);

namespace App\Filament\Admin\Widgets;


use Illuminate\Database\DatabaseManager;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * Виджет — fraud-попытки: статистика решений за сегодня.
 * Канон CatVRF 2026: Admin Panel → SecurityDashboard.
 */
final class FraudAttemptsWidget extends StatsOverviewWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today   = now()->startOfDay();
        $last7d  = now()->subDays(7);

        $byDecision = $this->db->table('fraud_attempts')
            ->where('created_at', '>=', $today)
            ->selectRaw("decision, COUNT(*) as cnt")
            ->groupBy('decision')
            ->pluck('cnt', 'decision');

        $blocked  = (int) ($byDecision['block']  ?? 0);
        $reviewed = (int) ($byDecision['review'] ?? 0);
        $allowed  = (int) ($byDecision['allow']  ?? 0);

        // Средний ML-score за сегодня
        $avgScore = $this->db->table('fraud_attempts')
            ->where('created_at', '>=', $today)
            ->whereNotNull('ml_score')
            ->avg('ml_score');

        // Тренд блокировок последних 7 дней
        $trend = $this->db->table('fraud_attempts')
            ->where('decision', 'block')
            ->where('created_at', '>=', $last7d)
            ->selectRaw("DATE(created_at) as day, COUNT(*) as cnt")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt')
            ->toArray();

        // Топ операций по блокировкам
        $topOp = $this->db->table('fraud_attempts')
            ->where('decision', 'block')
            ->where('created_at', '>=', $today)
            ->selectRaw("operation_type, COUNT(*) as cnt")
            ->groupBy('operation_type')
            ->orderByDesc('cnt')
            ->value('operation_type');

        return [
            Stat::make('Заблокировано (сегодня)', $blocked)
                ->description("Топ операция: " . ($topOp ?? 'нет'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($blocked > 100 ? 'danger' : ($blocked > 20 ? 'warning' : 'success'))
                ->chart($trend),

            Stat::make('На проверку (сегодня)', $reviewed)
                ->description('Требуют ручной проверки')
                ->descriptionIcon('heroicon-m-eye')
                ->color($reviewed > 50 ? 'warning' : 'gray'),

            Stat::make('Средний ML Score', number_format((float) ($avgScore ?? 0), 4))
                ->description('За сегодня по всем операциям')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color((float) ($avgScore ?? 0) > 0.5 ? 'danger' : 'success'),
        ];
    }
}
