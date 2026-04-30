# A/B Testing for CLV-Based Promotions - Setup Guide

**Version:** 1.0  
**Date:** 2026-04-28  
**Project:** CatVRF — AI-powered Healthcare Marketplace  

## Overview

This module provides production-ready A/B testing infrastructure for CLV (Customer Lifetime Value) based promotions. It turns analytics from "pretty numbers" into real revenue uplift, following best practices from Alibaba and Ozon where such experiments deliver **+15–35% GMV uplift** for sellers.

## Architecture

### Core Components

```
modules/Analytics/
├── Application/
│   ├── DTOs/
│   │   ├── ExperimentDTO.php
│   │   ├── VariantDTO.php
│   │   └── ExperimentResultDTO.php
│   ├── Services/
│   │   ├── ABTestingService.php
│   │   └── CLVExperimentDesigner.php
│   └── Facades/
│       └── ABTest.php
├── Models/
│   ├── Experiment.php
│   ├── ExperimentVariant.php
│   ├── ExperimentAssignment.php
│   └── ExperimentMetric.php
├── Infrastructure/
│   └── Jobs/
│       └── EvaluateExperimentJob.php
└── Filament/
    └── Resources/
        └── ExperimentResource/
```

### Database Schema

**experiments**
- Experiment configuration (name, target segment, CLV filters)
- Timing (start, end, scheduled)
- Status (draft, running, paused, finished)
- Results and winning variant

**experiment_variants**
- Variant configuration (discount, message, coupon)
- Traffic allocation per variant
- Sample size and metrics

**experiment_assignments**
- User assignments to variants (prevents leakage)
- CLV snapshot at assignment (stratification)
- Hash bucket for deterministic assignment
- Post-experiment metrics (revenue, orders, CLV delta)

**experiment_metrics**
- Daily aggregated metrics per variant
- Statistical analysis (mean, std, confidence intervals)
- Cumulative metrics for charts

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This creates the 4 tables: `experiments`, `experiment_variants`, `experiment_assignments`, `experiment_metrics`.

### 2. Register Service Provider (if needed)

The Analytics module should already be registered in `config/modules.php`. If not, add:

```php
'modules' => [
    'Analytics' => 'Modules\Analytics\AnalyticsServiceProvider',
],
```

### 3. Schedule Evaluation Job

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Evaluate all running experiments daily at 2 AM
    $schedule->command('abtest:evaluate')->dailyAt('02:00');
}
```

Create the command:

```bash
php artisan make:command EvaluateExperimentsCommand
```

```php
// app/Console/Commands/EvaluateExperimentsCommand.php
public function handle()
{
    $experiments = Experiment::running()->get();
    
    foreach ($experiments as $experiment) {
        EvaluateExperimentJob::dispatch($experiment->id);
    }
    
    $this->info("Queued evaluation for {$experiments->count()} experiments.");
}
```

## Usage

### Creating a CLV Discount Experiment

```php
use Modules\Analytics\Application\Facades\ABTest;

$experiment = ABTest::forSeller($sellerId)
    ->inTenant($tenantId)
    ->createCLVExperiment([
        'name' => 'Test 15% vs 10% discount for VIP customers',
        'discounts' => [15, 10, 0], // 15%, 10%, control
        'duration_days' => 14,
        'target_segment' => 'vip',
    ]);
```

### Assigning Buyers to Variants

```php
use Modules\Analytics\Application\Facades\ABTest;

$variant = ABTest::assign(
    experimentKey: '123_vip_discount_20260428_abc123',
    buyerId: $buyerId,
    sellerId: $sellerId,
    tenantId: $tenantId,
);

// Apply the discount
if ($variant->hasDiscount()) {
    Coupon::applyToCart($variant->getDiscount(), $buyerId, $variant->getCouponCode());
    
    // Record exposure when user sees the promotion
    ABTest::recordExposure($experimentKey, $buyerId, $sellerId, $tenantId);
}
```

### Managing Experiment Lifecycle

```php
use Modules\Analytics\Application\Facades\ABTest;

// Start experiment
ABTest::start($experimentId);

// Pause experiment
ABTest::pause($experimentId);

// Finish experiment
ABTest::finish($experimentId);

// Evaluate immediately (usually done via scheduled job)
\Modules\Analytics\Infrastructure\Jobs\EvaluateExperimentJob::dispatch($experimentId);
```

### Designing Custom Experiments

```php
use Modules\Analytics\Application\Facades\ABTest;

// Get configuration without creating
$config = ABTest::forSeller($sellerId)
    ->inTenant($tenantId)
    ->designCLVExperiment([
        'discounts' => [20, 15, 10, 0],
        'confidence_level' => 0.99,
        'effect_size' => 0.15,
    ]);

// Modify config if needed
$config['name'] = 'Custom experiment name';

// Create with modified config
$experiment = app(ABTestingService::class)->createExperiment($config);
```

### Churn Prevention Experiment

```php
$experiment = ABTest::forSeller($sellerId)
    ->inTenant($tenantId)
    ->createChurnPreventionExperiment([
        'name' => 'Retention strategies for at-risk customers',
        'churn_threshold' => 0.5,
        'strategies' => ['discount', 'free_shipping', 'control'],
    ]);
```

### Message Personalization Experiment

```php
$experiment = ABTest::forSeller($sellerId)
    ->inTenant($tenantId)
    ->createPersonalizationExperiment([
        'name' => 'Personalization level test',
        'personalization_types' => ['generic', 'category_based', 'purchase_history'],
    ]);
```

## Filament Admin Interface

Navigate to `/admin/analytics/experiments` to access the experiment management interface.

### Features

- **List View**: All experiments with status, sample size, timing
- **Filters**: Filter by status and target segment
- **Actions**: Start, pause, finish, evaluate experiments
- **Edit**: Modify experiment configuration
- **View**: Detailed experiment view with variants
- **Variants Relation Manager**: Manage experiment variants

### Experiment Actions

- **Start**: Change status from `draft` to `running`
- **Pause**: Pause a running experiment
- **Finish**: Complete the experiment and stop assignments
- **Evaluate Now**: Trigger immediate metric evaluation

## API Integration

### Example: Integration with Order Flow

```php
// In your order/cart controller
public function showPromotion(int $buyerId, int $sellerId)
{
    $experimentKey = config('abtest.experiment_key');
    
    $variant = ABTest::assign($experimentKey, $buyerId, $sellerId, tenant()->id);
    
    return response()->json([
        'discount' => $variant->getDiscount(),
        'message' => $variant->getMessage(),
        'coupon_code' => $variant->getCouponCode(),
    ]);
}
```

### Example: Post-Order Metric Update

```php
// After order completion, update metrics
$assignment = ExperimentAssignment::findExisting(
    tenant()->id,
    $experimentId,
    $sellerId,
    $buyerId
);

if ($assignment) {
    $assignment->updateMetrics([
        'revenue_14d' => $order->total,
        'orders_14d' => 1,
    ]);
}
```

## Statistical Analysis

### Methodology

The system uses **Bayesian A/B testing** with the following approach:

1. **Daily Metric Aggregation**: Metrics are aggregated daily per variant
2. **Statistical Testing**: Two-sample t-test for comparing variants vs control
3. **Confidence Intervals**: 95% CI calculated for all metrics
4. **Significance Threshold**: p-value < 0.05 for statistical significance

### Metrics Tracked

**Primary Metrics:**
- `revenue_14d`: Revenue 14 days after assignment
- `revenue_30d`: Revenue 30 days after assignment
- `orders_count`: Number of orders
- `clv_delta`: Change in predicted CLV
- `churn_prob_delta`: Change in churn probability

**Secondary Metrics:**
- Conversion rate
- Click-through rate
- Retention rate

### Result Interpretation

Results are stored in `experiments.results`:

```json
{
  "status": "evaluated",
  "primary_metric": "revenue_14d",
  "variant_results": {
    "A": {
      "lift_percent": 15.5,
      "p_value": 0.023,
      "is_significant": true,
      "variant_mean": 1250.50,
      "control_mean": 1082.50
    }
  },
  "winning_variant_id": 123,
  "winning_variant_key": "A"
}
```

## Best Practices

### Sample Size

**Minimum sample size: 300–500 users per variant** (calculated via power analysis)

The system automatically calculates recommended sample size based on:
- Population size (segment count)
- Confidence level (default 95%)
- Effect size (default 10% lift)

### Always Include Control

Every experiment must have a control variant (discount = 0, no intervention).

### Prevent Leakage

The system uses:
- **Unique constraint**: One assignment per buyer-seller-experiment
- **Hash-based assignment**: Deterministic, no race conditions
- **CLV snapshot**: Captured at assignment time for stratification

### Traffic Percentage

Start with conservative traffic (10–30%) and increase if results are positive.

### Duration

- **Minimum**: 7 days (for short-term metrics)
- **Recommended**: 14–30 days (for reliable revenue metrics)
- **Churn experiments**: 30+ days (churn patterns take time)

### GDPR Compliance

- Assignments can be deleted via GDPR request (soft deletes)
- PII anonymization before external ML calls
- Audit logging for all assignments

### Rate Limiting

Implement rate limiting on promotion endpoints to prevent abuse:

```php
// In your controller
RateLimiter::for('abtest-assign', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()->id);
});
```

## Performance Optimization

### Caching

Assignment results are cached for 1 hour:

```php
Cache::tags(["abtest:{$tenantId}", "seller:{$sellerId}"])
    ->remember($cacheKey, 3600, fn() => ...);
```

Cache is invalidated when:
- Experiment status changes (start/pause/finish)
- New variants are added

### Database Indexes

All tables have proper indexes for:
- Status queries
- Date ranges
- Foreign keys
- Unique constraints

### Async Evaluation

Metric evaluation runs in background jobs to avoid blocking:
```php
EvaluateExperimentJob::dispatch($experimentId);
```

## Monitoring

### Key Metrics to Monitor

1. **Assignment Rate**: Number of assignments per day
2. **Exposure Rate**: Number of users who actually saw promotions
3. **Sample Size Growth**: Ensure sufficient sample per variant
4. **Job Execution**: Evaluation job should run daily
5. **Cache Hit Rate**: Should be > 90%

### Alerts

Set up alerts for:
- Evaluation job failures
- Experiments running > 60 days
- Sample size imbalance (> 2:1 ratio between variants)
- Control variant sample size < 100

## Troubleshooting

### Experiment Not Assigning

**Problem**: Users always getting control variant

**Solutions:**
1. Check experiment status is `running`
2. Verify `traffic_percent` > 0
3. Check CLV filters match user segment
4. Verify hash bucket calculation

### Uneven Sample Sizes

**Problem**: Sample sizes significantly different between variants

**Solutions:**
1. Check `traffic_allocation` sums to 100
2. Verify hash distribution is uniform
3. Check for assignment caching issues

### No Statistical Significance

**Problem**: p-value always > 0.05

**Solutions:**
1. Increase sample size (run longer or increase traffic)
2. Check for effect size too small
3. Verify metric calculation is correct
4. Consider using CUPED for variance reduction

## Production Deployment Checklist

- [ ] Run migrations on production
- [ ] Schedule evaluation job in cron
- [ ] Configure monitoring/alerts
- [ ] Test assignment flow with small experiment
- [ ] Verify Filament admin access
- [ ] Review audit logs
- [ ] Set rate limits on promotion endpoints
- [ ] Document experiment naming convention
- [ ] Train sellers on using the interface
- [ ] Configure backup strategy for assignment data

## Future Enhancements

1. **Sequential Testing**: Stop experiments early if results are conclusive
2. **CUPED**: Use pre-experiment data to reduce variance
3. **Multi-Armed Bandit**: Dynamically allocate traffic to best-performing variants
4. **Python Bridge**: Use scipy.stats for more accurate statistical tests
5. **Real-time Dashboards**: WebSocket-based live experiment monitoring
6. **Automated Winner Application**: Auto-apply winning variant to all traffic

## References

- Alibaba A/B Testing Platform Documentation
- Ozon Experimentation Framework
- "Trustworthy Online Controlled Experiments" - Kohavi et al.
- Bayesian A/B Testing with Python - Cameron Davidson-Pilon

## Support

For issues or questions:
- GitHub Issues: https://github.com/nanolord126/CatVRF/issues
- Documentation: `/docs/analytics/`
- Contact: Analytics Team

---

**Author:** CatVRF Analytics Team  
**Last Updated:** 2026-04-28
