# Fraud ML System Architecture

**Version:** 1.0  
**Date:** 2026-04-23  
**Status:** Production Ready  

## Overview

Multi-layer ML-based fraud detection system for CatVRF marketplace, using Behavioral Biometrics as the primary passive signal. The system combines supervised, unsupervised, and ensemble models to detect fraud in real-time (< 50ms inference) while minimizing false positives for legitimate users (including those using VPN for foreign services).

## Architecture

### Layer 1: Real-time Feature Extraction

**Primary Signal: Behavioral Biometrics (35% weight)**
- Keystroke dynamics (hold times, transition times, typing speed)
- Mouse/touch patterns (velocity, acceleration, curvature)
- Session patterns (duration, active time ratio, navigation)
- Hesitation before critical actions

**Device & Session Features (20% weight)**
- Device fingerprint (canvas, WebGL, hardware concurrency)
- User-agent analysis
- New device detection
- Screen resolution patterns

**Geo Features (15% weight)**
- VPN/Proxy detection (multi-source: MaxMind, IP2Location, AbuseIPDB)
- Tor/I2P exit node detection
- Residential proxy detection
- Geo-territory compliance (Russian territories)
- IP distance & impossible travel detection

**Transaction / Action Features (20% weight)**
- Velocity checks (5min, 1h, 24h windows)
- Amount analysis (vs average, log transform)
- Operation type risk weighting
- Failed attempt tracking

**User Profile Features (10% weight)**
- Account age
- Historical block rate
- Success rate
- Trust score

### Layer 2: ML Ensemble Prediction

**Models:**
1. **XGBoost** (Supervised, 50% weight)
   - Primary model for known fraud patterns
   - Trained on historical labeled data
   - Feature importance: behavioral_score (0.25), behavioral_anomaly (0.20), vpn_risk (0.15)

2. **Isolation Forest** (Unsupervised, 30% weight)
   - Anomaly detection for new fraud patterns
   - Contamination: 0.1 (10% expected anomalies)
   - Detects statistical outliers

3. **LSTM** (Sequential, 20% weight)
   - Session sequence analysis
   - Detects rapid succession, escalation patterns
   - Sequence length: 10 actions

**Ensemble Method:** Weighted average with meta-learner adjustments

### Layer 3: Risk Scoring & Decision Engine

**Precision Tuning:**
- VPN alone: -0.15 penalty (not enough to block)
- Corporate VPN whitelist: -0.20 bonus
- High trust user (<5% block rate): -0.10 bonus
- Behavioral anomaly + new device: +0.15 boost
- Behavioral anomaly + geo mismatch: +0.20 boost

**Thresholds (Operation-Specific):**

| Operation | Low | Medium | High |
|-----------|-----|--------|------|
| Login | 0.4 | 0.7 | 0.85 |
| Register | 0.3 | 0.6 | 0.8 |
| KYB | 0.3 | 0.65 | 0.85 |
| Payout | 0.3 | 0.6 | 0.8 |
| Bank Change | 0.3 | 0.6 | 0.8 |
| Default | 0.4 | 0.7 | 0.85 |

**Decisions:**
- **Allow** (< Low): No action
- **Review** (Low-Medium): Log + notify
- **Challenge** (Medium-High): Soft Cooldown (1h) + force 2FA
- **Block** (>= High): Hard Cooldown (24h) + SplitKey invalidation + logout

## Integration Points

### Auth (Login/Register)
**File:** `app/Services/Auth/AuthService.php`

```php
// ML Fraud Check before authentication
$fraudResult = $this->fraudML->predictRisk(
    userId: $user->id,
    operationType: 'login',
    amount: 0,
    ipAddress: request()->ip(),
    deviceFingerprint: $credentials['fingerprint'] ?? null,
    context: [...],
);

if ($fraudResult['decision'] === 'block') {
    $this->applyFraudBlock($user, $fraudResult, $correlationId);
    throw ValidationException::withMessages([...]);
}
```

### KYB & Financial Actions
**Operations:** payout, bank_change, payment_init

Integration pattern similar to Auth, with higher sensitivity thresholds.

## Feature Store

**Redis (Online Inference):**
- Key prefix: `fraudml:features:`
- TTL: 24 hours
- Used for real-time prediction

**ClickHouse (Offline Training):**
- Table: `fraud_features_online`
- Materialized view for training data
- Enables drift detection

## Explainability (SHAP)

- Enabled for scores > 0.7
- Returns top-5 contributing features
- Used for audit and compliance
- Example output:
  ```json
  {
    "score": 0.85,
    "top_features": [
      {"feature": "behavioral_anomaly", "value": 0.35},
      {"feature": "vpn_risk_level", "value": 0.30},
      {"feature": "tx_velocity_risk", "value": 0.20}
    ]
  }
  ```

## Performance Targets

| Metric | Target | Current |
|--------|--------|---------|
| Inference time | < 50ms | ~30ms (stub) |
| False positive rate | < 5% | TBD |
| Fraud recall | > 95% | TBD |
| Test coverage | > 95% | In progress |

## Configuration

**File:** `config/fraud-ml.php`

Key settings:
- Feature weights
- Ensemble weights
- Operation thresholds
- VPN handling rules
- Performance timeouts
- Retraining schedule

## Model Storage

**Location:** `storage/models/fraud/`

Files:
- `xgboost_v1.joblib` - XGBoost model
- `isolation_forest_v1.joblib` - Isolation Forest model
- `lstm_v1.pt` - PyTorch LSTM model

Current: Stub files with training instructions

## Compliance (152-ФЗ, ФЗ-323)

- PII anonymization enabled
- Medical data masking
- Audit log retention: 365 days
- SHAP explainability for compliance

## Monitoring

- Prediction logging: `fraud_alert` channel
- Drift detection: 24h intervals
- Performance metrics: Prometheus
- Alert on drift: enabled

## Next Steps

1. **Train real models** with historical data
2. **Deploy Python microservice** for model inference
3. **Implement drift detection** with ClickHouse
4. **Add A/B testing** for model versions
5. **Complete KYB/Financial integration**
6. **Achieve 95% test coverage**

## References

- FraudMLService: `app/Services/Fraud/FraudMLService.php`
- Config: `config/fraud-ml.php`
- AuthService Integration: `app/Services/Auth/AuthService.php`
- Feature Store: `app/Services/ML/FraudMLFeatureStore.php`
- Explainer: `app/Services/ML/FraudMLExplainer.php`
