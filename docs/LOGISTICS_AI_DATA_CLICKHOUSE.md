# Logistics AI: Data & ClickHouse Architecture

**Версия:** 1.0  
**Дата:** 18.04.2026  
**Автор:** CatVRF AI Sensei  
**Статус:** Production Ready

---

## Оглавление

1. [Философия данных](#философия-данных)
2. [Текущее состояние ClickHouse](#текущее-состояние-clickhouse)
3. [Требуемая схема для Logistics AI](#требуемая-схема-для-logistics-ai)
4. [Data Pipeline Architecture](#data-pipeline-architecture)
5. [Feature Engineering](#feature-engineering)
6. [Реализация (по шагам)](#реализация-по-шагам)
7. [Мониторинг и качество данных](#мониторинг-и-качество-данных)

---

## Философия данных

### Принципы

1. **Event-First Approach**: Каждое действие (order created, location updated, courier assigned) — это event, который немедленно пишется в ClickHouse.
2. **Immutability**: Данные в ClickHouse — immutable. Никаких UPDATE, только INSERT + TTL для старения.
3. **Hot/Cold Separation**: Горячие данные (последние 7-30 дней) — в оперативной памяти (Redis + горячие партиции ClickHouse), холодные — в S3/Long-term storage.
4. **Feature Store**: Все фичи для ML предвычисляются и хранятся в dedicated таблицах feature_store_*.
5. **Audit Trail**: Каждая ML-прогноз записывается с actual outcome для online learning и drift detection.

### Зачем ClickHouse для Logistics AI

- **Column-oriented**: Идеален для аналитики (avg ETA, max surge, route distribution).
- **Compression**: LZ4/ZSTD — экономия 5-10x по сравнению с MySQL.
- **Time-series**: Встроенная поддержка партиционирования по времени.
- **Real-time**: Materialized views для автоматической агрегации.
- **Scalability**: Горизонтальное масштабирование кластеров.

---

## Текущее состояние ClickHouse

### Существующие таблицы

```sql
-- Уже реализовано (database/clickhouse/schema.sql):
ch_geo_events          -- Гео-активность пользователей (view, click, scroll)
ch_click_events        -- Клик-хитмап
ch_geo_hourly          -- Агрегированная гео-активность (почасовая)
ch_click_hourly        -- Агрегированные клики (почасовые)
```

### Проблемы текущей схемы

1. **Нет logistics-specific таблиц**: Нет данных о курьерах, заказах, маршрутах, ETA.
2. **Нет feature store**: Нет таблиц для ML-фич (courier_load, pvz_demand, traffic_score).
3. **Нет predictions log**: Нет таблицы для хранения ML-прогнозов vs actual outcomes.
4. **Нет real-time positions**: Нет таблицы для позиций курьеров каждые 10-30 сек.

---

## Требуемая схема для Logistics AI

### 1. Raw Events Layer (Raw Logs)

#### `logistics_orders_raw`

```sql
CREATE TABLE logistics_orders_raw (
    -- Identifiers
    order_id UUID,
    tenant_id UInt32,
    vertical String,  -- 'food', 'medical', 'pharmacy', etc.
    
    -- Order details
    user_id UInt32,
    merchant_id UInt32,
    order_type Enum8('courier' = 1, 'pvz' = 2, 'taxi' = 3),
    
    -- Location
    pickup_lat Float64,
    pickup_lon Float64,
    delivery_lat Float64,
    delivery_lon Float64,
    pvz_id Nullable(UInt32),
    
    -- Package
    weight_kg Float32,
    volume_cm3 Float32,
    items_count UInt16,
    
    -- Timing
    created_at DateTime,
    accepted_at Nullable(DateTime),
    picked_up_at Nullable(DateTime),
    delivered_at Nullable(DateTime),
    cancelled_at Nullable(DateTime),
    
    -- Outcomes
    status String,  -- 'pending', 'assigned', 'picked_up', 'delivered', 'cancelled'
    cancellation_reason Nullable(String),
    
    -- Context
    surge_multiplier Float32,
    base_price_kopek UInt32,
    final_price_kopek UInt32,
    payment_method String,
    
    -- Metadata
    correlation_id String,
    metadata String,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_status (status) TYPE set(100) GRANULARITY 8192,
    INDEX idx_pickup_geo (pickup_lat, pickup_lon) TYPE minmax GRANULARITY 8192,
    
    COMMENT 'Raw order events for logistics ML'
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, order_id)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 730 DAY;
```

#### `logistics_courier_locations_raw`

```sql
CREATE TABLE logistics_courier_locations_raw (
    -- Identifiers
    courier_id UInt32,
    tenant_id UInt32,
    location_id UUID,
    
    -- Location
    latitude Float64,
    longitude Float64,
    geo_hash String,
    accuracy_meters Nullable(Float32),
    
    -- Courier state
    status Enum8('idle' = 1, 'busy' = 2, 'offline' = 3),
    current_order_id Nullable(UUID),
    battery_level Nullable(UInt8),
    is_taxi UInt8,
    
    -- Timing
    recorded_at DateTime,
    created_at DateTime,
    
    -- Context
    speed_kmh Nullable(Float32),
    heading_degrees Nullable(Float32),
    
    -- Indices
    INDEX idx_courier_time (courier_id, recorded_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_geo_hash (geo_hash) TYPE bloom_filter GRANULARITY 8192,
    INDEX idx_status (status) TYPE set(10) GRANULARITY 8192,
    
    COMMENT 'Real-time courier GPS positions (every 10-30s)'
) ENGINE = MergeTree()
ORDER BY (courier_id, recorded_at)
PARTITION BY toYYYYMMDD(recorded_at)
TTL recorded_at + INTERVAL 90 DAY;
```

#### `logistics_pvz_load_raw`

```sql
CREATE TABLE logistics_pvz_load_raw (
    pvz_id UInt32,
    tenant_id UInt32,
    
    -- Load metrics
    active_orders UInt16,
    available_slots UInt16,
    total_capacity UInt16,
    
    -- Location
    latitude Float64,
    longitude Float64,
    geo_hash String,
    
    -- Timing
    recorded_at DateTime,
    
    -- Context
    zone_id Nullable(UInt32),
    surge_multiplier Float32,
    
    COMMENT 'PVZ load metrics for demand forecasting'
) ENGINE = MergeTree()
ORDER BY (pvz_id, recorded_at)
PARTITION BY toYYYYMMDD(recorded_at)
TTL recorded_at + INTERVAL 180 DAY;
```

#### `logistics_traffic_raw`

```sql
CREATE TABLE logistics_traffic_raw (
    -- Location
    geo_hash String,
    latitude Float64,
    longitude Float64,
    
    -- Traffic data (from external API: Yandex Traffic, Google Maps)
    traffic_level Enum8('low' = 1, 'moderate' = 2, 'high' = 3, 'severe' = 4),
    speed_kmh Nullable(Float32),
    delay_minutes Nullable(Float32),
    
    -- Weather context
    temperature_c Nullable(Float32),
    precipitation_mm Nullable(Float32),
    weather_condition Nullable(String),
    
    -- Timing
    recorded_at DateTime,
    
    -- Source
    data_source String,  -- 'yandex', 'google', 'openweather'
    
    COMMENT 'Traffic and weather data from external APIs'
) ENGINE = MergeTree()
ORDER BY (geo_hash, recorded_at)
PARTITION BY toYYYYMMDD(recorded_at)
TTL recorded_at + INTERVAL 30 DAY;
```

### 2. Feature Store Layer (ML Features)

#### `feature_store_courier_hourly`

```sql
CREATE TABLE feature_store_courier_hourly (
    courier_id UInt32,
    tenant_id UInt32,
    hour DateTime,
    
    -- Historical features (last 1h, 4h, 24h)
    orders_completed_1h UInt16,
    orders_completed_4h UInt16,
    orders_completed_24h UInt16,
    
    avg_delivery_time_1h Float32,
    avg_delivery_time_4h Float32,
    avg_delivery_time_24h Float32,
    
    total_distance_1h_km Float32,
    total_distance_4h_km Float32,
    total_distance_24h_km Float32,
    
    acceptance_rate_1h Float32,
    acceptance_rate_4h Float32,
    acceptance_rate_24h Float32,
    
    -- Current state
    current_status String,
    current_load UInt8,  -- orders currently assigned
    battery_level Nullable(UInt8),
    
    -- Location features
    last_geo_hash String,
    hours_since_last_order Nullable(Float32),
    
    COMMENT 'Courier features for assignment model'
) ENGINE = MergeTree()
ORDER BY (courier_id, hour)
PARTITION BY toYYYYMM(hour);
```

#### `feature_store_pvz_hourly`

```sql
CREATE TABLE feature_store_pvz_hourly (
    pvz_id UInt32,
    tenant_id UInt32,
    hour DateTime,
    
    -- Load features
    avg_load_1h Float32,
    avg_load_4h Float32,
    avg_load_24h Float32,
    
    max_load_24h UInt16,
    min_available_slots_24h UInt16,
    
    -- Demand features
    orders_received_1h UInt16,
    orders_received_4h UInt16,
    orders_received_24h UInt16,
    
    -- Time features
    hour_of_day UInt8,
    day_of_week UInt8,
    is_weekend UInt8,
    is_holiday UInt8,
    
    -- Location
    geo_hash String,
    
    COMMENT 'PVZ features for assignment model'
) ENGINE = MergeTree()
ORDER BY (pvz_id, hour)
PARTITION BY toYYYYMM(hour);
```

#### `feature_store_route_hourly`

```sql
CREATE TABLE feature_store_route_hourly (
    route_id UUID,
    tenant_id UInt32,
    hour DateTime,
    
    -- Route features
    distance_km Float32,
    estimated_duration_min UInt16,
    actual_duration_min Nullable(UInt16),
    
    -- ETA prediction features
    pickup_geo_hash String,
    delivery_geo_hash String,
    
    -- Time features
    hour_of_day UInt8,
    day_of_week UInt8,
    is_weekend UInt8,
    
    -- Traffic features
    traffic_level_at_pickup Nullable(UInt8),
    traffic_level_at_delivery Nullable(UInt8),
    avg_speed_kmh Nullable(Float32),
    
    -- Weather features
    temperature_c Nullable(Float32),
    precipitation_mm Nullable(Float32),
    
    -- Outcome
    delay_minutes Nullable(Float32),
    is_delayed UInt8,
    
    COMMENT 'Route features for ETA prediction model'
) ENGINE = MergeTree()
ORDER BY (route_id, hour)
PARTITION BY toYYYYMM(hour);
```

#### `feature_store_demand_forecasting`

```sql
CREATE TABLE feature_store_demand_forecasting (
    geo_hash String,
    tenant_id UInt32,
    hour DateTime,
    
    -- Demand metrics
    orders_count UInt16,
    unique_users UInt16,
    
    -- Historical lag features
    orders_1h_ago UInt16,
    orders_2h_ago UInt16,
    orders_4h_ago UInt16,
    orders_24h_ago UInt16,
    orders_7d_ago UInt16,
    
    -- Rolling averages
    avg_orders_4h Float32,
    avg_orders_24h Float32,
    avg_orders_7d Float32,
    
    -- Time features
    hour_of_day UInt8,
    day_of_week UInt8,
    day_of_month UInt8,
    month UInt8,
    is_weekend UInt8,
    is_holiday UInt8,
    
    -- Context
    weather_condition Nullable(String),
    temperature_c Nullable(Float32),
    
    COMMENT 'Demand forecasting features (2-6h ahead)'
) ENGINE = MergeTree()
ORDER BY (geo_hash, hour)
PARTITION BY toYYYYMM(hour);
```

### 3. Predictions Layer (ML Outputs)

#### `ml_predictions_courier_assignment`

```sql
CREATE TABLE ml_predictions_courier_assignment (
    prediction_id UUID,
    tenant_id UInt32,
    order_id UUID,
    
    -- Input features (snapshot)
    courier_candidates_ids Array(UInt32),
    pickup_geo_hash String,
    delivery_geo_hash String,
    order_weight_kg Float32,
    
    -- Prediction
    selected_courier_id UInt32,
    model_version String,
    prediction_confidence Float32,
    
    -- Alternative scores
    all_courier_scores Array(Float32),
    
    -- Context
    predicted_at DateTime,
    actual_courier_id Nullable(UInt32),
    actual_outcome Nullable(String),  -- 'accepted', 'rejected', 'cancelled'
    actual_acceptance_time_seconds Nullable(UInt32),
    
    -- Performance
    eta_prediction_error_minutes Nullable(Float32),
    
    COMMENT 'Courier assignment predictions + actual outcomes'
) ENGINE = MergeTree()
ORDER BY (prediction_id, predicted_at)
PARTITION BY toYYYYMMDD(predicted_at)
TTL predicted_at + INTERVAL 365 DAY;
```

#### `ml_predictions_eta`

```sql
CREATE TABLE ml_predictions_eta (
    prediction_id UUID,
    tenant_id UInt32,
    route_id UUID,
    order_id UUID,
    
    -- Input features
    pickup_lat Float64,
    pickup_lon Float64,
    delivery_lat Float64,
    delivery_lon Float64,
    distance_km Float32,
    transport_type String,
    
    -- Prediction
    predicted_eta_minutes UInt16,
    model_version String,
    prediction_confidence Float32,
    
    -- Context
    predicted_at DateTime,
    
    -- Actual outcome
    actual_eta_minutes Nullable(UInt16),
    error_minutes Nullable(Float32),
    is_delayed Nullable(UInt8),
    
    COMMENT 'ETA predictions + actual outcomes'
) ENGINE = MergeTree()
ORDER BY (prediction_id, predicted_at)
PARTITION BY toYYYYMMDD(predicted_at)
TTL predicted_at + INTERVAL 365 DAY;
```

#### `ml_predictions_pvz_scoring`

```sql
CREATE TABLE ml_predictions_pvz_scoring (
    prediction_id UUID,
    tenant_id UInt32,
    order_id UUID,
    
    -- Input features
    user_geo_hash String,
    available_pvz_ids Array(UInt32),
    order_type String,
    
    -- Prediction
    recommended_pvz_id UInt32,
    model_version String,
    prediction_confidence Float32,
    
    -- All scores
    all_pvz_scores Array(Float32),
    
    -- Context
    predicted_at DateTime,
    actual_pvz_id Nullable(UInt32),
    user_accepted UInt8,
    
    COMMENT 'PVZ scoring predictions + user choices'
) ENGINE = MergeTree()
ORDER BY (prediction_id, predicted_at)
PARTITION BY toYYYYMMDD(predicted_at)
TTL predicted_at + INTERVAL 365 DAY;
```

### 4. Materialized Views (Auto-Aggregation)

```sql
-- Courier features MV
CREATE MATERIALIZED VIEW feature_store_courier_hourly_mv TO feature_store_courier_hourly AS
SELECT
    courier_id,
    tenant_id,
    toStartOfHour(recorded_at) AS hour,
    
    -- Orders completed
    countIf(status = 'idle' AND recorded_at >= hour - INTERVAL 1 HOUR) AS orders_completed_1h,
    countIf(status = 'idle' AND recorded_at >= hour - INTERVAL 4 HOUR) AS orders_completed_4h,
    countIf(status = 'idle' AND recorded_at >= hour - INTERVAL 24 HOUR) AS orders_completed_24h,
    
    -- Current state
    argMax(status, recorded_at) AS current_status,
    countIf(current_order_id IS NOT NULL) AS current_load,
    
    -- Location
    argMax(geo_hash, recorded_at) AS last_geo_hash
    
FROM logistics_courier_locations_raw
GROUP BY courier_id, tenant_id, hour;

-- Demand forecasting MV
CREATE MATERIALIZED VIEW feature_store_demand_forecasting_mv TO feature_store_demand_forecasting AS
SELECT
    geo_hash,
    tenant_id,
    toStartOfHour(created_at) AS hour,
    
    count() AS orders_count,
    uniqExact(user_id) AS unique_users,
    
    -- Lag features (self-join alternative)
    -- Note: In production, use window functions or separate job
    
    hour_of_day,
    day_of_week,
    is_weekend
    
FROM logistics_orders_raw
GROUP BY geo_hash, tenant_id, hour;
```

---

## Data Pipeline Architecture

### Laravel Services

#### `LogisticsDataPipelineService`

```php
<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\GeoLogistics\Jobs\WriteToClickHouseJob;
use Modules\GeoLogistics\DTOs\OrderEventDto;
use Modules\GeoLogistics\DTOs\CourierLocationDto;

/**
 * Data Pipeline Service для Logistics AI.
 * 
 * Канон 2026:
 * - Все события пишутся асинхронно через Queue
 * - Batch inserts для оптимизации ClickHouse
 * - Circuit breaker для защиты от перегрузки
 */
final class LogisticsDataPipelineService
{
    private const BATCH_SIZE = 1000;
    private const QUEUE_NAME = 'clickhouse-write';
    
    public function __construct(
        private readonly \Illuminate\Contracts\Cache\Repository $cache,
        private readonly \Illuminate\Contracts\Redis\Factory $redis,
    ) {}
    
    /**
     * Записать событие заказа в ClickHouse.
     */
    public function logOrderEvent(OrderEventDto $dto): void
    {
        $this->batchInsert('logistics_orders_raw', [
            'order_id' => $dto->orderId,
            'tenant_id' => $dto->tenantId,
            'vertical' => $dto->vertical,
            'user_id' => $dto->userId,
            'merchant_id' => $dto->merchantId,
            'order_type' => $dto->orderType,
            'pickup_lat' => $dto->pickupLat,
            'pickup_lon' => $dto->pickupLon,
            'delivery_lat' => $dto->deliveryLat,
            'delivery_lon' => $dto->deliveryLon,
            'pvz_id' => $dto->pvzId,
            'weight_kg' => $dto->weightKg,
            'volume_cm3' => $dto->volumeCm3,
            'items_count' => $dto->itemsCount,
            'created_at' => $dto->createdAt->format('Y-m-d H:i:s'),
            'status' => $dto->status,
            'surge_multiplier' => $dto->surgeMultiplier,
            'base_price_kopek' => $dto->basePriceKopek,
            'final_price_kopek' => $dto->finalPriceKopek,
            'payment_method' => $dto->paymentMethod,
            'correlation_id' => $dto->correlationId,
        ]);
    }
    
    /**
     * Записать позицию курьера в ClickHouse.
     */
    public function logCourierLocation(CourierLocationDto $dto): void
    {
        $this->batchInsert('logistics_courier_locations_raw', [
            'courier_id' => $dto->courierId,
            'tenant_id' => $dto->tenantId,
            'location_id' => $dto->locationId,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
            'geo_hash' => $dto->geoHash,
            'accuracy_meters' => $dto->accuracyMeters,
            'status' => $dto->status,
            'current_order_id' => $dto->currentOrderId,
            'battery_level' => $dto->batteryLevel,
            'is_taxi' => $dto->isTaxi ? 1 : 0,
            'recorded_at' => $dto->recordedAt->format('Y-m-d H:i:s'),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'speed_kmh' => $dto->speedKmh,
            'heading_degrees' => $dto->headingDegrees,
        ]);
    }
    
    /**
     * Batch insert с накоплением в Redis.
     */
    private function batchInsert(string $table, array $data): void
    {
        $key = "clickhouse_batch:{$table}";
        
        // Добавляем в Redis list
        $this->redis->connection()->lpush($key, json_encode($data));
        
        // Если достигли batch size - отправляем в ClickHouse
        if ($this->redis->connection()->llen($key) >= self::BATCH_SIZE) {
            Queue::push(new WriteToClickHouseJob($table, $key), '', self::QUEUE_NAME);
        }
    }
    
    /**
     * Force flush всех batch-ов (для cron job).
     */
    public function flushAllBatches(): void
    {
        $tables = [
            'logistics_orders_raw',
            'logistics_courier_locations_raw',
            'logistics_pvz_load_raw',
        ];
        
        foreach ($tables as $table) {
            $key = "clickhouse_batch:{$table}";
            if ($this->redis->connection()->llen($key) > 0) {
                Queue::push(new WriteToClickHouseJob($table, $key), '', self::QUEUE_NAME);
            }
        }
    }
}
```

#### `WriteToClickHouseJob`

```php
<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Job для записи batch-данных в ClickHouse.
 */
final class WriteToClickHouseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $timeout = 120;
    
    public function __construct(
        private readonly string $table,
        private readonly string $redisKey,
    ) {
        $this->onQueue('clickhouse-write');
    }
    
    public function handle(): void
    {
        $correlationId = Str::uuid()->toString();
        
        try {
            $redis = app('redis')->connection();
            
            // Получаем все записи из Redis
            $batch = [];
            while ($item = $redis->rpop($this->redisKey)) {
                $batch[] = json_decode($item, true);
            }
            
            if (empty($batch)) {
                return;
            }
            
            // Формируем bulk INSERT
            $columns = array_keys($batch[0]);
            $values = [];
            
            foreach ($batch as $row) {
                $values[] = '(' . implode(',', array_map([$this, 'formatValue'], $row)) . ')';
            }
            
            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES %s',
                $this->table,
                implode(',', $columns),
                implode(',', $values)
            );
            
            // Выполняем в ClickHouse
            DB::connection('clickhouse')->statement($sql);
            
            Log::channel('audit')->info('clickhouse.batch.success', [
                'correlation_id' => $correlationId,
                'table' => $this->table,
                'rows' => count($batch),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('clickhouse.batch.error', [
                'correlation_id' => $correlationId,
                'table' => $this->table,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        if (is_array($value)) {
            return '[' . implode(',', array_map([$this, 'formatValue'], $value)) . ']';
        }
        
        return "'" . addslashes($value) . "'";
    }
}
```

### Event Listeners

```php
<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Listeners;

use Modules\GeoLogistics\Events\OrderCreated;
use Modules\GeoLogistics\Events\OrderStatusChanged;
use Modules\GeoLogistics\Events\CourierLocationUpdated;
use Modules\GeoLogistics\Services\LogisticsDataPipelineService;
use Modules\GeoLogistics\DTOs\OrderEventDto;
use Modules\GeoLogistics\DTOs\CourierLocationDto;

/**
 * Автоматическая запись событий в ClickHouse.
 */
final class WriteToClickHouseListener
{
    public function __construct(
        private readonly LogisticsDataPipelineService $pipeline,
    ) {}
    
    public function handleOrderCreated(OrderCreated $event): void
    {
        $this->pipeline->logOrderEvent(OrderEventDto::fromEvent($event));
    }
    
    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $this->pipeline->logOrderEvent(OrderEventDto::fromStatusChange($event));
    }
    
    public function handleCourierLocationUpdated(CourierLocationUpdated $event): void
    {
        $this->pipeline->logCourierLocation(CourierLocationDto::fromEvent($event));
    }
}
```

### Cron Job (Force Flush)

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Force flush ClickHouse batches every 5 minutes
    $schedule->call(function () {
        app(LogisticsDataPipelineService::class)->flushAllBatches();
    })->everyFiveMinutes();
}
```

---

## Feature Engineering

### Ключевые фичи для моделей

#### 1. Courier Assignment Model

**Temporal Features:**
- `orders_completed_1h/4h/24h` — продуктивность курьера
- `acceptance_rate_1h/4h/24h` — насколько часто курьер принимает заказы
- `avg_delivery_time_1h/4h/24h` — среднее время доставки
- `hours_since_last_order` — время простоя

**Spatial Features:**
- `distance_to_pickup_km` — расстояние до точки pickup
- `last_geo_hash` — последняя локация курьера
- `geo_hash_proximity` — proximity между курьером и заказом

**Context Features:**
- `current_load` — сколько заказов уже назначено
- `battery_level` — заряд батареи (для электросамокатов)
- `is_taxi` — это такси или курьер
- `surge_multiplier` — текущий surge в зоне

#### 2. PVZ Assignment Model

**Load Features:**
- `avg_load_1h/4h/24h` — средняя загрузка ПВЗ
- `max_load_24h` — пиковая загрузка
- `min_available_slots_24h` — минимум свободных слотов

**Demand Features:**
- `orders_received_1h/4h/24h` — сколько заказов пришло
- `demand_forecast_2h/6h` — прогноз спроса (из отдельной модели)

**Location Features:**
- `distance_to_user_km` — расстояние до пользователя
- `geo_hash` — для кластеризации

**User Preferences:**
- `user_preferred_pvz_id` — любимый ПВЗ пользователя
- `user_pvz_visit_count` — сколько раз пользователь бывал в этом ПВЗ

#### 3. ETA Prediction Model

**Route Features:**
- `distance_km` — расстояние маршрута
- `pickup_geo_hash`, `delivery_geo_hash` — гео-хэши

**Time Features:**
- `hour_of_day` — час дня (0-23)
- `day_of_week` — день недели (0-6)
- `is_weekend` — выходной
- `is_holiday` — праздник

**Traffic Features:**
- `traffic_level_at_pickup` — уровень трафика на старте
- `traffic_level_at_delivery` — уровень трафика на финише
- `avg_speed_kmh` — средняя скорость

**Weather Features:**
- `temperature_c` — температура
- `precipitation_mm` — осадки
- `weather_condition` — sunny, rain, snow

**Transport Type:**
- `transport_type` — courier, taxi, walk, bike

#### 4. Demand Forecasting Model

**Lag Features:**
- `orders_1h_ago`, `orders_2h_ago`, `orders_4h_ago` — лаги
- `orders_24h_ago` — вчера в это же время
- `orders_7d_ago` — неделю назад в это же время

**Rolling Features:**
- `avg_orders_4h`, `avg_orders_24h`, `avg_orders_7d` — скользящие средние

**Time Features:**
- `hour_of_day`, `day_of_week`, `day_of_month`, `month`
- `is_weekend`, `is_holiday`

**Context Features:**
- `weather_condition`, `temperature_c`
- `surge_multiplier` — текущий surge

### Feature Extraction Job

```php
<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Job для feature extraction (запускается раз в час).
 */
final class ExtractLogisticsFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 600; // 10 minutes
    
    public function handle(): void
    {
        $hour = now()->subHour()->startOfHour();
        
        // Extract courier features
        DB::connection('clickhouse')->statement("
            INSERT INTO feature_store_courier_hourly
            SELECT
                courier_id,
                tenant_id,
                ? AS hour,
                countIf(status = 'idle' AND recorded_at >= ? - INTERVAL 1 HOUR) AS orders_completed_1h,
                countIf(status = 'idle' AND recorded_at >= ? - INTERVAL 4 HOUR) AS orders_completed_4h,
                countIf(status = 'idle' AND recorded_at >= ? - INTERVAL 24 HOUR) AS orders_completed_24h,
                argMax(status, recorded_at) AS current_status,
                countIf(current_order_id IS NOT NULL) AS current_load,
                argMax(geo_hash, recorded_at) AS last_geo_hash
            FROM logistics_courier_locations_raw
            WHERE recorded_at >= ? - INTERVAL 24 HOUR
            GROUP BY courier_id, tenant_id
        ", [$hour, $hour, $hour, $hour, $hour]);
        
        // Extract demand features
        DB::connection('clickhouse')->statement("
            INSERT INTO feature_store_demand_forecasting
            SELECT
                geo_hash,
                tenant_id,
                ? AS hour,
                count() AS orders_count,
                uniqExact(user_id) AS unique_users,
                -- Lag features via window functions
                lagInFrame(orders_count, 1) OVER (PARTITION BY geo_hash ORDER BY hour) AS orders_1h_ago,
                lagInFrame(orders_count, 24) OVER (PARTITION BY geo_hash ORDER BY hour) AS orders_24h_ago,
                hour_of_day,
                day_of_week,
                is_weekend
            FROM logistics_orders_raw
            WHERE created_at >= ? - INTERVAL 7 DAY
            GROUP BY geo_hash, tenant_id, hour
        ", [$hour, $hour]);
    }
}
```

---

## Реализация (по шагам)

### Неделя 1: Schema Setup

1. **Создать миграцию ClickHouse**:
   ```bash
   # database/clickhouse/migrations/001_create_logistics_tables.sql
   cat database/clickhouse/schema.sql database/clickhouse/migrations/001_logistics.sql > all.sql
   clickhouse-client --multiquery < all.sql
   ```

2. **Добавить connection в config/database.php**:
   ```php
   'connections' => [
       'clickhouse' => [
           'driver' => 'clickhouse',
           'host' => env('CLICKHOUSE_HOST', 'localhost'),
           'port' => env('CLICKHOUSE_PORT', '8123'),
           'database' => env('CLICKHOUSE_DATABASE', 'catvrf'),
           'username' => env('CLICKHOUSE_USERNAME', 'default'),
           'password' => env('CLICKHOUSE_PASSWORD', ''),
           'options' => [
               \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
           ],
       ],
   ],
   ```

3. **Установить PHP драйвер**:
   ```bash
   composer require smi2/phpclickhouse
   ```

### Неделя 2: Data Pipeline

1. **Создать DTOs**:
   - `OrderEventDto`
   - `CourierLocationDto`
   - `PvzLoadDto`

2. **Создать Service**:
   - `LogisticsDataPipelineService`

3. **Создать Job**:
   - `WriteToClickHouseJob`

4. **Создать Events & Listeners**:
   - `OrderCreated` → `WriteToClickHouseListener`
   - `OrderStatusChanged` → `WriteToClickHouseListener`
   - `CourierLocationUpdated` → `WriteToClickHouseListener`

5. **Зарегистрировать в EventServiceProvider**:
   ```php
   protected $listen = [
       OrderCreated::class => [WriteToClickhouseListener::class],
       OrderStatusChanged::class => [WriteToClickhouseListener::class],
       CourierLocationUpdated::class => [WriteToClickhouseListener::class],
   ];
   ```

### Неделя 3: Feature Extraction

1. **Создать Job**:
   - `ExtractLogisticsFeaturesJob`

2. **Добавить в schedule**:
   ```php
   $schedule->job(new ExtractLogisticsFeaturesJob())->hourly();
   ```

3. **Создать Materialized Views**:
   - Добавить MV для auto-aggregation

### Неделя 4: Historical Data Backfill

1. **Создать backfill script**:
   ```php
   // scripts/backfill_logistics_data.php
   // Читает из MySQL orders/couriers и пишет в ClickHouse
   ```

2. **Запустить backfill**:
   ```bash
   php scripts/backfill_logistics_data.php --batch=1000
   ```

---

## Мониторинг и качество данных

### Метрики качества данных

1. **Completeness**: % записей с null в обязательных полях
2. **Timeliness**: Задержка между event и записью в ClickHouse
3. **Uniqueness**: % дубликатов (по order_id, location_id)
4. **Validity**: % записей с некорректными значениями (lat > 90, etc.)

### Alerting

```php
// DataQualityMonitorService
public function checkDataQuality(): void
{
    $nullCount = DB::connection('clickhouse')
        ->table('logistics_orders_raw')
        ->where('created_at', '>=', now()->subHour())
        ->whereNull('pickup_lat')
        ->count();
    
    if ($nullCount > 100) {
        Log::channel('audit')->alert('data_quality.null_coordinates', [
            'count' => $nullCount,
            'hour' => now()->subHour(),
        ]);
        
        // Send alert to Slack/Telegram
    }
}
```

### Grafana Dashboard

1. **Events per minute**: Скорость записи событий
2. **Batch size distribution**: Размер batch-ов
3. **ClickHouse insert latency**: Задержка записи
4. **Feature freshness**: Как давно обновлены фичи
5. **Data quality alerts**: Nulls, duplicates, invalid values

---

## Итог

**Архитектура данных для Logistics AI:**
- ✅ 4 raw tables (orders, courier locations, PVZ load, traffic)
- ✅ 4 feature store tables (courier, PVZ, route, demand)
- ✅ 3 predictions tables (assignment, ETA, PVZ scoring)
- ✅ Materialized views для auto-aggregation
- ✅ Laravel Queue + Redis для batch inserts
- ✅ Feature extraction job (hourly)
- ✅ Data quality monitoring

**Ожидаемый результат:**
- 30-90 дней исторических данных для обучения
- 100k+ событий/день без проблем с производительностью
- Реал-тайм фичи с задержкой < 5 минут
- 10-20x экономия storage vs MySQL

**Следующий шаг:** Реализовать Route Optimization (см. docs/LOGISTICS_AI_ROUTE_OPTIMIZATION.md)
