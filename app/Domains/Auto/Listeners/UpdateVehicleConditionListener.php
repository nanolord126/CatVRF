<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\AIDiagnosticsCompletedEvent;
use App\Domains\Auto\Models\AutoVehicle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class UpdateVehicleConditionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AIDiagnosticsCompletedEvent $event): void
    {
        $overallCondition = $event->diagnosticsData['damage_detection']['overall_condition'] ?? 8;
        $damageCount = count($event->diagnosticsData['damage_detection']['damages'] ?? []);

        DB::table('auto_vehicles')
            ->where('id', $event->vehicle->id)
            ->where('tenant_id', $event->tenantId)
            ->update([
                'condition_rating' => $overallCondition,
                'last_diagnostics_at' => now(),
                'metadata' => DB::raw("jsonb_set(
                    COALESCE(metadata, '{}'::jsonb),
                    '{last_diagnostics_correlation_id}',
                    '\"{$event->correlationId}\"'::jsonb
                )"),
                'updated_at' => now(),
            ]);

        Log::channel('audit')->info('auto.vehicle_condition.updated', [
            'correlation_id' => $event->correlationId,
            'vehicle_id' => $event->vehicle->id,
            'condition_rating' => $overallCondition,
            'damage_count' => $damageCount,
        ]);
    }
}
