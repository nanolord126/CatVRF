<?php declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;


use Illuminate\Database\DatabaseManager;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * AIConstructorUsageWidget — использование AI-конструкторов tenant'а.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Линейный график: количество AI-анализов по вертикалям за 14 дней.
 * Данные из таблицы user_ai_designs (tenant-scoped через orders).
 * Одна линия на каждую вертикаль (beauty, food, furniture, fashion и т.д.).
 */
final class AIConstructorUsageWidget extends ChartWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?int    $sort      = 3;
    protected static ?string $heading   = 'AI-конструкторы (14 дней)';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $tenantId = tenant()?->id;

        $days = 14;

        // Строим метки
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('d.m');
        }

        if (!$tenantId) {
            return [
                'datasets' => [],
                'labels'   => $labels,
            ];
        }

        // Получаем уникальных пользователей tenant'а
        $tenantUserIds = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        if (empty($tenantUserIds)) {
            return [
                'datasets' => [],
                'labels'   => $labels,
            ];
        }

        // Получаем вертикали
        $verticals = $this->db->table('user_ai_designs')
            ->whereIn('user_id', $tenantUserIds)
            ->where('created_at', '>=', now()->subDays($days))
            ->distinct()
            ->pluck('vertical')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($verticals)) {
            // Отдаём суммарный график
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $day  = now()->subDays($i)->startOfDay();
                $data[] = $this->db->table('user_ai_designs')
                    ->whereIn('user_id', $tenantUserIds)
                    ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                    ->count();
            }

            return [
                'datasets' => [
                    [
                        'label'       => 'Все вертикали',
                        'data'        => $data,
                        'borderColor' => '#6366f1',
                        'backgroundColor' => 'rgba(99,102,241,0.12)',
                        'fill'        => true,
                        'tension'     => 0.4,
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        // Цвета для разных вертикалей
        $palette = [
            '#6366f1', '#ec4899', '#f59e0b', '#10b981',
            '#3b82f6', '#8b5cf6', '#ef4444', '#14b8a6',
        ];

        $datasets = [];

        foreach ($verticals as $idx => $vertical) {
            $data = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $day    = now()->subDays($i)->startOfDay();
                $data[] = $this->db->table('user_ai_designs')
                    ->whereIn('user_id', $tenantUserIds)
                    ->where('vertical', $vertical)
                    ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                    ->count();
            }

            $color      = $palette[$idx % count($palette)];
            $datasets[] = [
                'label'           => ucfirst($vertical),
                'data'            => $data,
                'borderColor'     => $color,
                'backgroundColor' => str_replace(')', ', 0.1)', str_replace('rgb', 'rgba', $color)),
                'fill'            => false,
                'tension'         => 0.4,
                'borderWidth'     => 2,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels'   => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['stepSize' => 1, 'precision' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
