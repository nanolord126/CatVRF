<?php

declare(strict_types=1);

namespace App\Listeners\Logistics;

use Psr\Log\LoggerInterface;

use App\Events\Logistics\PvzAssigned;
use App\Services\Analytics\DataPipelineService;
use Illuminate\Log\LogManager;

/**
 * LogPvzAssignmentToClickHouse — логирование назначений ПВЗ
 *
 * Записывает данные о назначении в ClickHouse для обучения ML-модели.
 */
final class LogPvzAssignmentToClickHouse
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly DataPipelineService $dataPipeline,) {}

    public function handle(PvzAssigned $event): void
    {
        $this->dataPipeline->logPvzAssignment([
            'shipment_id' => $event->shipment->id,
            'order_id' => $event->shipment->order_id,
            'pvz_id' => $event->pickupPoint->id,
            'pvz_name' => $event->pickupPoint->name,
            'pvz_lat' => $event->pickupPoint->lat,
            'pvz_lon' => $event->pickupPoint->lng,
            'distance_m' => $event->distance,
            'pvz_capacity' => $event->pickupPoint->capacity_slots,
            'pvz_load_before' => $event->pickupPoint->current_load,
            'pvz_load_after' => $event->pickupPoint->current_load + 1,
            'pvz_is_24h' => $event->pickupPoint->is_24h ? 1 : 0,
            'pvz_working_hours' => $event->pickupPoint->working_hours,
            'user_preference_score' => 0.5, // TODO: рассчитать на основе истории
        ], $event->shipment->correlation_id ?? '');

        $this->log->channel('audit')->$this->logger->info('PVZ assignment logged to ClickHouse', [
            'shipment_id' => $event->shipment->id,
            'pvz_id' => $event->pickupPoint->id,
        ]);
    }
}
