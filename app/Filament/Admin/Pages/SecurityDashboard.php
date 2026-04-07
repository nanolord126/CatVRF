<?php declare(strict_types=1);

namespace App\Filament\Admin\Pages;


use Illuminate\Database\DatabaseManager;
use App\Filament\Admin\Widgets\FraudAttemptsWidget;
use App\Filament\Admin\Widgets\FailedLoginsWidget;
use App\Filament\Admin\Widgets\SecurityEventsChartWidget;
use Filament\Pages\Page;
use Filament\Actions\Action;

/**
 * SecurityDashboard — реал-тайм мониторинг безопасности.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Доступен только в Admin Panel (/admin/security).
 * Получает данные через Laravel Echo broadcast из SecurityMonitoringService.
 * Виджеты: FailedLogins, FraudAttempts, SecurityEventsChart, LiveGeoMap.
 */
final class SecurityDashboard extends Page
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Безопасность';
    protected static ?string $navigationGroup = 'Platform Management';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.admin.pages.security-dashboard';

    /**
     * Лайв-счётчики — обновляются через Livewire polling каждые 10 сек.
     */
    public int   $criticalCount   = 0;
    public int   $highCount       = 0;
    public int   $warningCount    = 0;
    public int   $blockedToday    = 0;
    public array $latestEvents    = [];

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $since = now()->startOfDay();

        $this->blockedToday = $this->db->table('fraud_attempts')
            ->where('decision', 'block')
            ->where('created_at', '>=', $since)
            ->count();

        $bySeverity = $this->db->table('fraud_attempts')
            ->where('created_at', '>=', $since)
            ->selectRaw("decision, COUNT(*) as cnt")
            ->groupBy('decision')
            ->pluck('cnt', 'decision');

        $this->criticalCount = (int) ($bySeverity['block']   ?? 0);
        $this->highCount     = (int) ($bySeverity['review']  ?? 0);
        $this->warningCount  = (int) ($bySeverity['allow']   ?? 0);

        $this->latestEvents = $this->db->table('fraud_attempts')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get([
                'id',
                'user_id',
                'operation_type',
                'ml_score',
                'decision',
                'ip_address',
                'correlation_id',
                'created_at',
            ])
            ->map(static fn ($row) => (array) $row)
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action('refresh'),
        ];
    }

    public function getWidgets(): array
    {
        return [
            FailedLoginsWidget::class,
            FraudAttemptsWidget::class,
            SecurityEventsChartWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 3;
    }
}
