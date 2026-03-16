# Monitoring & Analytics Setup
## Error Tracking, Performance Monitoring, Business Metrics

---

## 🎯 Overview

Complete monitoring infrastructure with:
- **Error Tracking** (Sentry) - Real-time exception monitoring
- **Performance Monitoring** (New Relic, DataDog) - API, database, cache metrics
- **Business Metrics** - Transaction tracking and KPI monitoring
- **Log Aggregation** - Centralized logging and analysis
- **Analytics Dashboard** - Custom dashboards and insights

---

## 🔴 Error Tracking (Sentry)

### Setup

```bash
# Install Sentry Laravel package
composer require sentry/sentry-laravel

# Publish configuration
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"

# Set environment variables
SENTRY_LARAVEL_DSN=https://your-key@sentry.io/your-project-id
SENTRY_RELEASE=v1.0.0
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Features

✅ **Real-time Exception Monitoring**
- Captures all unhandled exceptions
- Groups similar errors
- Tracks error frequency and impact

✅ **User Context**
- Associates errors with user accounts
- Tracks user actions leading to errors
- Identifies affected users

✅ **Breadcrumbs**
- Records user actions and events
- Tracks database queries
- Monitors HTTP requests
- Logs cache operations

✅ **Performance Monitoring**
- Transaction tracing
- Slow span detection
- Database query performance
- API endpoint monitoring

✅ **Release Tracking**
- Tracks which release caused issues
- Compares error rates across releases
- Rollback detection

### Usage

```php
// In your service or controller
use App\Services\ErrorTrackingService;

class YourService {
    public function __construct(private ErrorTrackingService $tracking) {}
    
    public function process() {
        try {
            // Your code
        } catch (Exception $e) {
            $this->tracking->reportException($e, [
                'operation' => 'data_processing',
                'user_id' => auth()->id(),
            ]);
        }
    }
}

// Set user context
$this->tracking->setUserContext($userId, $email, $name);

// Add breadcrumb for tracking user action
$this->tracking->addBreadcrumb('Concert created', 'marketplace', [
    'concert_id' => $concertId,
    'price' => $price,
]);

// Custom tags for filtering
$this->tracking->addTag('vertical', 'concerts');
$this->tracking->addTag('operation', 'create');

// Performance issue alerts
$this->tracking->reportPerformanceIssue(
    'api_call',
    $duration,
    $threshold = 1000
);
```

### Sentry Dashboard

Features available at [sentry.io](https://sentry.io):

1. **Issues Overview**
   - Error frequency
   - Affected users
   - Stack traces
   - Source maps

2. **Performance**
   - Transaction durations
   - Slow operations
   - Error rates by transaction
   - Web Vitals

3. **Release Health**
   - Crash-free rate per release
   - Session duration
   - Adoption metrics
   - Regression detection

4. **Alerts**
   - Error spike detection
   - Regression alerts
   - Custom thresholds
   - Integration with Slack/Email

---

## 📊 Performance Monitoring

### New Relic Integration

```bash
# Install New Relic PHP agent
composer require newrelic/newrelic-php-agent

# Configure
NEWRELIC_APPNAME="CatVRF Production"
NEWRELIC_LICENSE_KEY=your-license-key
```

### DataDog Integration

```bash
# Install DataDog PHP library
composer require datadog/dd-trace

# Configure
DATADOG_ENABLED=true
DATADOG_API_KEY=your-api-key
DATADOG_STATSD_HOST=localhost
DATADOG_STATSD_PORT=8125
DATADOG_APM_ENABLED=true
```

### Metrics Collected

✅ **API Performance**
```php
$this->performance->recordApiCall(
    method: 'GET',
    endpoint: '/api/concerts',
    statusCode: 200,
    duration: 245.5,
    memoryUsage: 2097152
);
```

Tracks:
- Response times
- Status codes
- Memory usage
- Request volume
- Error rates by endpoint

✅ **Database Performance**
```php
$this->performance->recordDatabaseQuery(
    query: 'SELECT * FROM concerts WHERE ...',
    duration: 87.3,
    bindings: [1, 'jazz']
);
```

Tracks:
- Query execution time
- Query frequency
- Slow query detection
- Index effectiveness

✅ **Cache Performance**
```php
$this->performance->recordCacheOperation(
    operation: 'get',
    key: 'concerts:list:page:1',
    hit: true,
    duration: 1.2
);
```

Tracks:
- Hit rate percentage
- Operation latency
- Cold cache keys
- Cache efficiency

✅ **Business Metrics**
```php
$this->performance->recordTransaction(
    type: 'concert_booking',
    status: 'success',
    amount: 150.00,
    metadata: ['venue' => 'Grand Hall']
);
```

Tracks:
- Transaction volume
- Success/failure rates
- Revenue metrics
- Transaction types

✅ **Page Load Metrics (Web Vitals)**
```php
$this->performance->recordPageLoadMetrics([
    'first_contentful_paint' => 1200,
    'largest_contentful_paint' => 2400,
    'cumulative_layout_shift' => 0.1,
    'first_input_delay' => 50,
    'time_to_first_byte' => 300,
]);
```

Tracks:
- FCP (First Contentful Paint)
- LCP (Largest Contentful Paint)
- CLS (Cumulative Layout Shift)
- FID (First Input Delay)
- TTFB (Time to First Byte)

---

## 📈 Monitoring Middleware

Automatically integrated via `MonitoringMiddleware`:

```php
// In app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\MonitoringMiddleware::class,
];
```

**Automatically tracks:**
- All API requests
- Response times and status codes
- User context and correlation ID
- Memory usage
- Exceptions and errors

---

## 📊 Metrics Dashboard

### Available Commands

#### Analyze Performance Metrics

```bash
# Analyze metrics from last 24 hours
php artisan metrics:analyze

# Analyze metrics from last 7 days
php artisan metrics:analyze --period=7d

# Analyze metrics from last month
php artisan metrics:analyze --period=1m
```

**Output includes:**
- API performance statistics
- Database query analysis
- Cache effectiveness
- Business transaction metrics
- Generated alerts

---

## 🔧 Configuration Files

### config/sentry.php
- DSN and environment
- Tracing sample rate (10% recommended)
- Profile sample rate
- Breadcrumb configuration
- Ignored exceptions list

### config/datadog.php
- API credentials
- StatsD configuration
- APM settings
- Logging configuration
- Global tags

### config/services.php
- Service provider credentials
- API endpoints
- Timeout settings

---

## 📚 Database Schema

### metrics_log Table

Stores all collected metrics for analysis:

```
Columns:
- id (primary key)
- type (api_call, db_query, cache_operation, transaction, error, page_load)
- data (JSON)
- duration_ms (float)
- status (success, failure, warning)
- user_id (nullable foreign key)
- method (GET, POST, PUT, DELETE)
- endpoint (API path)
- status_code (HTTP status)
- correlation_id (for request tracing)
- memory_mb (memory usage)
- cpu_percent (CPU usage)
- environment (production, staging, development)
- version (app version)
- created_at (timestamp)

Indexes:
- type + created_at (for filtering)
- status + created_at (for error tracking)
- user_id + created_at (for user analysis)
- correlation_id (for request tracing)
```

Create table:
```bash
php artisan migrate
```

---

## 📡 API Monitoring

### Monitored Endpoints

All endpoints automatically tracked with:

```json
{
  "method": "GET",
  "endpoint": "/api/concerts",
  "status_code": 200,
  "duration_ms": 234.5,
  "memory_mb": 2.5,
  "user_id": 123,
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Performance Thresholds

| Endpoint Type | Target | Alert |
|---------------|--------|-------|
| **Read (GET)** | < 300ms | > 500ms |
| **Create (POST)** | < 500ms | > 1000ms |
| **Update (PUT)** | < 500ms | > 1000ms |
| **Delete** | < 300ms | > 500ms |
| **Search** | < 300ms | > 500ms |

---

## 🚨 Alert Configuration

### Sentry Alerts

**Conditions:**
- Error spike (20% increase in errors)
- New issues (first occurrence)
- Regression (error reappears after fix)
- Critical errors (selected exception types)

**Channels:**
- Email notifications
- Slack integration
- PagerDuty integration
- Custom webhooks

### DataDog Alerts

**Conditions:**
- Metric threshold exceeded
- Anomaly detection
- Forecast alert (predicted threshold breach)
- Change alert (significant deviation)

**Channels:**
- Email
- Slack
- Opsgenie
- Custom webhooks

---

## 📊 Custom Dashboards

### DataDog Dashboard

Create custom dashboard with:

```json
{
  "widgets": [
    {
      "type": "timeseries",
      "title": "API Response Times",
      "query": "avg:api.response_time{*}"
    },
    {
      "type": "distribution",
      "title": "Response Time Distribution",
      "query": "dist:api.response_time{*}"
    },
    {
      "type": "heatmap",
      "title": "Error Rate by Hour",
      "query": "rate(errors{*})"
    },
    {
      "type": "number",
      "title": "Success Rate",
      "query": "avg:transaction.success_rate{*}"
    }
  ]
}
```

### Sentry Dashboard

**Key Metrics Displayed:**
- Error frequency over time
- Most affected users
- Top error types
- Release health
- User session replay (if enabled)

---

## 🔍 Debugging

### View Sentry Issues

```bash
# Open Sentry dashboard
https://sentry.io/organizations/your-org/issues

# Filter by:
# - Release
# - Environment
# - User
# - Event ID
# - Correlation ID
```

### Query Metrics

```bash
# Query recent metrics
php artisan tinker

# Get API performance
DB::table('metrics_log')
  ->where('type', 'api_call')
  ->where('created_at', '>=', now()->subHours(1))
  ->avg('duration_ms')

# Get error count
DB::table('metrics_log')
  ->where('status', 'failure')
  ->count()

# Get cache hit rate
$cache = DB::table('metrics_log')
  ->where('type', 'cache_operation')
  ->get();
$hitRate = $cache->where('hit', true)->count() / $cache->count() * 100;
```

---

## 🎯 Best Practices

✅ **Use Correlation IDs**
- Track requests across services
- Easier debugging and tracing
- Link errors to user sessions

✅ **Set User Context**
- Identify affected users
- Track user-specific metrics
- Prioritize high-impact issues

✅ **Add Meaningful Breadcrumbs**
- Record important business events
- Track state changes
- Document user journey

✅ **Sample Appropriately**
- Traces: 5-20% (depends on volume)
- Profiles: 5-10%
- Logs: 100% for errors, 10% for info

✅ **Set Meaningful Tags**
- Vertical (concert, restaurant, taxi, etc.)
- Feature (marketplace, inventory, payroll)
- Environment (production, staging)

✅ **Monitor Business Metrics**
- Transaction success rate
- Revenue tracking
- User engagement metrics
- Feature adoption

---

## 📞 Support

### Sentry Support
- Documentation: https://docs.sentry.io/
- Discord Community: https://discord.gg/Ww9hbqr

### DataDog Support
- Documentation: https://docs.datadoghq.com/
- Support Portal: https://support.datadoghq.com/

### New Relic Support
- Documentation: https://docs.newrelic.com/
- Support: https://support.newrelic.com/

---

## ✅ Quick Checklist

- [ ] Sentry credentials configured
- [ ] DataDog credentials configured (optional)
- [ ] New Relic credentials configured (optional)
- [ ] MonitoringMiddleware registered
- [ ] Database migration run (metrics_log table created)
- [ ] ErrorTrackingService injected in services
- [ ] PerformanceMonitoringService tracking endpoints
- [ ] Alerts configured in Sentry
- [ ] Dashboards created in DataDog
- [ ] Team notified of monitoring setup

---

**Version**: 1.0  
**Created**: 15 March 2026  
**Status**: ✅ Complete
