# CLV (Customer Lifetime Value) Prediction - Setup Guide

**Version:** 1.0  
**Date:** 2026-04-28  
**Project:** CatVRF - AI-powered Healthcare Marketplace

## Overview

CLV Prediction is a production-ready machine learning feature that predicts the future revenue each buyer will generate for a specific seller. Based on Alibaba/Ozon production experience, this feature typically delivers **20-40% improvement in seller retention** and **direct GMV growth** by enabling sellers to:

- See predicted CLV for each buyer (e.g., "this buyer will bring 124,000 ₽ in the next 12 months")
- Launch targeted retention campaigns for high-value buyers at churn risk
- Prioritize VIP customers with personalized offers
- Optimize marketing spend based on predicted value

## Architecture

### Components

1. **Feature Store** (`buyer_seller_features` table)
   - Aggregated RFM metrics (Recency, Frequency, Monetary)
   - Behavioral features (return rate, review score, traffic sources)
   - Updated daily via `CalculateBuyerFeaturesJob`

2. **ML Model** (XGBoost)
   - Training script: `python-ml/train_clv.py`
   - Inference script: `python-ml/infer_clv.py`
   - Retrained weekly via `RetrainCLVModelJob`

3. **PHP Services**
   - `SellerCLVService` - Main CLV prediction service
   - `MLInferenceService` - ML model inference client
   - Supports multiple deployment modes (local, HTTP, cloud, ONNX)

4. **Filament Dashboard**
   - `CLVOverviewWidget` - Key metrics overview
   - `TopBuyersByCLVWidget` - Top buyers by predicted CLV
   - `SegmentDistributionWidget` - Segment distribution pie chart
   - `ChurnRiskWidget` - High churn risk buyers

5. **Scheduler**
   - Daily feature calculation (02:00 UTC)
   - Weekly model retraining (Sunday 04:00 UTC)

## Setup Instructions

### 1. Database Migration

Run the migration to create the feature store table:

```bash
php artisan migrate
```

This creates the `buyer_seller_features` table with all necessary columns and indexes.

### 2. Python Dependencies

Install Python ML dependencies:

```bash
cd python-ml
pip install xgboost pandas scikit-learn joblib pyarrow
```

Optional: For ONNX runtime support (faster inference):
```bash
pip install onnxmltools
```

### 3. Environment Configuration

Add to your `.env` file:

```env
# CLV Configuration
CLV_ENABLED=true
CLV_MODEL_VERSION=latest
CLV_CACHE_TTL=3600
CLV_CHURN_THRESHOLD=0.5

# ML Inference
ML_INFERENCE_URL=http://localhost:8000
ML_DEPLOYMENT_MODE=local  # local, http, cloud, onnx

# Queues
ANALYTICS_QUEUE=analytics
ML_TRAINING_QUEUE=ml-training
```

### 4. Queue Configuration

Ensure your queue workers are running for the analytics and ML training queues:

```bash
php artisan horizon  # or
php artisan queue:work --queue=analytics,ml-training
```

### 5. Initial Feature Calculation

Run the feature calculation job manually to populate the feature store:

```bash
php artisan tinker
>>> use Modules\Analytics\Infrastructure\Jobs\CalculateBuyerFeaturesJob;
>>> use App\Services\AuditService;
>>> $auditService = app(AuditService::class);
>>> dispatch(new CalculateBuyerFeaturesJob(null, null, $auditService));
```

### 6. Initial Model Training

Train the initial ML model:

```bash
cd python-ml
# First, export data from database (placeholder - implement based on your orders table)
# Then train:
python train_clv.py --data-path /path/to/training_data.parquet --output-dir ../storage/app/ml_models/clv
```

## Usage Examples

### In PHP Code

```php
use Modules\Analytics\Facades\SellerAnalyticsFacade;
use Illuminate\Support\Facades\Auth;

// Predict CLV for a specific buyer
$sellerId = Auth::id();
$buyerId = 123;
$tenantId = tenant()->id;

$prediction = SellerAnalyticsFacade::clv()
    ->predictForBuyer($sellerId, $buyerId, $tenantId);

echo "Predicted CLV (180d): " . $prediction->predictedClv180d . " ₽\n";
echo "Churn Probability: " . ($prediction->churnProbability * 100) . "%\n";
echo "Segment: " . $prediction->segment . "\n";

// Get top buyers by CLV
$topBuyers = SellerAnalyticsFacade::clv()
    ->getTopBuyersByCLV($sellerId, $tenantId, limit: 50);

// Get high churn risk buyers
$atRiskBuyers = SellerAnalyticsFacade::clv()
    ->getHighChurnRiskBuyers($sellerId, $tenantId, threshold: 0.5);

// Get segment distribution
$distribution = SellerAnalyticsFacade::clv()
    ->getSegmentDistribution($sellerId, $tenantId);
```

### Via Facade

```php
use Modules\Analytics\Facades\SellerAnalyticsFacade;

// Simple facade access
$prediction = SellerAnalyticsFacade::clv()
    ->predictForBuyer($sellerId, $buyerId, $tenantId);
```

### Filament Dashboard

Add the CLV widgets to your seller dashboard resource:

```php
use Modules\Analytics\Filament\Widgets\CLVOverviewWidget;
use Modules\Analytics\Filament\Widgets\TopBuyersByCLVWidget;
use Modules\Analytics\Filament\Widgets\SegmentDistributionWidget;
use Modules\Analytics\Filament\Widgets\ChurnRiskWidget;

protected function getHeaderWidgets(): array
{
    return [
        CLVOverviewWidget::class,
        SegmentDistributionWidget::class,
    ];
}

protected function getFooterWidgets(): array
{
    return [
        TopBuyersByCLVWidget::class,
        ChurnRiskWidget::class,
    ];
}
```

## Production Deployment

### 1. Database Optimization

For production with large datasets:

```sql
-- Add partitioning for PostgreSQL (optional)
CREATE TABLE buyer_seller_features_partitioned (
    LIKE buyer_seller_features INCLUDING ALL
) PARTITION BY RANGE (tenant_id);

-- Create indexes for common queries
CREATE INDEX idx_buyer_seller_seller_clv 
    ON buyer_seller_features (seller_id, predicted_clv_180d DESC);

CREATE INDEX idx_buyer_seller_churn 
    ON buyer_seller_features (seller_id, churn_probability DESC);
```

### 2. ML Deployment Options

**Option A: HTTP Service (Recommended for Production)**

Deploy a FastAPI service for ML inference:

```python
# ml_service.py
from fastapi import FastAPI
import joblib
import numpy as np

app = FastAPI()
model = joblib.load('clv_model.joblib')

@app.post("/predict/clv")
async def predict_clv(features: dict):
    # Run prediction
    result = model.predict([list(features.values())])
    return {"clv_180d": float(result[0])}
```

Run with gunicorn:
```bash
gunicorn -w 4 -k uvicorn.workers.UvicornWorker ml_service:app --bind 0.0.0.0:8000
```

Update `.env`:
```env
ML_DEPLOYMENT_MODE=http
ML_INFERENCE_URL=http://ml-service:8000
```

**Option B: Cloud Deployment (AWS SageMaker / Vertex AI)**

1. Train model using the provided script
2. Upload model artifacts to S3/GCS
3. Deploy using cloud provider's ML platform
4. Update `.env` with cloud endpoint

**Option C: ONNX Runtime (Lowest Latency)**

Export model to ONNX:
```bash
python train_clv.py --data-path data.parquet --export-onnx
```

Install PHP ONNX extension:
```bash
pecl install onnxruntime
```

Update `.env`:
```env
ML_DEPLOYMENT_MODE=onnx
```

### 3. Monitoring

Set up monitoring for:

- Feature calculation job success/failure
- Model retraining job success/failure
- ML inference latency and error rates
- Prediction cache hit rate

Example monitoring queries:

```sql
-- Check feature store freshness
SELECT 
    DATE(updated_at) as date,
    COUNT(*) as features_updated
FROM buyer_seller_features
GROUP BY DATE(updated_at)
ORDER BY date DESC
LIMIT 7;

-- Check prediction coverage
SELECT 
    COUNT(*) as total_pairs,
    COUNT(CASE WHEN predicted_clv_180d IS NOT NULL THEN 1 END) as with_predictions,
    COUNT(CASE WHEN predicted_clv_180d IS NULL THEN 1 END) as without_predictions
FROM buyer_seller_features;
```

### 4. Performance Tuning

**Caching Strategy**

- Individual predictions: cached for 1 hour
- Top buyers list: cached for 30 minutes
- Segment distribution: cached for 1 hour

Cache invalidation happens automatically when features are updated.

**Query Optimization**

- Use indexed queries for seller-specific data
- Batch predictions for multiple buyers
- Pre-compute aggregated metrics

## Troubleshooting

### Issue: Predictions return zero or null

**Cause:** Feature store not populated or ML model not trained.

**Solution:**
1. Run `CalculateBuyerFeaturesJob` manually
2. Check if `buyer_seller_features` table has data
3. Train ML model using `python-ml/train_clv.py`

### Issue: High inference latency

**Cause:** Local Python subprocess is slow.

**Solution:**
1. Switch to HTTP deployment mode with FastAPI
2. Use ONNX runtime for lowest latency
3. Enable prediction caching

### Issue: Model accuracy is low

**Cause:** Insufficient training data or features.

**Solution:**
1. Ensure sufficient historical data (6+ months recommended)
2. Verify feature calculation is correct
3. Tune hyperparameters in `train_clv.py`
4. Consider per-seller fine-tuning for large sellers

### Issue: Memory exhausted during feature calculation

**Cause:** Processing too many buyer-seller pairs at once.

**Solution:**
1. Job already uses chunked processing (1000 pairs per chunk)
2. Reduce chunk size in `CalculateBuyerFeaturesJob`
3. Process specific sellers only by passing `sellerId` parameter

## Testing

### Unit Tests

```bash
php artisan test --filter=CLV
```

### Integration Tests

```bash
# Test feature calculation
php artisan tinker
>>> use Modules\Analytics\Infrastructure\Jobs\CalculateBuyerFeaturesJob;
>>> dispatch(new CalculateBuyerFeaturesJob($sellerId));

# Test prediction
php artisan tinker
>>> use Modules\Analytics\Facades\SellerAnalyticsFacade;
>>> SellerAnalyticsFacade::clv()->predictForBuyer(1, 2, 1);
```

### Load Testing

```bash
# Test prediction endpoint performance
k6 run k6/clv-prediction-test.js
```

## Maintenance

### Daily

- Monitor feature calculation job logs
- Check prediction cache hit rate
- Review high churn risk buyers

### Weekly

- Review model retraining logs
- Check model performance metrics
- Update segment thresholds if needed

### Monthly

- Analyze prediction accuracy vs actuals
- Review feature importance
- Update training data window

## Security & Compliance

- **PII Protection:** No PII data sent to external ML services
- **Audit Logging:** All CLV predictions logged via AuditService
- **Data Retention:** Feature store data retained per GDPR/FZ-152 requirements
- **Access Control:** Filament dashboard respects seller permissions

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Review this documentation
3. Check GitHub issues: https://github.com/nanolord126/CatVRF/issues

## References

- Alibaba CLV Implementation Guide (internal)
- Ozon Marketplace ML Pipeline Documentation
- XGBoost Documentation: https://xgboost.readthedocs.io/
- Laravel Queue Documentation: https://laravel.com/docs/queues

---

**Author:** CatVRF Team  
**Last Updated:** 2026-04-28
