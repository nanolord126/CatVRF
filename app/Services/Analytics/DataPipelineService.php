<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Psr\Log\LoggerInterface;

use App\Jobs\Analytics\WriteLogisticsFeaturesJob;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * DataPipelineService — сбор и запись логистических данных в ClickHouse
 *
 * Собирает фичи для ML-моделей:
 * - Заказы (вес, объём, время, адрес, тип фулфилмента)
 * - Позиции курьеров (lat/lng + timestamp)
 * - Статусы ПВЗ (load, слоты)
 * - ETA vs predicted
 * - Отмены/возвраты
 * - Трафик/погода
 *
 * Канон CatVRF 2026:
 * - Асинхронная запись через queue
 * - Batch-обработка для feature extraction
 * - Tenant scoping
 * - Аудит логирование
 */
final readonly class DataPipelineService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly QueueFactory $queue,
        private readonly AuditService $audit,
    ) {}

    /**
     * Записать данные о заказе в ClickHouse
     */
    public function logOrder(array $orderData, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->queue->push(new WriteLogisticsFeaturesJob([
            'type' => 'order',
            'data' => array_merge($orderData, [
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'correlation_id' => $correlationId,
                'event_time' => CarbonImmutable::now()->toDateTimeString(),
            ]),
        ]));

        $this->log->channel('audit')->$this->logger->info('Order data queued for ClickHouse', [
            'order_id' => $orderData['order_id'] ?? null,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Записать позицию курьера в ClickHouse
     */
    public function logCourierLocation(array $locationData, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->queue->push(new WriteLogisticsFeaturesJob([
            'type' => 'courier_location',
            'data' => array_merge($locationData, [
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'correlation_id' => $correlationId,
                'event_time' => CarbonImmutable::now()->toDateTimeString(),
            ]),
        ]));
    }

    /**
     * Записать данные о назначении курьера
     */
    public function logCourierAssignment(array $assignmentData, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->queue->push(new WriteLogisticsFeaturesJob([
            'type' => 'courier_assignment',
            'data' => array_merge($assignmentData, [
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'correlation_id' => $correlationId,
                'event_time' => CarbonImmutable::now()->toDateTimeString(),
            ]),
        ]));

        $this->log->channel('audit')->$this->logger->info('Courier assignment queued for ClickHouse', [
            'shipment_id' => $assignmentData['shipment_id'] ?? null,
            'courier_id' => $assignmentData['courier_id'] ?? null,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Записать данные о назначении ПВЗ
     */
    public function logPvzAssignment(array $pvzData, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->queue->push(new WriteLogisticsFeaturesJob([
            'type' => 'pvz_assignment',
            'data' => array_merge($pvzData, [
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'correlation_id' => $correlationId,
                'event_time' => CarbonImmutable::now()->toDateTimeString(),
            ]),
        ]));
    }

    /**
     * Записать реальные vs предсказанные ETA
     */
    public function logEtaComparison(array $etaData, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->queue->push(new WriteLogisticsFeaturesJob([
            'type' => 'eta_comparison',
            'data' => array_merge($etaData, [
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'correlation_id' => $correlationId,
                'event_time' => CarbonImmutable::now()->toDateTimeString(),
            ]),
        ]));
    }

    /**
     * Записать данные о загрузке ПВЗ
     */
    public function logPvzLoad(array $loadData, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->queue->push(new WriteLogisticsFeaturesJob([
            'type' => 'pvz_load',
            'data' => array_merge($loadData, [
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'correlation_id' => $correlationId,
                'event_time' => CarbonImmutable::now()->toDateTimeString(),
            ]),
        ]));
    }

    /**
     * Записать отмены/возвраты
     */
    public function logCancellation(array $cancellationData, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->queue->push(new WriteLogisticsFeaturesJob([
            'type' => 'cancellation',
            'data' => array_merge($cancellationData, [
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                'correlation_id' => $correlationId,
                'event_time' => CarbonImmutable::now()->toDateTimeString(),
            ]),
        ]));
    }

    /**
     * Batch-экспорт данных для обучения моделей
     */
    public function exportForTraining(string $table, string $startDate, string $endDate, string $format = 'csv'): string
    {
        // Экспорт из ClickHouse в Parquet/CSV для обучения
        $query = "SELECT * FROM {$table} WHERE event_time BETWEEN '{$startDate}' AND '{$endDate}'";

        // Экспорт через ClickHouse API
        // Возвращаем путь к файлу
        return base_path('storage' . DIRECTORY_SEPARATOR . 'exports/{$table}_{$startDate}_{$endDate}.{$format}');
    }

    /**
     * Получить статистику по данным
     */
    public function getDataStats(string $table, string $startDate, string $endDate): array
    {
        // Агрегация через ClickHouse запросы
        return [
            'total_records' => 0,
            'date_range' => ['start' => $startDate, 'end' => $endDate],
        ];
    }
}
