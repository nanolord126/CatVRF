# Feature Drift Detection - Vertical Integration Guide

**Дата:** 17 апреля 2026  
**Версия:** 1.0  
**Автор:** Senior Production Architect

## Обзор

Документация по интеграции Feature Drift Detection в 64 бизнес-вертикали CatVRF. Drift detection защищает ML модели от деградации из-за изменения распределения фич во времени.

## Архитектура

### Компоненты

1. **FeatureDriftDetectorService** (`app/Services/ML/FeatureDriftDetectorService.php`)
   - PSI (Population Stability Index) расчёт с percentile-based binning
   - KS-test (Kolmogorov-Smirnov) для непрерывных фич
   - Per-vertical thresholds (строже для Medical/Healthcare по 152-ФЗ)

2. **FeatureDriftMetricsService** (`app/Services/ML/FeatureDriftMetricsService.php`)
   - Prometheus metrics export
   - Grafana JSON endpoint
   - Feature drift history tracking

3. **HasFeatureDriftDetection** Trait (`app/Services/ML/Traits/HasFeatureDriftDetection.php`)
   - Reusable trait для всех вертикалей
   - Runtime drift checking
   - Reference distribution storage

4. **AbstractAIConstructorService** (`app/Services/ML/AbstractAIConstructorService.php`)
   - Базовый класс для AI Constructor Services
   - Встроенный drift detection
   - Common AI service functionality

## Пороги Drift Detection

### Default Thresholds
- **PSI < 0.10:** No significant drift (зелёный)
- **0.10 ≤ PSI < 0.25:** Minor drift (жёлтый, мониторинг)
- **PSI ≥ 0.25:** Significant drift (красный, shadow mode + alert)

### Medical/Healthcare Thresholds (152-ФЗ Compliance)
- **PSI < 0.08:** No significant drift
- **0.08 ≤ PSI < 0.15:** Minor drift
- **PSI ≥ 0.15:** Significant drift (ACTION REQUIRED)

## Способы интеграции

### Способ 1: Использование Trait (Рекомендуется)

```php
<?php

namespace App\Domains\Beauty\Services\AI;

use App\Services\ML\Traits\HasFeatureDriftDetection;
use App\Services\ML\FeatureDriftDetectorService;
use App\Services\ML\FeatureDriftMetricsService;

final class BeautyAIConstructorService
{
    use HasFeatureDriftDetection;

    public function __construct(
        private readonly FeatureDriftDetectorService $driftDetector,
        private readonly FeatureDriftMetricsService $driftMetrics,
    ) {
        $this->verticalCode = 'beauty';
        $this->initializeDriftDetection();
    }

    public function generateRecommendation(string $prompt): array
    {
        // Check drift before AI inference
        $this->checkFeatureDrift('prompt_length', strlen($prompt));
        
        // Your AI logic
        return $this->callAI($prompt);
    }
}
```

### Способ 2: Наследование от AbstractAIConstructorService

```php
<?php

namespace App\Domains\Food\Services\AI;

use App\Services\ML\AbstractAIConstructorService;

final class FoodAIConstructorService extends AbstractAIConstructorService
{
    protected string $verticalCode = 'food';

    public function recommendRestaurants(array $preferences): array
    {
        // Check drift for key features
        $this->checkFeatureDrift('preference_complexity', count($preferences));
        
        // Your AI logic
        return [];
    }
}
```

### Способ 3: Автоматическое применение через Artisan команду

```bash
# Dry-run (предварительный просмотр)
php artisan drift:detect:apply-verticals --dry-run

# Применить ко всем вертикалям
php artisan drift:detect:apply-verticals

# Применить к конкретной вертикали
php artisan drift:detect:apply-verticals --vertical=medical
```

## Конфигурация

### Monitored Features по вертикалям

Файл: `config/fraud.php`

Каждая вертикаль имеет свой список monitored features:

```php
'drift_detection_monitored_features' => [
    'medical' => [
        'ai_diagnosis_frequency',
        'health_score_spike',
        'emergency_event_rate',
        // ...
    ],
    'beauty' => [
        'service_type_distribution',
        'booking_frequency',
        'price_range_distribution',
        // ...
    ],
    // ... 62 другие вертикали
],
```

### Per-vertical Thresholds

```php
'drift_thresholds' => [
    'default' => [
        'psi_critical' => 0.25,
        'psi_moderate' => 0.1,
        'ks_critical' => 0.1,
        'ks_moderate' => 0.05,
    ],
    'medical' => [
        'psi_critical' => 0.15,
        'psi_moderate' => 0.08,
        'ks_critical' => 0.07,
        'ks_moderate' => 0.03,
    ],
    'healthcare' => [
        'psi_critical' => 0.15,
        'psi_moderate' => 0.08,
        'ks_critical' => 0.07,
        'ks_moderate' => 0.03,
    ],
],
```

## Runtime Drift Checking

### Проверка одиночной фичи

```php
// В вашем AI методе
$driftResult = $this->checkFeatureDrift('prompt_length', strlen($prompt));

if ($driftResult['drift_detected']) {
    Log::warning('Feature drift detected', [
        'feature' => 'prompt_length',
        'drift_score' => $driftResult['drift_score'],
    ]);
    
    // Можно переключиться на shadow mode
    if ($this->shouldUseShadowMode()) {
        return $this->callShadowAI($prompt);
    }
}
```

### Проверка нескольких фич

```php
$features = [
    'prompt_length' => strlen($prompt),
    'user_language' => $this->detectLanguage($prompt),
    'request_type' => $this->getRequestType($prompt),
];

$driftReport = $this->checkMultipleFeaturesDrift($features);

if ($driftReport['overall_drift_detected']) {
    // Alert team
    event(new SignificantDriftDetected($driftReport));
}
```

## Reference Distribution Storage

### После обучения модели

```php
// В ML retraining job
$features = [
    'prompt_length' => $this->getRecentPromptLengths(),
    'user_language' => $this->getRecentLanguages(),
    // ... другие фичи
];

$this->storeReferenceDistributions($modelVersion, $features);
```

### Сбор данных для reference distributions

```php
private function getRecentPromptLengths(): array
{
    // Query last 30 days of data from ClickHouse
    $data = DB::connection('clickhouse')
        ->table('ai_inference_logs')
        ->where('vertical', $this->verticalCode)
        ->where('created_at', '>=', now()->subDays(30))
        ->pluck('prompt_length')
        ->toArray();
    
    return $data;
}
```

## Monitoring

### Prometheus Metrics

```bash
# Scrape drift metrics
curl http://localhost/metrics/fraud/drift

# Пример вывода:
# HELP fraud_ml_feature_drift_psi_score PSI score for feature drift detection
# TYPE fraud_ml_feature_drift_psi_score gauge
fra_ml_feature_drift_psi_score{vertical_code="medical"} 0.045
fra_ml_feature_drift_psi_score{vertical_code="beauty"} 0.123

# HELP fraud_ml_drifted_features_count Number of features with critical drift
# TYPE fraud_ml_drifted_features_count gauge
fra_ml_drifted_features_count{vertical_code="medical"} 0
fra_ml_drifted_features_count{vertical_code="beauty"} 2
```

### Grafana Dashboard JSON

```bash
curl http://localhost/api/v1/ml/fraud/drift/metrics
```

### ClickHouse Queries

```sql
-- Get recent drift alerts
SELECT * 
FROM feature_drift_alerts 
WHERE created_at >= now() - INTERVAL 7 DAY
ORDER BY created_at DESC;

-- Get per-feature drift results
SELECT 
    feature_name,
    vertical_code,
    drift_score,
    drift_level
FROM feature_drift_detection_results
WHERE created_at >= now() - INTERVAL 24 HOUR
  AND drift_level IN ('critical', 'moderate');
```

## Scheduled Drift Checks

### Создание Scheduled Job

```php
// app/Console/Kernel.php
$schedule->command('drift:detect:check-verticals')
    ->daily()
    ->at('03:00')
    ->onSuccess(function () {
        Log::info('Daily drift check completed');
    });
```

### Команда для scheduled check

```bash
php artisan drift:detect:check-verticals
```

## Shadow Mode Integration

### Проверка shadow mode

```php
if ($this->shouldUseShadowMode()) {
    // Use shadow model
    return $this->callShadowAI($prompt);
} else {
    // Use active model
    return $this->callActiveAI($prompt);
}
```

### Включение shadow mode при drift

```php
// В drift detection logic
if ($driftReport['overall_drift_detected']) {
    cache(['beauty_shadow_mode_enabled' => true], now()->addHours(24));
    
    Log::critical('Shadow mode enabled due to drift', [
        'vertical' => $this->verticalCode,
        'drift_report' => $driftReport,
    ]);
}
```

## Alerting

### Email Alert

```php
// Event listener
class SignificantDriftDetectedListener
{
    public function handle(SignificantDriftDetected $event): void
    {
        Mail::to('ml-team@catvrf.com')
            ->send(new DriftAlertMail($event->driftReport));
    }
}
```

### Slack Alert

```php
// В drift detection logic
if ($driftReport['overall_drift_detected']) {
    Http::post(config('services.slack.webhook'), [
        'text' => '⚠️ Feature drift detected in ' . $this->verticalCode,
        'attachments' => [
            [
                'color' => 'danger',
                'fields' => [
                    ['title' => 'Vertical', 'value' => $this->verticalCode],
                    ['title' => 'Max PSI', 'value' => $driftReport['max_psi']],
                    ['title' => 'Drifted Features', 'value' => count($driftReport['drifted_features'])],
                ],
            ],
        ],
    ]);
}
```

## Тестирование

### Unit Test для drift detection

```php
public function test_drift_detection_in_ai_service(): void
{
    $service = new BeautyAIConstructorService(
        $this->driftDetector,
        $this->driftMetrics
    );

    // Store reference distribution
    $service->storeReferenceDistributions('v1.0.0', [
        'prompt_length' => [100, 150, 200, 120, 180],
    ]);

    // Check drift with outlier value
    $result = $service->checkFeatureDrift('prompt_length', 1000);

    $this->assertTrue($result['drift_detected']);
    $this->assertGreaterThan(1.0, $result['drift_score']);
}
```

## Rollback Procedure

### Откат модели при drift

```bash
# Manual rollback
php artisan ml:rollback --vertical=medical --version=v1.0.0

# Отключение shadow mode
php artisan drift:detect:disable-shadow-mode --vertical=medical
```

### Автоматический rollback

```php
// В drift detection logic
if ($driftReport['max_psi'] > 0.5) {
    $this->rollbackModel($previousVersion);
    
    Log::critical('Model auto-rolled back due to critical drift', [
        'vertical' => $this->verticalCode,
        'psi' => $driftReport['max_psi'],
    ]);
}
```

## Performance Considerations

### Кэширование

- Reference distributions кэшируются в Redis (TTL 24h)
- Drift reports кэшируются (TTL 1h)
- Используйте `Cache::tags()` для инвалидации

### Асинхронные проверки

```php
// Выносим drift check в queue
dispatch(new CheckFeatureDriftJob(
    $this->verticalCode,
    $featureName,
    $featureValue
));
```

### Batch processing

```php
// Проверяйте drift батчами, не для каждого запроса
if ($this->shouldCheckDrift()) { // e.g., 1% sampling
    $this->checkFeatureDrift($featureName, $value);
}
```

## Troubleshooting

### Drift detection всегда возвращает false

**Проблема:** Reference distribution не сохранена

**Решение:**
```php
// Проверьте сохранение после обучения
$this->storeReferenceDistributions($modelVersion, $features);
```

### PSI всегда высокий

**Проблема:** Слишком мало данных для reliable PSI

**Решение:**
```php
// Увеличьте минимальное количество сэмплов
'min_samples_for_drift_check' => 1000,
```

### Shadow mode не включается

**Проблема:** Thresholds слишком строгие

**Решение:**
```php
// Настройте thresholds для вашей вертикали
'drift_thresholds' => [
    'your_vertical' => [
        'psi_critical' => 0.35, // увеличьте
    ],
],
```

## Checklist для каждой вертикали

- [ ] Добавить `HasFeatureDriftDetection` trait или наследовать от `AbstractAIConstructorService`
- [ ] Настроить `$verticalCode`
- [ ] Добавить drift checks в ключевые AI методы
- [ ] Настроить monitored features в `config/fraud.php`
- [ ] Настроить per-vertical thresholds (если нужно)
- [ ] Сохранять reference distributions после обучения модели
- [ ] Добавить scheduled drift check (ежедневно/еженедельно)
- [ ] Настроить alerting (email/Slack)
- [ ] Добавить unit tests
- [ ] Настроить Grafana dashboard
- [ ] Документировать drift handling procedure

## Критические вертикали (Priority 1)

1. **Medical** — 152-ФЗ compliance, health impact
2. **Healthcare** — 152-ФЗ compliance, health impact
3. **Payment** — финансовые транзакции
4. **Payout** — финансовые выплаты

Эти вертикали должны иметь:
- Строгие thresholds (PSI < 0.15)
- Автоматический shadow mode при drift
- Немедленные alerts
- 24/7 monitoring

## Дополнительные ресурсы

- PSI Calculation: `app/Services/ML/FeatureDriftDetectorService.php`
- Metrics Export: `app/Services/ML/FeatureDriftMetricsService.php`
- Example Integration: `app/Services/ML/Examples/BeautyAIConstructorWithDriftDetectionExample.php`
- Configuration: `config/fraud.php`
- ClickHouse Schema: `database/clickhouse/migrations/2024_01_01_000006_create_feature_drift_reference_table.php`

## Поддержка

Для вопросов по интеграции:
- Технический lead: ml-team@catvrf.com
- On-call: #ml-ops Slack channel
- Emergency: +7 (XXX) XXX-XX-XX

---

**Версия:** 1.0  
**Последнее обновление:** 17 апреля 2026  
**Статус:** Production Ready
