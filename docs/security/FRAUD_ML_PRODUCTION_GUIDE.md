# Fraud ML Production Guide

**Version:** 1.0  
**Date:** 2026-04-23  
**Purpose:** Train, deploy, and monitor ML fraud detection models in production

## Overview

This guide covers the end-to-end process of training, deploying, and maintaining the fraud detection ML models (XGBoost, Isolation Forest, LSTM) for CatVRF.

## Prerequisites

- Python 3.11+
- scikit-learn, xgboost, torch
- ClickHouse access (for training data)
- Redis access (for feature store)
- Docker (for model deployment)
- Basic ML knowledge

## Training Pipeline

### 1. Data Collection from ClickHouse

```sql
-- Extract training data from fraud_features_online
SELECT 
    user_id,
    operation_type,
    behavioral_score,
    behavioral_anomaly,
    is_vpn,
    vpn_risk_level,
    tx_velocity_risk,
    amount_vs_avg,
    is_new_device,
    geo_mismatch,
    account_age_days,
    historical_block_rate,
    decision,  -- Label: 'block' = fraud, 'allow' = legitimate
    timestamp
FROM fraud_features_online
WHERE timestamp >= NOW() - INTERVAL 90 DAY
  AND decision IN ('block', 'allow')
INTO OUTFILE '/tmp/fraud_training_data.csv'
FORMAT CSVWithNames;
```

### 2. Feature Engineering (Python)

```python
# train/preprocess.py
import pandas as pd
import numpy as np
from sklearn.preprocessing import StandardScaler

# Load data
df = pd.read_csv('/tmp/fraud_training_data.csv')

# Create labels
df['is_fraud'] = (df['decision'] == 'block').astype(int)

# Feature selection
features = [
    'behavioral_score',
    'behavioral_anomaly',
    'is_vpn',
    'vpn_risk_level',
    'tx_velocity_risk',
    'amount_vs_avg',
    'is_new_device',
    'geo_mismatch',
    'account_age_days',
    'historical_block_rate',
]

X = df[features].fillna(0)
y = df['is_fraud']

# Train-test split (stratified)
from sklearn.model_selection import train_test_split
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, stratify=y, random_state=42
)

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

# Save scaler
import joblib
joblib.dump(scaler, 'scaler.pkl')
```

### 3. XGBoost Training

```python
# train/xgboost_train.py
import xgboost as xgb
from sklearn.metrics import classification_report, roc_auc_score

# Train XGBoost
model = xgb.XGBClassifier(
    n_estimators=200,
    max_depth=6,
    learning_rate=0.1,
    subsample=0.8,
    colsample_bytree=0.8,
    scale_pos_weight=10,  # Handle class imbalance (fraud ~1%)
    random_state=42,
    eval_metric='auc',
)

model.fit(
    X_train_scaled, y_train,
    eval_set=[(X_test_scaled, y_test)],
    verbose=True
)

# Evaluate
y_pred = model.predict(X_test_scaled)
y_prob = model.predict_proba(X_test_scaled)[:, 1]

print("ROC-AUC:", roc_auc_score(y_test, y_prob))
print(classification_report(y_test, y_pred))

# Feature importance
importance = model.feature_importances_
for feat, imp in zip(features, importance):
    print(f"{feat}: {imp:.4f}")

# Save model
model.save_model('xgboost_v1.joblib')
```

### 4. Isolation Forest Training

```python
# train/isolation_forest_train.py
from sklearn.ensemble import IsolationForest

# Train on legitimate transactions only
legitimate_data = X_train_scaled[y_train == 0]

iso_forest = IsolationForest(
    n_estimators=100,
    max_samples='auto',
    contamination=0.1,  # Expected anomaly rate
    random_state=42,
)

iso_forest.fit(legitimate_data)

# Test on known fraud
fraud_data = X_test_scaled[y_test == 1]
fraud_scores = iso_forest.score_samples(fraud_data)
print("Average fraud anomaly score:", fraud_scores.mean())

# Save model
import joblib
joblib.dump(iso_forest, 'isolation_forest_v1.joblib')
```

### 5. LSTM Training (PyTorch)

```python
# train/lstm_train.py
import torch
import torch.nn as nn
from torch.utils.data import DataLoader, TensorDataset

# Prepare sequence data (last 10 actions per user)
def create_sequences(df, seq_length=10):
    sequences = []
    for user_id in df['user_id'].unique():
        user_data = df[df['user_id'] == user_id].sort_values('timestamp')
        for i in range(len(user_data) - seq_length):
            seq = user_data.iloc[i:i+seq_length][features].values
            sequences.append(seq)
    return np.array(sequences)

seq_train = create_sequences(df[df.index.isin(X_train.index)])
seq_test = create_sequences(df[df.index.isin(X_test.index)])

# LSTM Model
class FraudLSTM(nn.Module):
    def __init__(self, input_size, hidden_size=64):
        super().__init__()
        self.lstm = nn.LSTM(input_size, hidden_size, batch_first=True)
        self.fc = nn.Linear(hidden_size, 1)
        self.sigmoid = nn.Sigmoid()
    
    def forward(self, x):
        _, (hidden, _) = self.lstm(x)
        out = self.fc(hidden[-1])
        return self.sigmoid(out)

# Training
model = FraudLSTM(input_size=len(features))
criterion = nn.BCELoss()
optimizer = torch.optim.Adam(model.parameters(), lr=0.001)

# Convert to tensors
train_loader = DataLoader(
    TensorDataset(torch.FloatTensor(seq_train), torch.FloatTensor(y_train_seq)),
    batch_size=32,
    shuffle=True
)

for epoch in range(50):
    for batch_x, batch_y in train_loader:
        optimizer.zero_grad()
        outputs = model(batch_x).squeeze()
        loss = criterion(outputs, batch_y)
        loss.backward()
        optimizer.step()

# Save model
torch.save(model.state_dict(), 'lstm_v1.pt')
```

### 6. Model Validation

```python
# train/validate.py
import joblib
import torch

# Load models
xgb_model = xgb.XGBClassifier()
xgb_model.load_model('xgboost_v1.joblib')
iso_forest = joblib.load('isolation_forest_v1.joblib')
lstm_model = FraudLSTM(input_size=len(features))
lstm_model.load_state_dict(torch.load('lstm_v1.pt'))

# Ensemble prediction
def ensemble_predict(X):
    xgb_score = xgb_model.predict_proba(X)[:, 1]
    iso_score = 1 - (iso_forest.score_samples(X) + 0.5)  # Convert to 0-1
    lstm_score = lstm_model(torch.FloatTensor(X.reshape(1, -1, len(features))))
    
    ensemble = 0.5 * xgb_score + 0.3 * iso_score + 0.2 * lstm_score
    return ensemble

# Validate
test_scores = ensemble_predict(X_test_scaled)
test_pred = (test_scores > 0.5).astype(int)

print("Ensemble ROC-AUC:", roc_auc_score(y_test, test_scores))
print("Recall (fraud detection):", recall_score(y_test, test_pred))
print("Precision:", precision_score(y_test, test_pred))

# Targets: > 95% recall, < 5% false positive rate
```

## Deployment

### 1. Model Versioning

```bash
# Version naming: YYYY-MM-DD-v{version}
mv xgboost_v1.joblib xgboost_2026-04-23-v1.joblib
mv isolation_forest_v1.joblib isolation_forest_2026-04-23-v1.joblib
mv lstm_v1.pt lstm_2026-04-23-v1.pt

# Copy to production
cp *.joblib /var/www/catvrf/storage/models/fraud/
cp *.pt /var/www/catvrf/storage/models/fraud/
```

### 2. Laravel Integration Update

Update `app/Services/Fraud/FraudMLService.php` to load real models:

```php
private function predictXGBoost(array $features, string $correlationId): float
{
    $modelPath = storage_path('models/fraud/xgboost_v1.joblib');
    
    if (!file_exists($modelPath)) {
        return $this->predictXGBoostStub($features, $correlationId);
    }
    
    // Call Python microservice for inference
    $response = Http::post('http://localhost:5000/predict/xgboost', [
        'features' => $features,
    ]);
    
    return $response->json('score', 0.0);
}
```

### 3. Python Microservice (FastAPI)

```python
# inference_service.py
from fastapi import FastAPI
import joblib
import numpy as np
from pydantic import BaseModel

app = FastAPI()

# Load models
xgb_model = joblib.load('storage/models/fraud/xgboost_v1.joblib')
iso_forest = joblib.load('storage/models/fraud/isolation_forest_v1.joblib')
scaler = joblib.load('storage/models/fraud/scaler.pkl')

class Features(BaseModel):
    behavioral_score: float
    behavioral_anomaly: int
    is_vpn: int
    vpn_risk_level: float
    tx_velocity_risk: float
    amount_vs_avg: float
    is_new_device: int
    geo_mismatch: int
    account_age_days: int
    historical_block_rate: float

@app.post("/predict/xgboost")
async def predict_xgboost(features: Features):
    X = np.array([list(features.dict().values())])
    X_scaled = scaler.transform(X)
    score = xgb_model.predict_proba(X_scaled)[0, 1]
    return {"score": float(score)}

@app.post("/predict/isolation_forest")
async def predict_isolation_forest(features: Features):
    X = np.array([list(features.dict().values())])
    X_scaled = scaler.transform(X)
    score = 1 - (iso_forest.score_samples(X_scaled)[0] + 0.5)
    return {"score": float(score)}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=5000)
```

Deploy with Docker:

```dockerfile
# Dockerfile.inference
FROM python:3.11-slim

WORKDIR /app
COPY inference_service.py .
COPY storage/models/fraud ./storage/models/fraud/

RUN pip install fastapi uvicorn xgboost scikit-learn numpy

CMD ["uvicorn", "inference_service:app", "--host", "0.0.0.0", "--port", "5000"]
```

```bash
docker build -t fraud-ml-inference -f Dockerfile.inference .
docker run -d -p 5000:5000 fraud-ml-inference
```

### 4. A/B Testing

```php
// config/fraud-ml.php
'ab_testing' => [
    'enabled' => true,
    'percentage' => 10,  // 10% traffic to new model
    'new_model_version' => '2026-04-23-v1',
    'old_model_version' => '2026-03-25-v1',
],

// In FraudMLService.php
private function predictXGBoost(array $features, string $correlationId): float
{
    $useNewModel = random_int(1, 100) <= config('fraud-ml.ab_testing.percentage');
    
    if ($useNewModel) {
        return $this->predictXGBoostNew($features, $correlationId);
    }
    
    return $this->predictXGBoostOld($features, $correlationId);
}
```

## Monitoring

### 1. Drift Detection

```python
# monitoring/drift_detection.py
from scipy import stats
import pandas as pd

def detect_drift():
    # Get last 7 days of features
    current_features = get_clickhouse_features(days=7)
    baseline_features = get_baseline_features()
    
    # KS test for each feature
    drift_detected = False
    for feature in features:
        statistic, p_value = stats.ks_2samp(
            current_features[feature],
            baseline_features[feature]
        )
        
        if p_value < 0.05:  # Significant drift
            print(f"Drift detected in {feature}: p={p_value}")
            drift_detected = True
    
    if drift_detected:
        send_alert("Feature drift detected - retrain models")
```

### 2. Performance Metrics

```php
// Monitor via Prometheus
$fraudScore = Histogram::new('fraud_ml_score', 'Fraud ML prediction score');
$fraudLatency = Histogram::new('fraud_ml_latency_ms', 'Fraud ML inference latency');
$fraudDecision = Counter::new('fraud_ml_decision_total', 'Fraud ML decisions', ['decision']);

$fraudScore->observe($result['score']);
$fraudLatency->observe($result['latency_ms']);
$fraudDecision->inc(['decision' => $result['decision']]);
```

### 3. Alerting

Grafana dashboard queries:

```promql
# High fraud rate
rate(fraud_ml_decision_total{decision="block"}[5m]) > 0.1

# Slow inference
histogram_quantile(0.95, rate(fraud_ml_latency_ms_bucket[5m])) > 50

# Model accuracy (from feedback loop)
rate(fraud_ml_false_positive_total[1h]) / rate(fraud_ml_predictions_total[1h]) > 0.05
```

## Retraining Schedule

### Automated Retraining (Cron)

```yaml
# .github/workflows/retrain-fraud-models.yml
name: Retrain Fraud Models
on:
  schedule:
    - cron: '0 2 * * 0'  # Every Sunday at 2 AM
  workflow_dispatch:

jobs:
  retrain:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Train models
        run: |
          python train/preprocess.py
          python train/xgboost_train.py
          python train/isolation_forest_train.py
          python train/lstm_train.py
          python train/validate.py
      
      - name: Deploy models
        run: |
          rsync -av *.joblib user@server:/var/www/catvrf/storage/models/fraud/
          ssh user@server "docker restart fraud-ml-inference"
```

### Manual Retraining

```bash
# Trigger manual retrain
php artisan fraud:ml:retrain --model=xgboost
php artisan fraud:ml:retrain --model=all
```

## Troubleshooting

### Model Not Loading

```bash
# Check model file exists
ls -lh storage/models/fraud/

# Check file permissions
chmod 644 storage/models/fraud/*.joblib
chmod 644 storage/models/fraud/*.pt

# Check Python microservice
curl http://localhost:5000/health
```

### High False Positives

1. Check feature distribution drift
2. Adjust thresholds in `config/fraud-ml.php`
3. Lower VPN penalty if legitimate users affected
4. Review feature importance for unexpected signals

### Low Fraud Recall

1. Increase model complexity (n_estimators, max_depth)
2. Add more training data with fraud examples
3. Adjust scale_pos_weight for class imbalance
4. Add new features (e.g., session sequences)

### Slow Inference

1. Enable model caching in Redis
2. Use ONNX Runtime for faster inference
3. Deploy Python microservice with GPU
4. Batch predictions for multiple users

## Compliance Checklist

- [ ] PII anonymization in training data
- [ ] Medical data masking before external processing
- [ ] Audit log retention: 365 days
- [ ] SHAP explainability enabled for high-risk predictions
- [ ] Model versioning tracked
- [ ] A/B testing with rollback capability
- [ ] Drift detection alerts configured
- [ ] Regular security audits of ML pipeline

## References

- FraudML Architecture: `docs/security/FRAUD_ML_ARCHITECTURE.md`
- Config: `config/fraud-ml.php`
- Service: `app/Services/Fraud/FraudMLService.php`
- Tests: `tests/Unit/Services/Fraud/FraudMLServiceTest.php`
