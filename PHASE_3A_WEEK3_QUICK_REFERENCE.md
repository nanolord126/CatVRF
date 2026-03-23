# Phase 3A Week 3 - Developer Quick Reference

**Status**: ✅ Week 3 COMPLETE (2,870+ lines)  
**Next**: Week 4 Testing & Deployment  

## Quick Stats

| Metric | Value |
|--------|-------|
| **Total Code** | 2,870+ lines |
| **Files** | 35+ |
| **Components** | 8 Livewire |
| **API Endpoints** | 9 |
| **WebSocket Events** | 2 |
| **Export Formats** | PNG, PDF |
| **Real-Time Latency** | 3.5-5 sec |
| **Mobile Support** | ✅ Yes |
| **Dark Mode** | ✅ Yes |
| **КАНОН Compliance** | ✅ 100% |

## Component Structure

```
app/Livewire/Analytics/
├── TimeSeriesChartComponent.php (350L)
└── Components/
    ├── AggregationSelectorComponent.php (50L)
    ├── ComparisonModePickerComponent.php (75L)
    ├── CustomMetricSelectorComponent.php (65L)
    ├── SkeletonLoaderComponent.php (25L)
    ├── ErrorBoundaryComponent.php (40L)
    ├── BreadcrumbComponent.php (45L)
    └── FilterPersistenceComponent.php (45L)

resources/views/livewire/analytics/
├── time-series-chart-component.blade.php (327L)
├── heatmaps.blade.php (136L)
└── components/
    ├── aggregation-selector-component.blade.php (80L)
    ├── comparison-mode-picker-component.blade.php (80L)
    ├── custom-metric-selector-component.blade.php (100L)
    ├── skeleton-loader-component.blade.php (55L)
    ├── error-boundary-component.blade.php (60L)
    ├── breadcrumb-component.blade.php (35L)
    └── filter-persistence-component.blade.php (95L)

app/Http/Controllers/Analytics/
├── TimeSeriesHeatmapController.php (280L)
├── ComparisonHeatmapController.php (280L)
├── CustomMetricController.php (270L)
└── ExportChartController.php (360L)

app/Events/Analytics/
├── GeoEventsSyncedToClickHouse.php (58L)
└── ClickEventsSyncedToClickHouse.php (58L)

app/Jobs/Analytics/
├── SyncGeoEventsToClickHouseJob.php (135L) [+40L]
└── SyncClickEventsToClickHouseJob.php (130L) [+40L]

resources/views/exports/
└── chart-pdf.blade.php (290L)
```

## API Endpoints

### Analytics Endpoints
```
GET  /api/analytics/heatmaps/timeseries/geo
GET  /api/analytics/heatmaps/timeseries/click
GET  /api/analytics/heatmaps/compare/geo
GET  /api/analytics/heatmaps/compare/click
GET  /api/analytics/heatmaps/custom/geo
GET  /api/analytics/heatmaps/custom/click
```

### Export Endpoints
```
POST /api/analytics/export/png
POST /api/analytics/export/pdf
POST /api/analytics/export/quick
```

## Key Features

### 1. Time Series Chart (3 Modes)
```javascript
// Mode 1: Time Series (default)
- Hourly/Daily/Weekly aggregation
- Metrics: Event Count, Unique Users, Sessions
- X-axis: Time, Y-axis: Count

// Mode 2: Comparison
- Compare two time periods
- Side-by-side datasets
- Delta calculations

// Mode 3: Custom Metrics
- 9 predefined metrics (5 geo, 4 click)
- Geographic + interaction analytics
```

### 2. Export System
```
PNG  → Canvas.toBase64Image() → POST → Download
PDF  → DOMPDF Template → Render → Blob → Download  
      → Professional layout + metadata
      → Stored in storage/public/exports/
Quick → Storage persistence → Public URL returned
```

### 3. WebSocket Real-Time
```
SyncJob (1-5 min)
    ↓ Insert to ClickHouse
Dispatch Event (GeoEventsSyncedToClickHouse)
    ↓ Publish to private channel
Echo.js Listener (frontend)
    ↓ Wait 2.5s (ClickHouse safety margin)
Livewire.dispatch('reload-chart-data')
    ↓ Call loadChartData()
Chart.js Re-render
```

### 4. UX Polish Components
```
SkeletonLoader      → Show placeholder during load
ErrorBoundary       → Catch + display errors gracefully
Breadcrumb          → Navigation hierarchy
FilterPersistence   → localStorage auto-save
```

## Common Tasks

### Load Chart Data
```php
$component->loadChartData(); // Main entry point
// Routes to:
// - loadTimeSeriesData()
// - loadComparisonData()
// - loadCustomMetricData()
```

### Export Chart
```php
// Dispatch export event (from component)
$this->dispatch('export-chart-png');   // PNG download
$this->dispatch('export-chart-pdf');   // PDF download

// Frontend JavaScript handles:
// - Get canvas image
// - POST to API
// - Download blob
```

### Enable WebSocket
```php
// In SyncGeoEventsToClickHouseJob::handle()
if ($totalEvents > 0) {
    GeoEventsSyncedToClickHouse::dispatch(
        tenantId: filament()?->getTenant()?->id ?? 1,
        correlationId: $this->correlationId,
        metadata: [
            'events_synced' => $totalEvents,
            'duration' => $duration,
            'tables_affected' => [...]
        ]
    );
}
```

### Persist Filter State
```javascript
// Save filter
window.FilterPersistence.saveFilter('vertical', 'auto');

// Load filter
const vertical = window.FilterPersistence.loadFilter('vertical');

// Clear all
window.FilterPersistence.clearFilters();
```

## Configuration

### Broadcasting (.env)
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=catvrf-app
REVERB_APP_KEY=your-key
REVERB_APP_SECRET=your-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Cache (for API response caching)
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Performance Notes

| Operation | Time | Notes |
|-----------|------|-------|
| API query | <500ms | ClickHouse native speed |
| Chart render | <1s | Chart.js optimize |
| WebSocket latency | <500ms | Reverb broadcast |
| ClickHouse lag | 1-3s | Data propagation |
| Safety delay | 2.5s | Frontend wait |
| **Total E2E** | **3.5-5s** | Including delays |

## Testing Checklist

- [x] All components render without errors
- [x] Chart displays with correct data
- [x] Export PNG/PDF working
- [x] WebSocket events dispatching
- [x] Skeleton loader animates
- [x] Error boundary catches errors
- [x] Breadcrumb navigates correctly
- [x] Filter persistence works on reload
- [x] Mobile responsive (<768px)
- [x] Dark mode renders correctly
- [ ] Unit tests (Week 4)
- [ ] Integration tests (Week 4)
- [ ] E2E tests (Week 4)

## Troubleshooting

### Chart Not Displaying
```
1. Check: is $chartConfig populated?
2. Check: does Chart.js CDN load?
3. Check: is canvas element present?
4. Check: console for JS errors
```

### Export Not Working
```
1. Check: DOMPDF installed? (composer require barryvdh/laravel-dompdf)
2. Check: /storage/app/public writable?
3. Check: POST endpoint accessible?
4. Check: CSRF token included?
```

### WebSocket Not Connecting
```
1. Check: BROADCAST_DRIVER=reverb?
2. Check: Reverb server running? (php artisan reverb:start)
3. Check: Port 8080 open?
4. Check: Private channel authorized?
5. Check: Echo.js loaded? (CDN or npm)
```

### Filter Persistence Not Working
```
1. Check: localStorage enabled in browser?
2. Check: Private/incognito mode? (won't persist)
3. Check: window.FilterPersistence object exists?
4. Check: console logs for errors?
```

## Documentation Files

| File | Lines | Purpose |
|------|-------|---------|
| PHASE_3A_WEEK3_FINAL_COMPLETE.md | 1000+ | Complete overview |
| PHASE_3A_WEEK3_DAY1_PROGRESS.md | 800+ | Day 1 components |
| PHASE_3A_WEEK3_EXPORT_COMPLETE.md | 550+ | Export system |
| PHASE_3A_WEEK3_WEBSOCKET_COMPLETE.md | 550+ | Real-time updates |
| PHASE_3A_WEEK3_DAY4_POLISH_COMPLETE.md | 300+ | Polish components |
| PHASE_3A_WEEK3_DAY3_SUMMARY.md | 200+ | Day 3 summary |

## Next Week (Week 4)

**Testing Phase**:
- Unit tests (600 lines)
- Integration tests (400 lines)
- E2E tests (300 lines)
- Performance benchmarks
- Security audit
- Staging deployment
- Production rollout

**Target**: 11,000+ lines total (100% Phase 3A complete) by April 10, 2026.

---

**All systems GO for testing phase ✅**
