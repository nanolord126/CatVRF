# Feature Drift Detection Integration Guide

**Версия:** 2026.04.17  
**Статус:** Production Ready

## Обзор

Feature Drift Detection интегрирован во все 64 вертикали CatVRF через:
- **`FeatureDriftDetectorService`** — core сервис с PSI, KS-test, JS divergence
- **`HasFeatureDriftDetection`** Trait — для интеграции в AI сервисы
- **`config/feature_drift.php`** — конфигурация фич и порогов для всех вертикалей
- **`SignificantFeatureDriftDetected`** событие — автоматические алерты при HIGH severity

## Архитектура

```
┌─────────────────────────────────────────────────────────────┐
│                    AI Service (Vertical)                    │
│                  (Medical, Food, Beauty, ...)               │
├─────────────────────────────────────────────────────────────┤
│  use HasFeatureDriftDetection                               │
│  - checkAllFeaturesDrift($data)                            │
│  - checkFeatureDrift($name, $expected, $actual)            │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│           FeatureDriftDetectorService                        │
│  - calculatePSI()                                          │
│  - calculateKS()                                           │
│  - calculateJSDivergence()                                 │
│  - detectDriftForFeature()                                 │
│  - detectAllFeatures()                                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
         ┌─────────────┼─────────────┐
         ▼             ▼             ▼
    PSI (40%)    KS-test (35%)   JS (25%)
         │             │             │
         └─────────────┼─────────────┘
                       ▼
         CombinedDriftScore (0-1)
                       │
         ┌─────────────┴─────────────┐
         ▼                           ▼
    LOW (≤0.3)              HIGH (>0.7)
         │                           │
         └───────────┬───────────────┘
                     ▼
    SignificantFeatureDriftDetected Event
                     │
                     ▼
    HandleSignificantFeatureDrift Listener
    - Shadow mode
    - Alerts (Slack/Email)
    - Cache invalidation
    - Prometheus metrics
```

## Быстрая интеграция

### Шаг 1: Добавить Trait в AI сервис

```php
<?php declare(strict_types=1);

namespace App\Domains\Medical\Services\AI;

use App\Services\ML\Traits\HasFeatureDriftDetection;
use App\Domains\FraudML\Services\FeatureDriftDetectorService;

final readonly class MedicalAIConstructorService
{
    use HasFeatureDriftDetection;

    public function __construct(
        private readonly FeatureDriftDetectorService $driftDetector,
    ) {
        $this->verticalCode = 'medical';
        $this->initializeDriftDetection();
    }

    public function generateDiagnosis(array $patientData): array
    {
        // Extract features for drift detection
        $currentFeatures = $this->extractCurrentFeatures($patientData);
        
        // Check drift automatically
        $driftResult = $this->checkAllFeaturesDrift($currentFeatures);
        
        // Generate AI diagnosis
        $diagnosis = $this->aiModel->predict($patientData);
        
        return $diagnosis;
    }

    private function extractCurrentFeatures(array $data): array
    {
        return [
            'ai_diagnosis_frequency' => [
                'expected' => $this->getReferenceDistribution('ai_diagnosis_frequency'),
                'actual' => $this->getCurrentDiagnosisFrequency(),
            ],
            'health_score' => [
                'expected' => $this->getReferenceDistribution('health_score'),
                'actual' => $this->getCurrentHealthScores(),
            ],
            // ... другие фичи
        ];
    }
}
```

### Шаг 2: Настроить фичи в конфиге

```php
// config/feature_drift.php
return [
    'verticals' => [
        'medical' => [
            'features' => [
                'ai_diagnosis_frequency',
                'health_score',
                'emergency_event_rate',
                'quota_usage_ratio',
            ],
            'thresholds' => [
                'psi_critical' => 0.2, // Строгие пороги для Medical
                'psi_moderate' => 0.08,
                'ks_alpha' => 0.03,
            ],
            'enabled' => true,
        ],
    ],
];
```

### Шаг 3: Запустить scheduled drift check

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Ежедневный drift check для всех вертикалей
    $schedule->call(function () {
        $verticals = Config::get('feature_drift.verticals', []);
        
        foreach ($verticals as $vertical => $config) {
            if (!$config['enabled'] ?? false) {
                continue;
            }

            $service = app("App\\Domains\\{$vertical}\\Services\\AI\\{$vertical}AIConstructorService");
            $currentData = $service->extractCurrentDailyData();
            $service->performScheduledDriftCheck($currentData);
        }
    })->dailyAt('03:00');
}
```

## Примеры интеграции по категориям

### Medical Vertical (Критичная)

```php
final readonly class MedicalAIConstructorService
{
    use HasFeatureDriftDetection;

    public function __construct(
        private readonly FeatureDriftDetectorService $driftDetector,
    ) {
        $this->verticalCode = 'medical';
        $this->initializeDriftDetection();
    }

    private function extractCurrentFeatures(array $data): array
    {
        return [
            'ai_diagnosis_frequency' => [
                'expected' => $this->getReferenceDistribution('ai_diagnosis_frequency'),
                'actual' => array_column($data, 'ai_diagnosis_count'),
            ],
            'health_score' => [
                'expected' => $this->getReferenceDistribution('health_score'),
                'actual' => array_column($data, 'health_score'),
            ],
            'emergency_event_rate' => [
                'expected' => $this->getReferenceDistribution('emergency_event_rate'),
                'actual' => array_column($data, 'emergency_rate'),
            ],
        ];
    }
}
```

### Food Vertical (Высокая нагрузка)

```php
final readonly class FoodAIConstructorService
{
    use HasFeatureDriftDetection;

    public function __construct(
        private readonly FeatureDriftDetectorService $driftDetector,
    ) {
        $this->verticalCode = 'food';
        $this->initializeDriftDetection();
    }

    private function extractCurrentFeatures(array $data): array
    {
        return [
            'order_frequency' => [
                'expected' => $this->getReferenceDistribution('order_frequency'),
                'actual' => array_column($data, 'orders_per_hour'),
            ],
            'average_order_value' => [
                'expected' => $this->getReferenceDistribution('average_order_value'),
                'actual' => array_column($data, 'order_value'),
            ],
            'delivery_time_minutes' => [
                'expected' => $this->getReferenceDistribution('delivery_time_minutes'),
                'actual' => array_column($data, 'delivery_time'),
            ],
        ];
    }
}
```

### Taxi Vertical (Real-time)

```php
final readonly class TaxiAIConstructorService
{
    use HasFeatureDriftDetection;

    public function __construct(
        private readonly FeatureDriftDetectorService $driftDetector,
    ) {
        $this->verticalCode = 'taxi';
        $this->initializeDriftDetection();
    }

    private function extractCurrentFeatures(array $data): array
    {
        return [
            'ride_request_frequency' => [
                'expected' => $this->getReferenceDistribution('ride_request_frequency'),
                'actual' => array_column($data, 'requests_per_minute'),
            ],
            'average_ride_distance_km' => [
                'expected' => $this->getReferenceDistribution('average_ride_distance_km'),
                'actual' => array_column($data, 'distance_km'),
            ],
            'wait_time_minutes' => [
                'expected' => $this->getReferenceDistribution('wait_time_minutes'),
                'actual' => array_column($data, 'wait_time'),
            ],
        ];
    }
}
```

## Prometheus Metrics

Все метрики экспортируются в лог-формат для scraping:

```
# Feature-specific metrics
ml_feature_drift_psi{feature="ai_diagnosis_frequency",vertical="medical"} 0.05
ml_feature_drift_ks{feature="ai_diagnosis_frequency",vertical="medical"} 0.03
ml_feature_drift_js{feature="ai_diagnosis_frequency",vertical="medical"} 0.04
ml_feature_drift_combined{feature="ai_diagnosis_frequency",vertical="medical"} 0.45

# Drift detection events
ml_feature_drift_detected_total{feature="health_score",vertical="medical",severity="HIGH"} 1

# Vertical-level metrics
ml_vertical_drift_score{vertical="medical"} 0.52
ml_vertical_drift_detected_total{vertical="medical"} 1
```

## Alerting

При HIGH severity автоматически:
1. **Логируется** в audit канал
2. **Отправляется алерт** (Slack/Email/PagerDuty)
3. **Инвалидируется кэш** reference distributions
4. **Переводится модель** в shadow mode
5. **Записывается метрика** в Prometheus

## Конфигурация порогов

```bash
# .env
FEATURE_DRIFT_ENABLED=true
FEATURE_DRIFT_CACHE_TTL=168  # 7 days
FEATURE_DRIFT_AUTO_ALERT=true
```

## Пороги по умолчанию

| Метрика | LOW | MEDIUM | HIGH |
|---------|-----|--------|------|
| PSI | < 0.1 | 0.1-0.25 | > 0.25 |
| KS p-value | > 0.05 | 0.01-0.05 | ≤ 0.01 |
| JS Divergence | < 0.1 | 0.1-0.3 | > 0.3 |
| Combined Score | ≤ 0.3 | 0.3-0.7 | > 0.7 |

## Vertical-specific thresholds

Medical вертикаль имеет более строгие пороги:
- PSI critical: 0.2 (вместо 0.25)
- KS alpha: 0.03 (вместо 0.05)

## Тестирование

```php
// tests/Unit/Domains/Medical/MedicalAIConstructorServiceTest.php

public function test_drift_detection_integration(): void
{
    $service = app(MedicalAIConstructorService::class);
    
    $currentData = [
        'ai_diagnosis_count' => [1, 2, 3, 4, 5],
        'health_score' => [4.5, 4.8, 5.0, 5.2, 5.5],
    ];
    
    $features = $service->extractCurrentFeatures($currentData);
    $result = $service->checkAllFeaturesDrift($features);
    
    $this->assertArrayHasKey('summary', $result);
    $this->assertArrayHasKey('overall_drift_detected', $result['summary']);
}
```

## Troubleshooting

### Drift detection не работает

1. Проверьте `FEATURE_DRIFT_ENABLED=true` в .env
2. Убедитесь, что Trait добавлен в сервис
3. Проверьте, что `initializeDriftDetection()` вызван в конструкторе
4. Проверьте конфиг в `config/feature_drift.php`

### False positives

1. Отрегулируйте thresholds в конфиге
2. Увеличьте cache TTL для более стабильных reference distributions
3. Используйте vertical-specific thresholds для критичных вертикалей

### Redis cache issues

1. Проверьте Redis соединение
2. Используйте `invalidateReferenceCache()` при изменениях бизнес-логики
3. Увеличьте `FEATURE_DRIFT_CACHE_TTL` если нужно

## Production Checklist

- [ ] Добавлен Trait во все AI сервисы (64 вертикали)
- [ ] Настроены critical features для каждой вертикали
- [ ] Настроены vertical-specific thresholds
- [ ] Scheduled drift check добавлен в Kernel
- [ ] Prometheus scraper настроен
- [ ] Alerting интегрирован (Slack/Email)
- [ ] Unit tests написаны
- [ ] Load testing проведено
- [ ] Documentation обновлена

## Поддержка

Для вопросов и проблем:
- GitHub Issues: https://github.com/nanolord126/CatVRF/issues
- Documentation: `/docs/FEATURE_DRIFT_INTEGRATION.md`
- Config: `config/feature_drift.php`
