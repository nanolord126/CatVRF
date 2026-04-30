# Fraud ML Production Guide

## Overview

This guide covers the production deployment and operation of the CatVRF ML-based fraud detection system.

**Production 2026 CANON:**
- Multi-layered ML fraud detection (Behavioral + Device + Geo + Transaction)
- Ensemble models: XGBoost (supervised), Isolation Forest (unsupervised), LSTM (sequential)
- < 50ms inference time
- False positive rate < 5% for legitimate VPN users
- Recall > 95% on test data
- Explainable with SHAP values
- VPN alone does NOT block - only with additional signals

## Architecture

### Layer 1: Real-time Feature Extraction

**Behavioral Biometrics (35% weight)**
- Keystroke dynamics (hold time, transition time, typing speed)
- Mouse/touch patterns (velocity, acceleration, curvature)
- Session behavior (duration, active time ratio, navigation patterns)
- Time-of-day patterns

**Device & Session Features (20% weight)**
- Device fingerprint (user-agent, screen resolution, timezone, language)
- Device type (mobile, tablet, desktop)
- Browser and OS detection
- Known/trusted device status
- Device authentication count and age

**Geo Features (15% weight)**
- IP geolocation (country, city, region)
- Geo consistency with user profile
- Geo distance calculation
- Russian territories compliance (Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia)

**Network Features (25% weight)**
- VPN detection (MaxMind, IP2Location, AbuseIPDB)
- Residential proxy detection
- Tor exit node detection
- Datacenter/hosting detection
- Corporate VPN whitelist

**Transaction Features (10% weight)**
- Transaction amount and currency
- High-value transaction flag
- Action velocity (actions per minute/hour)
- Transaction type

**User History Features (5% weight)**
- Account age
- New account flag
- Transaction history
- Failed authentication attempts

### Layer 2: ML Models (Ensemble)

**XGBoost (40% weight) - Supervised**
- Detects known fraud patterns from labeled historical data
- Handles imbalanced data (fraud < 1%)
- Feature importance analysis
- SHAP explainability

**Isolation Forest (35% weight) - Unsupervised**
- Anomaly detection from user baseline
- Identifies new fraud patterns not in training data
- Contamination parameter for expected fraud rate
- Per-user baseline comparison

**LSTM (25% weight) - Sequential**
- Analyzes action sequences within a session
- Temporal pattern recognition
- Handles variable-length sequences
- Detects rapid successive actions

### Layer 3: Risk Scoring & Decision Engine

**Risk Thresholds**
- Low: < 0.4 (allow)
- Medium: 0.4-0.7 (challenge or soft Cooldown)
- High: >= 0.7 (block + Cooldown + notification)

**VPN Handling (Precision Tuning)**
- VPN alone penalty: -0.15 (reduces score)
- Corporate VPN discount: -0.20
- VPN with behavioral anomaly boost: +0.30
- VPN with geo mismatch boost: +0.30

**Actions**
- Allow: No cooldown, no notification
- Review: No cooldown, info notification
- Challenge: 1-hour soft Cooldown, warning notification
- Block: 24-hour Cooldown, critical notification, SplitKey invalidation, session logout

## Model Training

### Prerequisites

```bash
# Python 3.11+
python --version

# Install dependencies
cd python-ml
pip install -r requirements.txt
```

### Data Preparation

**Extract from ClickHouse**

```sql
-- Extract fraud features for training
SELECT
    user_id,
    tenant_id,
    behavioral_score,
    typing_score,
    mouse_score,
    is_vpn,
    is_residential_proxy,
    geo_match,
    is_known_device,
    transaction_amount,
    account_age_hours,
    actions_per_minute,
    is_fraud_label
FROM fraud_features_online
WHERE created_at >= NOW() - INTERVAL 30 DAY
```

**Feature Engineering**

```python
# python-ml/scripts/prepare_training_data.py
import pandas as pd
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split

# Load data
df = pd.read_csv('fraud_features.csv')

# Handle imbalanced data
fraud_samples = df[df['is_fraud_label'] == 1]
normal_samples = df[df['is_fraud_label'] == 0].sample(
    n=len(fraud_samples) * 10,  # 10:1 ratio
    random_state=42
)
balanced_df = pd.concat([fraud_samples, normal_samples])

# Split data
X_train, X_val, y_train, y_val = train_test_split(
    balanced_df.drop('is_fraud_label', axis=1),
    balanced_df['is_fraud_label'],
    test_size=0.2,
    random_state=42,
    stratify=balanced_df['is_fraud_label']
)

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_val_scaled = scaler.transform(X_val)
```

### Training XGBoost Model

```python
# python-ml/scripts/train_xgboost.py
from models.xgboost_fraud_model import XGBoostFraudModel

model = XGBoostFraudModel(
    model_path='storage/models/xgboost/fraud_model.json',
)

# Train
metrics = model.train(
    X_train=pd.DataFrame(X_train_scaled, columns=feature_names),
    y_train=y_train,
    X_val=pd.DataFrame(X_val_scaled, columns=feature_names),
    y_val=y_val,
)

print(f"Training metrics: {metrics}")

# Save model
model.save_model()
```

### Training Isolation Forest Model

```python
# python-ml/scripts/train_isolation_forest.py
from models.isolation_forest_anomaly import IsolationForestAnomalyModel

model = IsolationForestAnomalyModel(
    model_path='storage/models/isolation_forest/anomaly_model.pkl',
    contamination=0.1,  # Expected fraud rate
)

# Train (unsupervised - no labels needed)
metrics = model.train(X_train=pd.DataFrame(X_train_scaled, columns=feature_names))

print(f"Training metrics: {metrics}")

# Save model
model.save_model()
```

### Training LSTM Model

```python
# python-ml/scripts/train_lstm.py
from models.lstm_sequence_model import LSTMSequenceAnalyzer

model = LSTMSequenceAnalyzer(
    model_path='storage/models/lstm/sequence_model.pth',
    sequence_length=50,
)

# Prepare sequence data
sequences = prepare_action_sequences(user_sessions)
labels = prepare_sequence_labels(user_sessions)

# Train
metrics = model.train(
    sequences=sequences,
    labels=labels,
    epochs=10,
    batch_size=32,
    learning_rate=0.001,
)

print(f"Training metrics: {metrics}")

# Save model
model.save_model()
```

## Model Deployment

### Option 1: ONNX Runtime (Recommended)

**Convert models to ONNX**

```bash
# Convert XGBoost to ONNX
python-ml/scripts/convert_xgboost_to_onnx.py

# Convert PyTorch LSTM to ONNX
python-ml/scripts/convert_lstm_to_onnx.py
```

**Deploy with PHP ONNX Runtime**

```php
// In FraudMLService.php
use OnnxRuntime\InferenceSession;

$session = new InferenceSession('storage/models/onnx/fraud_ensemble.onnx');
$result = $session->run($inputData);
```

### Option 2: Python Microservice

**Start inference servers**

```bash
# Start XGBoost server (port 8001)
cd python-ml
python -m models.xgboost_fraud_model

# Start Isolation Forest server (port 8002)
python -m models.isolation_forest_anomaly

# Start LSTM server (port 8003)
python -m models.lstm_sequence_model
```

**Configure in config/fraud-ml.php**

```php
'python_service' => [
    'enabled' => true,
    'url' => env('FRAUD_ML_PYTHON_SERVICE_URL', 'http://localhost:8000'),
    'timeout_ms' => 50,
    'retry_attempts' => 2,
],
```

### Option 3: Stubby Rules (Fallback)

The FraudMLService includes rule-based predictions as a fallback when ML models are unavailable. This ensures fail-open behavior.

## Monitoring

### Performance Metrics

**Inference Latency**
```bash
# Check average inference time
grep "latency_ms" storage/logs/fraud_ml.log | awk '{sum+=$1; count++} END {print sum/count}'
```

Target: < 50ms

**False Positive Rate**
```sql
-- Calculate FPR from audit logs
SELECT
    COUNT(CASE WHEN fraud_score > 0.7 AND is_legitimate = true THEN 1 END) * 100.0 / COUNT(*) as fpr
FROM fraud_audit_log
WHERE created_at >= NOW() - INTERVAL 7 DAY
```

Target: < 5%

**Recall**
```sql
-- Calculate recall from labeled data
SELECT
    COUNT(CASE WHEN fraud_score > 0.7 AND is_fraud = true THEN 1 END) * 100.0 / 
    COUNT(CASE WHEN is_fraud = true THEN 1 END) as recall
FROM fraud_audit_log
WHERE created_at >= NOW() - INTERVAL 7 DAY
```

Target: > 95%

### Drift Detection

**Feature Distribution Drift**

```python
# python-ml/scripts/detect_drift.py
from scipy.stats import ks_2samp

# Compare current feature distribution with training distribution
current_features = load_current_features()
training_features = load_training_features()

for feature in feature_names:
    statistic, p_value = ks_2samp(
        current_features[feature],
        training_features[feature]
    )
    if p_value < 0.05:  # Significant drift
        alert_drift(feature, statistic, p_value)
```

**Prediction Drift**

```sql
-- Monitor average fraud score over time
SELECT
    DATE(created_at) as date,
    AVG(fraud_score) as avg_score,
    COUNT(*) as prediction_count
FROM fraud_audit_log
WHERE created_at >= NOW() - INTERVAL 30 DAY
GROUP BY DATE(created_at)
ORDER BY date DESC
```

### Alerting

**Grafana Dashboard**

Set up alerts in Grafana for:
- Inference latency > 50ms (warning)
- Inference latency > 100ms (critical)
- False positive rate > 5% (warning)
- False positive rate > 10% (critical)
- Recall < 90% (warning)
- Recall < 85% (critical)
- Feature drift detected (warning)

## Retraining Schedule

### Automated Retraining

**Weekly Retraining**

```bash
# Add to crontab
0 2 * * 0 cd /opt/catvrf && php artisan fraud:ml:retrain --weekly
```

**Retraining Command**

```php
// app/Console/Commands/RetrainFraudMLModels.php
class RetrainFraudMLModels extends Command
{
    protected $signature = 'fraud:ml:retrain {--weekly : Weekly retraining}';
    
    public function handle()
    {
        // 1. Extract new data from ClickHouse
        $newData = $this->extractTrainingData();
        
        // 2. Train models
        $this->trainXGBoost($newData);
        $this->trainIsolationForest($newData);
        $this->trainLSTM($newData);
        
        // 3. Validate models
        $metrics = $this->validateModels();
        
        // 4. Deploy if metrics meet thresholds
        if ($metrics['recall'] > 0.95 && $metrics['fpr'] < 0.05) {
            $this->deployModels();
        }
    }
}
```

### Canary Deployment

**Gradual Rollout**

```php
// config/fraud-ml.php
'ab_testing' => [
    'enabled' => true,
    'treatment_percentage' => 10,  // 10% of traffic to new model
    'treatment_model_version' => 'v1.1.0',
],
```

Monitor canary metrics before full rollout:
- Compare prediction distribution
- Compare false positive rate
- Compare recall
- Monitor user complaints

## Feature Store

### Redis Cache

```php
// Cache feature vectors for fast lookup
Cache::put(
    "fraud_features:{$userId}:{$sessionId}",
    $features,
    300 // 5 minutes TTL
);
```

### ClickHouse Feature Store

```sql
-- Create feature store table
CREATE TABLE IF NOT EXISTS fraud_features_online
(
    user_id UInt64,
    session_id String,
    features Map(String, Float64),
    created_at DateTime,
    expires_at DateTime
)
ENGINE = MergeTree()
ORDER BY (user_id, created_at)
TTL expires_at + INTERVAL 1 DAY;
```

## Security & Compliance

### Data Anonymization

**Medical Data (152-ФЗ, ФЗ-323)**

```php
// Anonymize medical features before sending to external ML
$anonymizedFeatures = [
    'behavioral_score' => $features['behavioral_score'],
    // ... other non-medical features
    // DO NOT include: symptoms, diagnosis, medical_history
];
```

**PII Masking**

```php
// Mask IP addresses in logs
$maskedIp = $this->maskIp($ipAddress); // 192.168.1.1 -> 192.168.***.***

// Mask user agents
$maskedUA = $this->maskUserAgent($userAgent); // Keep browser/OS only
```

### Audit Logging

```php
// All fraud predictions are logged to audit trail
$this->auditService->logEvent('fraud_ml_prediction', [
    'user_id' => $user->id,
    'fraud_score' => $score,
    'risk_level' => $riskLevel,
    'explanation' => $explanation,
    'correlation_id' => $correlationId,
], 'security');
```

### Russian Territories Compliance

Special handling for:
- Crimea, Sevastopol
- DPR, LPR
- Kherson, Zaporizhzhia

```php
// Geo mismatch with Russian territories triggers critical risk
if ($features['geo']['is_russian_territory_violation']) {
    $finalScore = min(1.0, $finalScore + 0.30);
}
```

## Troubleshooting

### High False Positive Rate

**Diagnosis**
```sql
-- Check which features contribute most to false positives
SELECT
    feature,
    AVG(contribution) as avg_contribution
FROM fraud_audit_log
WHERE is_legitimate = true AND fraud_score > 0.7
GROUP BY feature
ORDER BY avg_contribution DESC
LIMIT 10
```

**Solution**
- Adjust feature weights in config/fraud-ml.php
- Increase VPN alone penalty
- Retrain models with more negative samples

### High Inference Latency

**Diagnosis**
```bash
# Check which model is slow
grep "model.*latency" storage/logs/fraud_ml.log
```

**Solution**
- Enable ONNX Runtime (faster than Python microservice)
- Reduce feature count
- Enable model caching
- Use Redis for feature caching

### Model Drift

**Diagnosis**
```python
# Run drift detection script
python python-ml/scripts/detect_drift.py
```

**Solution**
- Trigger immediate retraining
- Update feature engineering
- Adjust model hyperparameters

## Performance Tuning

### Caching Strategy

```php
// Enable feature caching
'feature_store' => [
    'redis_enabled' => true,
    'redis_ttl_seconds' => 300,  // 5 minutes
],
```

### Async Inference

For non-critical operations, use async inference:

```php
// Dispatch to queue
ProcessFraudMLCheck::dispatch($request, $user, $actionType);
```

### Batch Prediction

For bulk operations, use batch prediction:

```python
# Python microservice supports batch prediction
@app.post("/predict_batch")
async def predict_batch(request: BatchPredictionRequest):
    scores = model.predict_batch(request.features_list)
    return {"scores": scores}
```

## Rollback Procedure

### Immediate Rollback

```bash
# 1. Disable ML system
php artisan config:set fraud-ml.enabled false

# 2. Clear cache
php artisan cache:clear

# 3. Restart services
php artisan queue:restart
```

### Model Version Rollback

```bash
# 1. Restore previous model version
cp storage/models/xgboost/fraud_model_v1.0.0.json \
   storage/models/xgboost/fraud_model.json

# 2. Reload models
php artisan fraud:ml:reload
```

## Support

For issues or questions:
- Check logs: `storage/logs/fraud_ml.log`
- Check metrics: Grafana dashboard
- Check documentation: `docs/FRAUD_ML_PRODUCTION_GUIDE.md`
- Contact: security@catvrf.ru
