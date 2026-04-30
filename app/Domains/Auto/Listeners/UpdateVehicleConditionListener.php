<?php

declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Auto\Events\AIDiagnosticsCompletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\CarbonImmutable;

final class UpdateVehicleConditionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    public function handle(AIDiagnosticsCompletedEvent $event): void
    {
        $overallCondition = $event->diagnosticsData['damage_detection']['overall_condition'] ?? 8;
        $damageCount = count($event->diagnosticsData['damage_detection']['damages'] ?? []);

        $this->db->table('auto_vehicles')
            ->where('id', $event->vehicle->id)
            ->where('tenant_id', $event->tenantId)
            ->update([
                'condition_rating' => $overallCondition,
                'last_diagnostics_at' => CarbonImmutable::now(),
                'metadata' => $this->db->raw("jsonb_set(
                    COALESCE(metadata, '{}'::jsonb),
                    '{last_diagnostics_correlation_id}',
                    '\"{$event->correlationId}\"'::jsonb
                )"),
                'updated_at' => CarbonImmutable::now(),
            ]);

        $this->log->channel('audit')->$this->logger->info('auto.vehicle_condition.updated', [
            'correlation_id' => $event->correlationId,
            'vehicle_id' => $event->vehicle->id,
            'condition_rating' => $overallCondition,
            'damage_count' => $damageCount,
        ]);
    }
}
