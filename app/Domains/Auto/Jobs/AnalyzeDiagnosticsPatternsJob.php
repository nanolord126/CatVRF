<?php declare(strict_types=1);

namespace App\Domains\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class AnalyzeDiagnosticsPatternsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $tenantId,
    ) {}

    public function handle(): void
    {
        $yesterday = Carbon::yesterday()->startOfDay();
        $today = Carbon::today()->startOfDay();

        $diagnostics = DB::table('auto_diagnostics_history')
            ->where('tenant_id', $this->tenantId)
            ->whereBetween('created_at', [$yesterday, $today])
            ->get();

        $totalDiagnostics = $diagnostics->count();
        $totalDamages = 0;
        $criticalDamages = 0;
        $damageTypes = [];

        foreach ($diagnostics as $diagnostic) {
            $data = json_decode($diagnostic->diagnostics_data, true);
            $damageDetection = $data['damage_detection'] ?? [];

            $totalDamages += $damageDetection['total_count'] ?? 0;
            $criticalDamages += $damageDetection['critical_count'] ?? 0;

            foreach ($damageDetection['damages'] ?? [] as $damage) {
                $type = $damage['type'] ?? 'unknown';
                $damageTypes[$type] = ($damageTypes[$type] ?? 0) + 1;
            }
        }

        $analysisResult = [
            'tenant_id' => $this->tenantId,
            'date' => $yesterday->toDateString(),
            'total_diagnostics' => $totalDiagnostics,
            'total_damages' => $totalDamages,
            'critical_damages' => $criticalDamages,
            'damage_types' => $damageTypes,
            'avg_damages_per_vehicle' => $totalDiagnostics > 0 ? $totalDamages / $totalDiagnostics : 0,
            'critical_damage_rate' => $totalDamages > 0 ? $criticalDamages / $totalDamages : 0,
        ];

        DB::table('auto_diagnostics_analytics')->insert([
            'tenant_id' => $this->tenantId,
            'analysis_date' => $yesterday,
            'analysis_data' => json_encode($analysisResult),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::channel('audit')->info('auto.diagnostics_patterns.analyzed', [
            'tenant_id' => $this->tenantId,
            'date' => $yesterday->toDateString(),
            'total_diagnostics' => $totalDiagnostics,
        ]);
    }
}
