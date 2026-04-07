<?php declare(strict_types=1);

namespace App\Filament\Admin\Widgets;


use Illuminate\Database\DatabaseManager;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Виджет — график security-событий за 7 дней.
 * Канон CatVRF 2026: Admin Panel → SecurityDashboard.
 * Показывает block / review / allow по дням — цветовое разделение.
 */
final class SecurityEventsChartWidget extends ChartWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?string $heading = 'Security-события за 7 дней';
    protected static ?int    $sort    = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(static fn (int $i) => now()->subDays($i)->format('Y-m-d'));

        $raw = $this->db->table('fraud_attempts')
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->selectRaw("DATE(created_at) as day, decision, COUNT(*) as cnt")
            ->groupBy('day', 'decision')
            ->get()
            ->groupBy('day');

        $blocked  = [];
        $reviewed = [];
        $allowed  = [];

        foreach ($days as $day) {
            $group      = $raw->get($day, collect());
            $blocked[]  = (int) ($group->firstWhere('decision', 'block')?->cnt  ?? 0);
            $reviewed[] = (int) ($group->firstWhere('decision', 'review')?->cnt ?? 0);
            $allowed[]  = (int) ($group->firstWhere('decision', 'allow')?->cnt  ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Заблокировано',
                    'data'            => $blocked,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor'     => 'rgb(239, 68, 68)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                ],
                [
                    'label'           => 'На проверку',
                    'data'            => $reviewed,
                    'backgroundColor' => 'rgba(249, 115, 22, 0.2)',
                    'borderColor'     => 'rgb(249, 115, 22)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Пропущено',
                    'data'            => $allowed,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor'     => 'rgb(34, 197, 94)',
                    'borderWidth'     => 1,
                    'fill'            => true,
                ],
            ],
            'labels' => $days->map(static fn (string $d) => \Carbon\Carbon::parse($d)->format('d.m'))->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
