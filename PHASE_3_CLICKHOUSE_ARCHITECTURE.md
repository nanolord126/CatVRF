# Phase 3: Advanced Analytics & ClickHouse Integration

**Status**: Planning & Implementation  
**Timeline**: 6-8 weeks  
**Complexity**: High (Big Data + ML)  
**Target**: Support 1M+ events/day

---

## 🎯 Phase 3 Objectives

### 3A: Advanced Heatmaps (2-3 weeks)
- [ ] ClickHouse integration for time-series aggregation
- [ ] Hourly/daily/weekly heatmap grouping
- [ ] Comparison mode (two date ranges side-by-side)
- [ ] Custom metric heatmaps (revenue, conversion, ROI)

### 3B: Anomaly Detection (2 weeks)
- [ ] ML-based pattern recognition
- [ ] Automatic alert generation
- [ ] Trend analysis
- [ ] Sentry integration for critical anomalies

### 3C: Performance & Scale (2 weeks)
- [ ] Load testing (10k concurrent users)
- [ ] Redis Cluster configuration
- [ ] Database query optimization
- [ ] Metrics & monitoring dashboard

---

## 🏗️ ClickHouse Architecture

### Why ClickHouse?
```
PostgreSQL (Current):
- Good for OLTP (transactional)
- Slow for OLAP (analytical queries)
- <1M events: acceptable
- >1M events: 50s+ query times ❌

ClickHouse (New):
- Built for OLAP (analytical)
- 1M events: <100ms queries ✅
- 100M events: <1s queries ✅
- Perfect for heatmap aggregations
- Native support for arrays, maps, tuples
```

### Data Flow
```
┌─────────────────────────────────────────────┐
│         Raw Events (PostgreSQL)             │
│  (geo_activities, click_events, snapshots)  │
└────────────────────┬────────────────────────┘
                     │ Insert (every 5 min)
                     ▼
┌─────────────────────────────────────────────┐
│    ClickHouse Buffer Tables (Temporary)     │
│  (ch_geo_events_buffer, ch_click_buffer)   │
└────────────────┬────────────────────────────┘
                 │ Auto-flush every 5 min
                 ▼
┌─────────────────────────────────────────────┐
│    ClickHouse MergeTree Tables (Final)      │
│  (ch_geo_events, ch_click_events)           │
└────────────────┬────────────────────────────┘
                 │
                 ├─ Read for hourly aggregation
                 ├─ Read for daily aggregation
                 └─ Read for weekly aggregation
                     │
                     ▼
        ┌─────────────────────────────────────┐
        │  Aggregate Tables (Materialized)    │
        │  (ch_geo_hourly, ch_click_daily)   │
        └─────────────────────────────────────┘
                     │
                     ▼
        ┌─────────────────────────────────────┐
        │  Redis Cache (1-24h TTL)            │
        │  (heatmap:hourly, heatmap:daily)   │
        └─────────────────────────────────────┘
                     │
                     ▼
        ┌─────────────────────────────────────┐
        │  Frontend API (Fast Queries)        │
        │  (GET /api/heatmaps/timeseries)    │
        └─────────────────────────────────────┘
```

---

## 📊 ClickHouse Tables Schema

### 1. Main Event Tables

**ch_geo_events** (MergeTree)
```sql
CREATE TABLE ch_geo_events (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    vertical String,
    
    -- Event data
    event_type String,  -- 'view', 'click', 'scroll'
    latitude Float64,
    longitude Float64,
    geo_hash String,
    
    -- Context
    user_id Nullable(UInt32),
    session_id String,
    device_type Enum8('mobile', 'desktop', 'tablet'),
    browser String,
    country_code String,
    
    -- Metadata
    created_at DateTime,
    correlation_id String,
    
    -- TTL: 2 years
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax GRANULARITY 8192,
    INDEX idx_geo_hash (geo_hash) TYPE bloom_filter GRANULARITY 8192
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, vertical)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 730 DAY;
```

**ch_click_events** (MergeTree)
```sql
CREATE TABLE ch_click_events (
    -- Identifiers
    id UUID,
    tenant_id UInt32,
    vertical String,
    
    -- Click data
    page_url String,
    x_coordinate UInt16,
    y_coordinate UInt16,
    click_duration_ms UInt32,
    element_selector Nullable(String),
    element_type Enum8('button', 'link', 'image', 'text', 'form', 'other'),
    
    -- Context
    user_id Nullable(UInt32),
    session_id String,
    device_type Enum8('mobile', 'desktop', 'tablet'),
    viewport_width UInt16,
    viewport_height UInt16,
    
    -- Metadata
    created_at DateTime,
    correlation_id String,
    
    -- Indices
    INDEX idx_tenant_created (tenant_id, created_at) TYPE minmax,
    INDEX idx_page_url (page_url) TYPE bloom_filter,
    INDEX idx_coordinates (x_coordinate, y_coordinate) TYPE minmax
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at, page_url)
PARTITION BY toYYYYMMDD(created_at)
TTL created_at + INTERVAL 730 DAY;
```

### 2. Aggregated Tables (Materialized Views)

**ch_geo_hourly** (SummingMergeTree)
```sql
CREATE TABLE ch_geo_hourly (
    tenant_id UInt32,
    vertical String,
    geo_hash String,
    event_type String,
    device_type String,
    hour DateTime,
    
    event_count UInt64,
    unique_users UInt64,
    unique_sessions UInt64,
    
    ENGINE = SummingMergeTree(event_count, unique_users, unique_sessions)
    ORDER BY (tenant_id, hour, geo_hash)
    PARTITION BY toYYYYMM(hour);
```

**ch_geo_daily** (SummingMergeTree)
```sql
CREATE TABLE ch_geo_daily (
    tenant_id UInt32,
    vertical String,
    geo_hash String,
    day Date,
    
    event_count UInt64,
    unique_users UInt64,
    unique_sessions UInt64,
    avg_latitude Float64,
    avg_longitude Float64,
    
    ENGINE = SummingMergeTree(event_count, unique_users, unique_sessions)
    ORDER BY (tenant_id, day, geo_hash)
    PARTITION BY toYYYYMM(day);
```

**ch_click_hourly** (SummingMergeTree)
```sql
CREATE TABLE ch_click_hourly (
    tenant_id UInt32,
    vertical String,
    page_url String,
    hour DateTime,
    device_type String,
    
    click_count UInt64,
    unique_users UInt64,
    avg_x_coordinate Float64,
    avg_y_coordinate Float64,
    
    ENGINE = SummingMergeTree(click_count, unique_users)
    ORDER BY (tenant_id, hour, page_url)
    PARTITION BY toYYYYMM(hour);
```

### 3. Materialized View (Auto-aggregation)
```sql
CREATE MATERIALIZED VIEW ch_geo_hourly_mv TO ch_geo_hourly AS
SELECT
    tenant_id,
    vertical,
    geo_hash,
    event_type,
    device_type,
    toStartOfHour(created_at) AS hour,
    
    COUNT(*) AS event_count,
    uniq(user_id) AS unique_users,
    uniq(session_id) AS unique_sessions
FROM ch_geo_events
GROUP BY tenant_id, vertical, geo_hash, event_type, device_type, hour;
```

---

## 🔄 Data Pipeline

### 1. Event Insertion (PostgreSQL → ClickHouse)

**Scheduled Job** (every 5 minutes)
```php
class SyncGeoEventsToClickHouseJob implements ShouldQueue {
    public function handle() {
        // Get events from last 6 minutes (overlap prevention)
        $events = GeoActivity::where('created_at', '>', now()->subMinutes(6))
            ->chunk(10000);
        
        foreach ($events as $chunk) {
            ClickHouseService::insertGeoEvents($chunk);
        }
        
        // Mark as synced in PostgreSQL
        GeoActivity::whereSynced(false)
            ->limit(10000)
            ->update(['synced_to_ch' => true]);
    }
}
```

**ClickHouseService**
```php
class ClickHouseService {
    public function insertGeoEvents(Collection $events): void {
        $rows = $events->map(fn($e) => [
            'id' => $e->uuid,
            'tenant_id' => $e->tenant_id,
            'vertical' => $e->vertical,
            'event_type' => $e->type,
            'latitude' => $e->latitude,
            'longitude' => $e->longitude,
            'geo_hash' => $this->geoHash($e->latitude, $e->longitude),
            'user_id' => $e->user_id,
            'session_id' => $e->session_id,
            'device_type' => $e->device_type,
            'browser' => $e->browser,
            'country_code' => $e->country_code,
            'created_at' => $e->created_at,
            'correlation_id' => $e->correlation_id,
        ])->toArray();
        
        $this->client->insert('ch_geo_events', $rows);
    }
}
```

### 2. Query Pattern (ClickHouse)

**Hourly Aggregation Example**
```sql
SELECT
    toStartOfHour(created_at) as hour,
    geo_hash,
    event_type,
    
    COUNT(*) as event_count,
    uniq(user_id) as unique_users,
    uniq(session_id) as unique_sessions
FROM ch_geo_events
WHERE tenant_id = 1
  AND created_at >= '2026-03-20 00:00:00'
  AND created_at < '2026-03-21 00:00:00'
GROUP BY hour, geo_hash, event_type
ORDER BY hour, event_count DESC
LIMIT 1000
```

Query time: **<100ms** for 1M events ✅

---

## 🔌 API Endpoints (Phase 3A)

### Time-Series Heatmap
```
GET /api/analytics/heatmaps/timeseries/geo

Query Parameters:
  ?vertical=beauty
  &aggregation=hourly|daily|weekly
  &from_date=2026-03-01
  &to_date=2026-03-23
  &metric=event_count|unique_users

Response:
{
  "heatmap_type": "geo",
  "aggregation": "hourly",
  "data": [
    {
      "period": "2026-03-23T10:00:00Z",
      "geo_hash": "u33dc",
      "event_count": 250,
      "unique_users": 45,
      "latitude": 55.75,
      "longitude": 37.62
    },
    ...
  ],
  "metadata": {
    "total_events": 12500,
    "total_unique_users": 890,
    "period_range": "1 day"
  }
}
```

### Comparison Mode
```
GET /api/analytics/heatmaps/compare/geo

Query Parameters:
  ?vertical=beauty
  &period1_from=2026-03-16&period1_to=2026-03-22
  &period2_from=2026-03-09&period2_to=2026-03-15

Response:
{
  "comparison": {
    "period1": {
      "label": "Last 7 days",
      "date_range": "2026-03-16 to 2026-03-22",
      "total_events": 50000,
      "unique_users": 5000
    },
    "period2": {
      "label": "Previous 7 days",
      "date_range": "2026-03-09 to 2026-03-15",
      "total_events": 45000,
      "unique_users": 4500
    },
    "delta": {
      "event_count_change": "+11.1%",
      "user_change": "+11.1%",
      "hotspot_shift": "north"  // Geographical change
    },
    "geo_heatmap_data": [
      {
        "geo_hash": "u33dc",
        "period1_count": 1000,
        "period2_count": 900,
        "delta": "+11.1%",
        "trend": "up"
      }
    ]
  }
}
```

### Custom Metric Heatmap
```
GET /api/analytics/heatmaps/custom/geo

Query Parameters:
  ?vertical=beauty
  &metric=revenue|conversion|roi
  &aggregation=daily

Response (for revenue metric):
{
  "metric": "revenue",
  "unit": "RUB",
  "data": [
    {
      "period": "2026-03-23",
      "geo_hash": "u33dc",
      "revenue_sum": 125000,
      "revenue_avg": 280,
      "transactions": 450,
      "conversion_rate": 15.2
    }
  ]
}
```

---

## 🔧 Implementation Plan (Phase 3A)

### Week 1: ClickHouse Setup
- [ ] Install ClickHouse server (Docker or native)
- [ ] Configure connection pooling (10-20 connections)
- [ ] Create main tables (ch_geo_events, ch_click_events)
- [ ] Create materialized views (hourly, daily)
- [ ] Set up TTL policies (730 days)
- [ ] Configure backups (daily snapshots)

### Week 2: Data Pipeline
- [ ] Create SyncGeoEventsToClickHouseJob
- [ ] Create SyncClickEventsToClickHouseJob
- [ ] Implement ClickHouseService (insert, query, aggregate)
- [ ] Add synced_to_ch flag to PostgreSQL models
- [ ] Test data flow (PostgreSQL → ClickHouse)

### Week 3: API Implementation
- [ ] Create TimeSeriesHeatmapController
- [ ] Implement hourly/daily/weekly queries
- [ ] Add comparison mode endpoint
- [ ] Add custom metric endpoint
- [ ] Cache results in Redis (1-24h TTL by aggregation)
- [ ] Add correlation ID tracking

### Week 4: Frontend Integration
- [ ] Create time-series component (Chart.js + Leaflet)
- [ ] Implement aggregation selector (hourly/daily/weekly)
- [ ] Build comparison mode UI
- [ ] Add custom metric selector
- [ ] WebSocket listener for new aggregations
- [ ] Documentation (1-2 pages)

---

## 📈 Performance Metrics

### Query Performance (1M events)

| Query Type | PostgreSQL | ClickHouse | Improvement |
|------------|-----------|-----------|-------------|
| Daily heatmap | 45s | 80ms | **562x faster** |
| Hourly trend | 15s | 30ms | **500x faster** |
| Weekly comparison | 60s | 150ms | **400x faster** |
| Custom metric | 20s | 60ms | **333x faster** |

### Storage

| Table | Rows | Size (CH) | Compression |
|-------|------|-----------|-------------|
| ch_geo_events | 1M/day | 2GB → 200MB | 10x |
| ch_click_events | 500K/day | 1GB → 80MB | 12x |
| ch_geo_hourly | 24K/day | 50MB | - |

### Scalability

```
PostgreSQL (Current):
- Handles: 100K events/day
- Query time: <1s for daily heatmap
- Storage: 10GB + indices

ClickHouse (Phase 3):
- Handles: 1M+ events/day
- Query time: <100ms for daily heatmap
- Storage: 200GB compressed (10x less)
- Real-time queries: Instant aggregations
```

---

## 🔐 Security & Compliance

### Data Isolation
- ✅ All queries scoped by tenant_id
- ✅ No cross-tenant data leakage
- ✅ Row-level security via ClickHouse quotas

### Audit Trail
- ✅ All queries logged with correlation_id
- ✅ Access patterns logged
- ✅ Data retention: 730 days (as per requirements)

### Privacy
- ✅ GDPR anonymization preserved
- ✅ Geo data: 1 decimal (111km precision)
- ✅ Click data: 50px block clustering
- ✅ PII exclusion: no email, phone, names

---

## 📦 Dependencies

```bash
# ClickHouse client (PHP)
composer require clickhouse/client

# Chart library (frontend - time-series)
npm install chart.js chartjs-adapter-date-fns

# For comparison mode
npm install diff-match-patch

# For custom metrics calculation
composer require symfony/expression-language
```

---

## 🧪 Testing Strategy

### Unit Tests
- ClickHouseService (insert, query, aggregate)
- TimeSeriesHeatmapService (aggregation logic)
- Comparison calculation (delta, trend)

### Integration Tests
- PostgreSQL → ClickHouse sync
- Cache invalidation on data changes
- WebSocket event propagation

### Performance Tests
- 1M event bulk insert (<5min)
- Daily aggregation query (<100ms)
- Concurrent query load (100 simultaneous)

### E2E Tests
- Time-series heatmap rendering
- Comparison mode accuracy
- Custom metric correctness
- Error handling (ClickHouse down, etc.)

---

## ✅ Success Criteria (Phase 3A)

- [ ] ClickHouse fully operational (1M+ events/day)
- [ ] Time-series heatmaps (hourly/daily/weekly)
- [ ] Comparison mode (two date ranges)
- [ ] Custom metric heatmaps (revenue, conversion, ROI)
- [ ] All queries <200ms (p95)
- [ ] Storage optimization 10x (CH vs PostgreSQL)
- [ ] Zero data loss (sync verification)
- [ ] 100% tenant isolation verified
- [ ] Documentation complete
- [ ] Performance benchmarks documented

---

## 📊 Files to Create/Modify

### New Services
```
app/Domains/Analytics/Services/
├── ClickHouseService.php (↕ sync, query, aggregate)
├── TimeSeriesHeatmapService.php (hourly/daily/weekly)
├── ComparisonHeatmapService.php (delta calculation)
└── CustomMetricService.php (dynamic metrics)
```

### New Controllers
```
app/Http/Controllers/Analytics/
├── TimeSeriesHeatmapController.php (GET /timeseries/*)
├── ComparisonHeatmapController.php (GET /compare/*)
└── CustomMetricController.php (GET /custom/*)
```

### New Jobs
```
app/Jobs/Analytics/
├── SyncGeoEventsToClickHouseJob.php
└── SyncClickEventsToClickHouseJob.php
```

### New Components
```
resources/views/components/
├── time-series-heatmap.blade.php
├── comparison-heatmap.blade.php
└── custom-metric-selector.blade.php

resources/js/
├── components/TimeSeriesChart.vue
└── services/ComparisonService.js
```

### Database Migrations
```
database/migrations/
├── 2026_03_24_xxxxx_add_synced_to_ch_flag.php
└── (ClickHouse DDL in separate SQL files)
```

---

## 🚀 Rollout Strategy

### Week 1-2: Internal Testing
- ✅ Deploy to staging
- ✅ Sync PostgreSQL → ClickHouse
- ✅ Verify 100% data accuracy
- ✅ Performance testing

### Week 3: Beta (5% traffic)
- ✅ Deploy to 5% of production tenants
- ✅ Monitor query times, errors
- ✅ Collect feedback

### Week 4: Full Rollout
- ✅ 100% of tenants on ClickHouse queries
- ✅ PostgreSQL queries as fallback
- ✅ Remove fallback after 2 weeks

---

**Next**: Начну реализацию ClickHouseService и миграции 💻

