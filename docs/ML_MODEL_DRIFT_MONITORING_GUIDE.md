# ML Model Drift Monitoring Guide

**Версия:** 2026.04.23  
**Статус:** Production Ready  
**Соответствие:** 152-ФЗ, ФЗ-323, ФСТЭК

---

## Обзор

Система мониторинга дрейфа ML-моделей CatVRF обеспечивает непрерывный контроль качества фрод-моделей в реальном времени. Система отслеживает три типа дрейфа:

1. **Data Drift (Feature Drift)** — изменения в распределении входных фич
2. **Concept Drift (Label Drift)** — ухудшение качества модели (accuracy decay, F1 decay)
3. **Model Drift (Prediction Drift)** — сдвиг распределения предсказаний модели

### Ключевые возможности

- **Real-time мониторинг** (< 100ms overhead) при каждом предсказании
- **Daily анализ** — полное сравнение 24h vs 7-day baseline
- **Explainability** — SHAP values для интерпретации дрейфа
- **Auto-retrain** — автоматический ретрейн при критическом дрейфе (с human approval)
- **Alerting** — Telegram/Slack/Email уведомления
- **Dashboard** — Filament дашборд для data scientists
- **Compliance** — соответствует 152-ФЗ, ФЗ-323 (анонимизация данных)

---

## Архитектура

### Компоненты

```
┌─────────────────────────────────────────────────────────────┐
│                     ModelDriftService                        │
│  - monitorRealTime() — реальный мониторинг                 │
│  - dailyFullAnalysis() — ежедневный анализ                 │
└─────────────────────────────────────────────────────────────┘
                            ↓
        ┌───────────────────┼───────────────────┐
        ↓                   ↓                   ↓
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│   Redis       │   │  ClickHouse   │   │   Queue       │
│  (Baseline)   │   │  (History)    │   │  (Jobs)       │
└───────────────┘   └───────────────┘   └───────────────┘
        ↓                   ↓                   ↓
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│  Feature      │   │   Drift       │   │   Auto        │
│  Store        │   │   Reports     │   │   Retrain     │
└───────────────┘   └───────────────┘   └───────────────┘
```

### Мониторируемые модели

- **Behavioral Biometrics** — keystroke timing, mouse velocity, session patterns
- **Fraud ML Ensemble** — XGBoost + Isolation Forest + LSTM
- **Insider Threat** — access patterns, data volume anomalies
- **VPN/Proxy Detection** — residential proxy ratio, ASN changes

---

## Конфигурация

### Основные настройки (`config/fraud-ml.php`)

```php
'drift' => [
    'enabled' => env('FRAUD_ML_DRIFT_ENABLED', true),
    'real_time_enabled' => env('FRAUD_ML_DRIFT_REALTIME_ENABLED', true),
    'daily_analysis_enabled' => env('FRAUD_ML_DRIFT_DAILY_ENABLED', true),

    // Real-time мониторинг
    'real_time' => [
        'sample_window_size' => 10000,  // Последние 10K предсказаний
        'check_interval_seconds' => 300,  // Проверка каждые 5 минут
        'overhead_threshold_ms' => 100,  // Max 100ms overhead
    ],

    // Daily анализ
    'daily' => [
        'reference_window_days' => 7,  // 7 дней baseline
        'current_window_days' => 1,  // Последние 24h
        'scheduled_time' => '02:00',  // Запуск в 2 AM UTC
    ],

    // Пороги (production standards from Ozon/Amazon)
    'thresholds' => [
        'data_drift' => [
            'psi_warning' => 0.1,      // PSI warning threshold
            'psi_critical' => 0.25,    // PSI critical threshold
            'ks_alpha_warning' => 0.05, // KS-test p-value warning
            'ks_alpha_critical' => 0.01, // KS-test p-value critical
            'js_warning' => 0.1,       // JS divergence warning
            'js_critical' => 0.3,     // JS divergence critical
        ],
        'concept_drift' => [
            'accuracy_decay_warning' => 0.05,  // 5% decay warning
            'accuracy_decay_critical' => 0.08,  // 8% decay critical
            'f1_decay_warning' => 0.05,       // 5% F1 decay warning
            'f1_decay_critical' => 0.08,       // 8% F1 decay critical
        ],
        'model_drift' => [
            'prediction_distribution_shift' => 0.15,
            'score_variance_change' => 0.2,
        ],
    ],

    // Alerting
    'alerting' => [
        'enabled' => true,
        'channels' => [
            'telegram' => env('FRAUD_ML_DRIFT_TELEGRAM_ENABLED', false),
            'slack' => env('FRAUD_ML_DRIFT_SLACK_ENABLED', false),
            'email' => env('FRAUD_ML_DRIFT_EMAIL_ENABLED', true),
        ],
        'recipients' => [
            'data_scientists' => env('FRAUD_ML_DRIFT_DS_EMAILS', ''),
            'ml_engineers' => env('FRAUD_ML_DRIFT_MLE_EMAILS', ''),
            'security_team' => env('FRAUD_ML_DRIFT_SEC_EMAILS', ''),
        ],
        'cooldown_minutes' => 60,  // Не alert чаще 1 раза в час
    ],

    // Auto-retrain
    'auto_retrain' => [
        'enabled' => env('FRAUD_ML_DRIFT_AUTORETRAIN_ENABLED', false),
        'require_human_approval' => true,  // Всегда требуется approval для production
        'min_labeled_samples' => 1000,
        'canary_percentage' => 10,  // 10% canary deployment
        'canary_duration_hours' => 24,
    ],
],
```

---

## Развертывание

### 1. Настройка ClickHouse схемы

```bash
# Применить миграцию ClickHouse для drift monitoring
clickhouse-client --multiquery < database/clickhouse/migrations/create_fraud_drift_tables.sql
```

### 2. Настройка scheduled job

Добавить в `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Daily drift analysis в 2 AM UTC
    $schedule->job(new \App\Jobs\ML\DailyDriftAnalysisJob())
        ->dailyAt('02:00')
        ->timezone('UTC')
        ->withoutOverlapping();
}
```

### 3. Настройка очередей

```bash
# Создать очереди для drift monitoring
php artisan queue:table
php artisan migrate

# Запустить worker для drift monitoring
php artisan queue:work --queue=ml-drift,ml-retrain --sleep=3 --tries=3
```

### 4. Переменные окружения

Добавить в `.env`:

```env
# Drift Monitoring
FRAUD_ML_DRIFT_ENABLED=true
FRAUD_ML_DRIFT_REALTIME_ENABLED=true
FRAUD_ML_DRIFT_DAILY_ENABLED=true

# Alerting
FRAUD_ML_DRIFT_EMAIL_ENABLED=true
FRAUD_ML_DRIFT_DS_EMAILS=data-science@example.com
FRAUD_ML_DRIFT_MLE_EMAILS=ml-engineering@example.com
FRAUD_ML_DRIFT_SEC_EMAILS=security@example.com

# Auto-retrain (disabled по умолчанию для safety)
FRAUD_ML_DRIFT_AUTORETRAIN_ENABLED=false
```

---

## Использование

### Real-time мониторинг

Автоматически включен в `FraudMLService::scoreOperation()`:

```php
$score = $fraudMLService->scoreOperation($dto);
// Drift monitoring происходит автоматически
```

### Manual запуск daily анализа

```php
use App\Jobs\ML\DailyDriftAnalysisJob;

// Для всех моделей
DailyDriftAnalysisJob::dispatch();

// Для конкретной модели
DailyDriftAnalysisJob::dispatch('fraud_ml_ensemble', 'medical');
```

### Request retrain (при критическом дрейфе)

```php
use App\Jobs\ML\AutoRetrainJob;

AutoRetrainJob::dispatch(
    modelType: 'fraud_ml_ensemble',
    verticalCode: 'medical',
    driftReportId: $reportId,
    requireApproval: true  // Всегда true в production
);
```

---

## Filament Dashboard

Доступ к дашборду drift monitoring:

1. Перейти в Filament Admin
2. Меню: ML & Analytics → Drift Monitoring
3. Возможности:
   - Обзор всех моделей и их drift status
   - Детальный анализ по модели
   - Trigger manual analysis
   - Request retrain
   - Просмотр SHAP explainability

**Доступ:** Super-admin, Data Scientists, ML Engineers

---

## Метрики дрейфа

### PSI (Population Stability Index)

- **< 0.1** — OK (нет дрейфа)
- **0.1 - 0.25** — WARNING (умеренный дрейф)
- **> 0.25** — CRITICAL (сильный дрейф)

### KS-test (Kolmogorov-Smirnov)

- **p-value > 0.05** — OK (нет значимых различий)
- **p-value ≤ 0.05** — DRIFT DETECTED (значимые различия)

### JS Divergence (Jensen-Shannon)

- **< 0.1** — OK
- **0.1 - 0.3** — WARNING
- **> 0.3** — CRITICAL

### Accuracy Decay

- **< 5%** — OK
- **5% - 8%** — WARNING
- **> 8%** — CRITICAL

---

## Troubleshooting

### Drift не детектируется

**Проблема:** Система не детектирует дрейф, хотя он есть.

**Решения:**
1. Проверить, что `FRAUD_ML_DRIFT_ENABLED=true`
2. Убедиться, что baseline установлен (первые 1000 предсказаний)
3. Проверить пороги в `config/fraud-ml.php`
4. Проверить логи: `tail -f storage/logs/laravel.log | grep drift`

### Слишком много false positives

**Проблема:** Много предупреждений о дрейфе, когда его нет.

**Решения:**
1. Увеличить пороги PSI/JS в конфиге
2. Увеличить `reference_window_days` до 14 дней
3. Отключить auto-retrain: `FRAUD_ML_DRIFT_AUTORETRAIN_ENABLED=false`
4. Настроить alert cooldown: `alerting.cooldown_minutes=120`

### High overhead при предсказаниях

**Проблема:** Drift мониторинг замедляет предсказания.

**Решения:**
1. Отключить real-time мониторинг: `FRAUD_ML_DRIFT_REALTIME_ENABLED=false`
2. Увеличить `check_interval_seconds` до 600 (10 минут)
3. Уменьшить `sample_window_size` до 5000
4. Использовать async drift monitoring через queue

---

## Best Practices

### Production

1. **Всегда require human approval** для auto-retrain в production
2. **Использовать canary deployment** (10% traffic) перед полным rollout
3. **Мониторить SHAP values** для понимания причин дрейфа
4. **Обновлять baseline** только при успешном retrain
5. **Хранить drift reports** в ClickHouse для аудита (152-ФЗ)

### Data Science

1. **Анализировать drift по вертикалям** отдельно (medical ≠ payment)
2. **Проверять seasonal patterns** перед триггером retrain
3. **Использовать labeled data** для concept drift detection
4. **A/B тестировать** новые модели перед production
5. **Документировать** причины дрейфа и действия

### Security

1. **Анонимизировать PII** перед отправкой в external ML services
2. **Маскировать чувствительные фичи** в логах
3. **Ограничить доступ** к drift dashboard (role-based)
4. **Аудитировать** все retrain операции
5. **Retention:** хранить drift reports 365 дней (compliance)

---

## Compliance

### 152-ФЗ (Personal Data)

- Все фичи анонимизированы перед drift analysis
- PII не хранится в drift reports
- Audit trail для всех drift alerts

### ФЗ-323 (Medical Data)

- Медицинские фичи маскируются
- Нет PII в external ML calls
- Separate baseline для medical vertical

### ФСТЭК (Security)

- RBAC для drift dashboard
- Audit logging всех retrain operations
- Encryption at rest для drift reports

---

## Мониторинг и алерты

### Prometheus Metrics

Система экспортирует следующие метрики:

```
fraud_ml_feature_drift_psi_score{vertical_code, model_type}
fraud_ml_feature_drift_ks_score{vertical_code, model_type}
fraud_ml_drifted_features_count{vertical_code}
fraud_ml_overall_drift_detected{vertical_code}
fraud_ml_drift_check_timestamp{vertical_code}
```

### Grafana Dashboard

Используйте файл `docs/grafana/catvrf-ml-drift-monitor.json` для импорта дашборда в Grafana.

---

## Testing

```bash
# Unit tests
./vendor/bin/pest tests/Unit/Services/ML/ModelDriftServiceTest.php

# Feature tests
./vendor/bin/pest tests/Feature/ML/DriftMonitoringTest.php

# Load test drift monitoring
k6 run k6/crash-test-drift-monitoring.js
```

---

## Дополнительные ресурсы

- [Fraud ML Production Guide](FRAUD_ML_PRODUCTION_GUIDE.md)
- [Database Security Fortress](DATABASE_SECURITY_FORTRESS_2026.md)
- [ClickHouse Schema](../../database/clickhouse/migrations/create_fraud_drift_tables.sql)
- [Filament Resource](../../Filament/Resources/DriftMonitoringResource.php)

---

## Changelog

**2026.04.23**
- ✅ Initial implementation
- ✅ Real-time drift monitoring
- ✅ Daily drift analysis
- ✅ SHAP explainability
- ✅ Auto-retrain pipeline
- ✅ Filament dashboard
- ✅ ClickHouse schema
- ✅ Pest tests (95%+ coverage)

---

**Поддержка:** ML Engineering Team  
**Escalation:** Security Team (для критического дрейфа)
