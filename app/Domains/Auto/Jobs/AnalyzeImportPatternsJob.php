<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final class AnalyzeImportPatternsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $analysisDate,
        public readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $cacheKey = "import:analysis:$this->tenantId:$this->analysisDate";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return;
        }

        $imports = DB::table('car_imports')
            ->where('tenant_id', $this->tenantId)
            ->whereDate('created_at', $this->analysisDate)
            ->get();

        $totalImports = $imports->count();
        $totalValue = $imports->sum('declared_value');
        $totalDuties = 0;

        $countryDistribution = [];
        $engineTypeDistribution = [];
        $statusDistribution = [];

        foreach ($imports as $import) {
            $importData = json_decode($import->metadata ?? '{}', true);
            $totalDuties += $importData['paid_amount'] ?? 0;

            $country = $import->country_origin;
            $countryDistribution[$country] = ($countryDistribution[$country] ?? 0) + 1;

            $engineType = $import->engine_type;
            $engineTypeDistribution[$engineType] = ($engineTypeDistribution[$engineType] ?? 0) + 1;

            $status = $import->status;
            $statusDistribution[$status] = ($statusDistribution[$status] ?? 0) + 1;
        }

        $analysisData = [
            'date' => $this->analysisDate,
            'total_imports' => $totalImports,
            'total_value' => $totalValue,
            'total_duties' => $totalDuties,
            'average_duties_per_import' => $totalImports > 0 ? $totalDuties / $totalImports : 0,
            'country_distribution' => $countryDistribution,
            'engine_type_distribution' => $engineTypeDistribution,
            'status_distribution' => $statusDistribution,
            'analyzed_at' => now()->toIso8601String(),
        ];

        DB::table('car_import_analytics')->insert([
            'tenant_id' => $this->tenantId,
            'analysis_date' => $this->analysisDate,
            'analysis_data' => json_encode($analysisData),
            'correlation_id' => $this->correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::put($cacheKey, true, 86400);

        Log::channel('audit')->info('car.import.patterns.analyzed', [
            'tenant_id' => $this->tenantId,
            'analysis_date' => $this->analysisDate,
            'correlation_id' => $this->correlationId,
            'total_imports' => $totalImports,
        ]);
    }
}
