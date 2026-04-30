# Bot Protection System

Многослойная система детекции и блокировки бот-трафика для CatVRF.

## Обзор

Система защищает CatVRF от автоматизированных атак, скрейпинга и злонамеренных ботов, используя многослойную архитектуру детекции.

### Архитектура

```
┌─────────────────────────────────────────────────────────────┐
│ Layer 0: Edge Protection (Cloudflare Bot Management)      │
│ - WAF правила                                              │
│ - Rate limiting                                            │
│ - IP reputation                                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Laravel Middleware + Honeypots                    │
│ - User-Agent анализ                                         │
│ - Honeypot поля в формах                                   │
│ - JavaScript challenges                                    │
│ - Turnstile CAPTCHA                                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: Behavioral Biometrics + AI Detection              │
│ - Keystroke patterns                                       │
│ - Mouse movement                                           │
│ - Session behavior                                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: FraudControl + VpnDetection + InsiderThreat       │
│ - VPN/Proxy/Tor detection                                  │
│ - Fraud scoring                                            │
│ - Velocity checks                                          │
└─────────────────────────────────────────────────────────────┘
```

## Уровни риска

| Уровень | Описание | Защита |
|---------|----------|--------|
| **LOW** | Низкий риск | Разрешить с логированием |
| **MEDIUM** | Средний риск | Turnstile + rate limiting |
| **HIGH** | Высокий риск | Блокировка + Cooldown 24ч + уведомление owner |
| **CRITICAL** | Критический риск | Постоянная блокировка + Cooldown 7 дней + уведомление всех |

## Компоненты

### 1. BotDetectionService

Основной сервис для детекции ботов.

```php
use App\Services\Security\BotDetectionService;
use App\DTO\Security\BotDetectionResult;

$service = app(BotDetectionService::class);
$result = $service->detect($request, $user);

if ($result->requiresProtection()) {
    $service->applyProtection($request, $result, $user);
}
```

### 2. BotProtectionMiddleware

Middleware для критических маршрутов.

```php
// routes/api.php
Route::middleware(['bot-protection'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/clients/search', [ClientController::class, 'search']);
});
```

### 3. Honeypot компоненты

Скрытые поля в формах для детекции ботов.

```blade
<x-honeypot-field />
```

### 4. UserDevice интеграция

Отслеживание bot-risk на уровне устройств.

```php
$device->updateBotDetection($riskLevel, $detectionData);

if ($device->shouldAutoDisable()) {
    $device->revoke();
}
```

## Конфигурация

Файл: `config/bot-protection.php`

### Основные настройки

```php
return [
    'enabled' => env('BOT_PROTECTION_ENABLED', true),
    
    'thresholds' => [
        'medium' => 0.4,
        'high' => 0.65,
        'critical' => 0.85,
    ],
    
    'layers' => [
        'edge_protection' => true,
        'middleware_checks' => true,
        'behavioral_analysis' => true,
        'vpn_detection' => true,
        'fraud_integration' => true,
    ],
];
```

### Whitelist/Blacklist

```php
'whitelist' => [
    'user_agents' => ['Googlebot', 'Bingbot'],
    'ip_ranges' => ['66.249.64.0/19'],
],

'blacklist' => [
    'user_agents' => ['scrapy', 'curl', 'python-requests'],
    'patterns' => ['/bot/i', '/crawler/i'],
],
```

### Специальные правила для российских территорий

```php
'russian_territories' => [
    'enabled' => true,
    'territories' => ['Crimea', 'Sevastopol', 'DPR', 'LPR', ...],
    'vpn_auto_high_risk' => true,
    'scraping_critical_risk' => true,
],
```

## ClickHouse логирование

Таблица: `bot_detection_events`

```sql
CREATE TABLE bot_detection_events (
    id UUID,
    correlation_id String,
    is_bot Bool,
    risk_level Enum8('low' = 1, 'medium' = 2, 'high' = 3, 'critical' = 4),
    confidence Float32,
    ip_address String,
    user_agent String,
    detection_sources Array(String),
    created_at DateTime,
    ...
) ENGINE = MergeTree()
ORDER BY (created_at, risk_level, ip_address);
```

### Запросы для аналитики

```sql
-- Количество детекций по дням
SELECT 
    toDate(created_at) as date,
    risk_level,
    count(*) as detections
FROM bot_detection_events
WHERE created_at >= now() - INTERVAL 7 DAY
GROUP BY date, risk_level;

-- Топ IP с критическим риском
SELECT 
    ip_address,
    count(*) as detection_count
FROM bot_detection_events
WHERE risk_level = 'critical'
AND created_at >= now() - INTERVAL 24 HOUR
GROUP BY ip_address
ORDER BY detection_count DESC
LIMIT 10;
```

## Тестирование

Запуск тестов:

```bash
php artisan test tests/Unit/Services/Security/BotDetectionServiceTest.php
```

Покрытие: ≥95%

## Интеграция с существующими сервисами

### BehavioralBiometricsService

```php
$analysis = $behavioralBiometrics->analyzeSignals($user, $signals, $sessionId);

if ($analysis['is_anomalous']) {
    // Bot detection signal
}
```

### VpnDetectionService

```php
$vpnResult = $vpnDetection->detect($request, $user);

if ($vpnResult->isVpn && !$vpnResult->isCorporateVpn) {
    // Bot detection signal
}
```

### FraudControlService

```php
$fraudCheck = $fraudControl->checkRequest($context);

if ($fraudCheck['should_block']) {
    // Bot detection signal
}
```

## Production Deployment

### 1. Cloudflare Bot Management

1. Включите **Bot Management** в Cloudflare dashboard
2. Настройте **WAF правила** для критических путей
3. Добавьте **Rate limiting** для API endpoints

Пример WAF правила:
```
(http.request.uri.path contains "/api/" and cf.threat_score > 50)
```

### 2. ClickHouse миграция

```bash
# Выполните миграцию
clickhouse-client --host <host> --user <user> --password <pass> \
    --query "$(cat database/clickhouse/migrations/2026_04_23_000001_create_bot_detection_events_table.sql)"
```

### 3. Environment variables

```env
BOT_PROTECTION_ENABLED=true
BOT_EDGE_PROTECTION_ENABLED=true
BOT_MIDDLEWARE_CHECKS_ENABLED=true
BOT_BEHAVIORAL_ANALYSIS_ENABLED=true
BOT_VPN_DETECTION_ENABLED=true
BOT_FRAUD_INTEGRATION_ENABLED=true

TURNSTILE_SITE_KEY=your_site_key
TURNSTILE_SECRET_KEY=your_secret_key

BOT_CACHE_ENABLED=true
BOT_CACHE_TTL=3600
BOT_LOG_CHANNEL=security
BOT_LOG_CLICKHOUSE=true
```

### 4. Мониторинг

Grafana dashboard: `docs/grafana/catvrf-bot-protection-dashboard.json`

Метрики для отслеживания:
- `bot_detections_total` - общее количество детекций
- `bot_detections_by_risk_level` - детекции по уровню риска
- `bot_protection_applied_total` - применённые меры защиты
- `bot_false_positives` - ложные срабатывания

## Troubleshooting

### Ложные срабатывания

1. Добавьте User-Agent в whitelist
2. Настройте исключения для доверенных IP
3. Отрегулируйте пороги в `config/bot-protection.php`

### Высокий latency

1. Включите кэширование: `BOT_CACHE_ENABLED=true`
2. Увеличьте TTL: `BOT_CACHE_TTL=3600`
3. Отключите тяжелые слои для non-critical routes

### Пропущенные боты

1. Проверьте whitelist/blacklist
2. Уменьшите пороги риска
3. Включите дополнительные слои детекции

## Безопасность

### Анонимизация IP

IP адреса анонимизируются перед логированием в ClickHouse:
```
192.168.1.100 → 192.168.1.0
```

### Хранение данных

- Данные хранятся в ClickHouse 90 дней (TTL)
- Логи в security channel: 30 дней
- User device records: неограниченно

### Compliance

Система соответствует:
- 152-ФЗ (персональные данные)
- ФЗ-323 (медицинская информация)
- Приказу Минздрава

## Контакты

- Security Team: security@catvrf.ru
- Documentation: https://github.com/nanolord126/CatVRF
