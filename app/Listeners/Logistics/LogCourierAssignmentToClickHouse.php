<?php

declare(strict_types=1);

namespace App\Listeners\Logistics;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Events\Logistics\CourierAssigned;
use App\Services\Analytics\DataPipelineService;
use Illuminate\Log\LogManager;

/**
 * LogCourierAssignmentToClickHouse — логирование назначений курьеров
 *
 * Записывает данные о назначении в ClickHouse для обучения ML-модели.
 */
final class LogCourierAssignmentToClickHouse
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly DataPipelineService $dataPipeline,) {}

    public function handle(CourierAssigned $event): void
    {
        $this->dataPipeline->logCourierAssignment([
            'shipment_id' => $event->shipment->id,
            'order_id' => $event->shipment->order_id,
            'courier_id' => $event->courier->id,
            'courier_vehicle_type' => $event->courier->vehicle_type,
            'courier_is_taxi' => $event->courier->is_taxi_driver ? 1 : 0,
            'courier_rating' => $event->courier->rating ?? 5.0,
            'courier_capacity_kg' => $event->courier->capacity_kg,
            'distance_m' => $event->distance,
            'eta_predicted' => $event->predictedEta,
            'assignment_time' => CarbonImmutable::now()->toDateTimeString(),
            'accepted' => 0, // Будет обновлено при подтверждении
            'rejected' => 0,
            'hour_of_day' => CarbonImmutable::now()->hour,
            'day_of_week' => CarbonImmutable::now()->dayOfWeek,
            'weather_condition' => 'unknown', // TODO: интегрировать погодный API
            'traffic_level' => 'unknown', // TODO: интегрировать трафик API
        ], $event->shipment->correlation_id ?? '');

        $this->log->channel('audit')->$this->logger->info('Courier assignment logged to ClickHouse', [
            'shipment_id' => $event->shipment->id,
            'courier_id' => $event->courier->id,
        ]);
    }
}
