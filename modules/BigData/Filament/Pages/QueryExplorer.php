<?php

declare(strict_types=1);

namespace Modules\BigData\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Modules\BigData\Application\Services\BigDataService;

/**
 * Query Explorer Page
 *
 * Safe SQL query interface for analysts to query ClickHouse.
 * Restricted to admin users with proper permissions.
 */
class QueryExplorer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code';
    protected static ?string $navigationLabel = 'Query Explorer';
    protected static ?string $navigationGroup = 'Analytics';
    protected static string $view = 'bigdata.filament.pages.query-explorer';

    public BigDataService $bigData;

    public string $query = '';
    public array $results = [];
    public string $error = '';
    public int $executionTime = 0;
    public int $rowCount = 0;

    public function mount(BigDataService $bigData): void
    {
        $this->bigData = $bigData;
    }

    public function executeQuery(): void
    {
        $this->validate([
            'query' => 'required|string|max:10000',
        ]);

        if (!Auth::user()?->can('bigdata.query')) {
            $this->error = 'You do not have permission to execute queries.';
            return;
        }

        $startTime = microtime(true);

        try {
            $this->results = $this->bigData->executeQuery($this->query);
            $this->rowCount = count($this->results);
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->results = [];
            $this->rowCount = 0;
        }

        $this->executionTime = (int) ((microtime(true) - $startTime) * 1000);
    }

    public function getExampleQueries(): array
    {
        return [
            'Daily GMV' => "SELECT metric_date, SUM(gmv_total) as gmv FROM ch_daily_metrics WHERE tenant_id = 1 AND metric_date >= today() - INTERVAL 30 DAY GROUP BY metric_date ORDER BY metric_date",
            'Top Sellers' => "SELECT seller_id, SUM(gmv_total) as total_gmv FROM ch_seller_daily_metrics WHERE tenant_id = 1 AND metric_date >= today() - INTERVAL 30 DAY GROUP BY seller_id ORDER BY total_gmv DESC LIMIT 10",
            'Event Counts' => "SELECT event_type, COUNT(*) as count FROM ch_raw_events WHERE tenant_id = 1 AND created_at >= now() - INTERVAL 1 HOUR GROUP BY event_type ORDER BY count DESC",
            'CLV Distribution' => "SELECT clv_segment, COUNT(*) as user_count FROM ch_clv_predictions WHERE tenant_id = 1 GROUP BY clv_segment",
        ];
    }

    public function loadExample(string $example): void
    {
        $this->query = $example;
    }
}
