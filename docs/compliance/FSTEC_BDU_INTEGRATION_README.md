# Интеграция БДУ ФСТЭК в модель угроз ИСПДн CatVRF

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Проект:** CatVRF — AI-powered Healthcare Marketplace

---

## Обзор

Этот документ описывает интеграцию БДУ ФСТЭК (Банк данных угроз безопасности информации) в модель угроз ИСПДн проекта CatVRF. Интеграция позволяет:

- Автоматически актуализировать модель угроз из официального источника ФСТЭК
- Обосновать класс защиты УЗ-3 для Роскомнадзора/ФСТЭК
- Автоматически реагировать на новые угрозы в коде (FraudControl, InsiderThreatService, Behavioral Biometrics)
- Демонстрировать соблюдение требований Приказа №21 ФСТЭК

---

## Структура

### Документы

- `ISPDN_THREAT_MODEL_FSTEC_BDU.md` — Полная модель угроз ИСПДн с примерами из БДУ
- `UZ3_CLASS_DETERMINATION_ACT_TEMPLATE.md` — Шаблон акта определения класса защиты УЗ-3
- `FSTEC_BDU_INTEGRATION_README.md` — Этот документ (инструкция по использованию)

### Код

- `app/Services/Security/FstecBduService.php` — Сервис синхронизации с БДУ ФСТЭК
- `app/Models/FstecThreat.php` — Модель угроз из БДУ
- `database/migrations/2026_04_23_000005_create_fstec_threats_table.php` — Миграция таблицы угроз

### Тесты

- `tests/Unit/Services/Security/FstecBduServiceTest.php` — Unit тесты для FstecBduService

---

## Быстрый старт

### 1. Запуск миграции

```bash
php artisan migrate
```

### 2. Синхронизация угроз из БДУ

```bash
# Через Artisan команду (рекомендуется)
php artisan fstec:sync-threats

# Или программно
use App\Services\Security\FstecBduService;

$service = app(FstecBduService::class);
$result = $service->syncThreats(force: true);
```

### 3. Получение релевантных угроз

```php
use App\Services\Security\FstecBduService;

$service = app(FstecBduService::class);

// Все релевантные угрозы
$threats = $service->getRelevantThreats();

// Только критические угрозы
$criticalThreats = $service->getRelevantThreats('critical');

// Угрозы, требующие немедленного действия
$urgentThreats = $service->getThreatsRequiringImmediateAction();
```

### 4. Интеграция с FraudControlService

```php
use App\Services\Security\FstecBduService;
use App\Services\Fraud\FraudControlService;

$fstecService = app(FstecBduService::class);
$fraudControl = app(FraudControlService::class);

// Получить risk adjustment для конкретной угрозы
$adjustment = $fstecService->getThreatRiskAdjustment('УБИ.131');

// Обновить fraud score пользователя
$fraudControl->updateUserFraudScore($userId, $adjustment);
```

---

## Архитектура

### FstecBduService

Сервис обеспечивает:

1. **Синхронизация с БДУ ФСТЭК**
   - Периодический парсинг БДУ (ежемесячно)
   - Определение релевантности угроз для CatVRF
   - Автоматическое обновление risk score

2. **Интеграция с существующими сервисами**
   - FraudControlService — повышение risk score при новых угрозах
   - InsiderThreatService — добавление паттернов угроз
   - Behavioral Biometrics — усиление liveness detection

3. **API для работы с угрозами**
   - Получение релевантных угроз
   - Фильтрация по уровню риска
   - Проверка на новые биометрические угрозы

### Модель FstecThreat

Поля модели:

- `fstec_id` — ID угрозы в БДУ (УБИ.131, BDU:2026-00650)
- `name` — Название угрозы
- `description` — Описание
- `threat_type` — Тип угрозы (НСД, ПО, Утечка, Уязвимость)
- `threat_class` — Класс (external, internal, infrastructure)
- `is_relevant` — Релевантность для CatVRF
- `relevance_reason` — Обоснование релевантности
- `probability` — Вероятность (very_low ... very_high)
- `impact` — Влияние (very_low ... very_high)
- `risk_level` — Уровень риска (low, medium, high, critical)
- `affected_systems` — Затронутые системы (JSON)
- `mitigation_measures` — Меры защиты по Приказу №21 (JSON)
- `is_mitigated` — Статус mitigated

---

## Примеры угроз из БДУ

### Внешние атаки (хакеры)

| ID БДУ | Описание | CVSS | Риск | Меры защиты |
|--------|----------|------|------|-------------|
| УБИ.131 | Credential stuffing, token replay | 8.5 | Критический | М.2.1, М.2.4, М.2.5 |
| BDU:2026-00650 | SQL-инъекция в веб-интерфейсе | 9.8 | Критический | М.3.1, М.3.2, М.3.3 |
| BDU:2026-00120 | Уязвимость MultipartFile.move() | 8.2 | Критический | М.3.6, М.3.7 |

### Внутренние угрозы (инсайдеры)

| ID БДУ | Описание | CVSS | Риск | Меры защиты |
|--------|----------|------|------|-------------|
| УБИ.006 | Массовый сбор информации (scraping) | 8.5 | Критический | М.6.1, М.6.2, М.6.3 |
| УБИ.065 | Доступ через облачный провайдер | 9.0 | Критический | М.4.1, М.4.2, М.6.4 |

### Угрозы биометрии (CatVRF-специфичные)

| ID | Описание | CVSS | Риск | Меры защиты |
|----|----------|------|------|-------------|
| CATVRF-001 | Replay-атака на Passkey/WebAuthn | 8.5 | Критический | М.2.4, М.2.5, М.2.6 |
| CATVRF-002 | Deepfake / injection в liveness | 9.0 | Критический | М.2.6, М.2.7 |

---

## Чек-лист для аудита Роскомнадзор/ФСТЭК

### Демонстрация использования БДУ

- [ ] Документ модели угроз содержит ссылки на БДУ (ID угрозы/уязвимости)
- [ ] Таблица fstec_threats содержит 10-15 реальных примеров из БДУ
- [ ] Есть механизм синхронизации с БДУ (FstecBduService)
- [ ] Угрозы регулярно актуализируются (ежемесячно)
- [ ] Есть журнал изменений угроз (audit log через timestamps)
- [ ] Меры защиты соответствуют Приказу №21

### Демонстрация защиты от конкретных угроз

- [ ] УБИ.131 — Passkey/WebAuthn + Behavioral Biometrics + Continuous Auth
- [ ] BDU:2026-00650 — Отказ от raw queries + TenantIsolationMiddleware + ORM
- [ ] BDU:2026-00120 — spatie/medialibrary + antivirus scan + TenantAwareUrlGenerator
- [ ] УБИ.006 — InsiderThreatService + behavioral biometrics + masked data + JIT access
- [ ] УБИ.225 — Tenant isolation + filesystem bootstrapper + custom TenantAwareUrlGenerator

### Демонстрация мониторинга и реагирования

- [ ] SIEMService интегрирован с SIEM
- [ ] AutomatedIncidentResponseService автоматизирует реагирование
- [ ] Prometheus metrics собираются
- [ ] OpenTelemetry tracing включен
- [ ] Audit логи маскируют чувствительные данные

### Демонстрация автоматической реакции на новые угрозы

- [ ] FstecBduService повышает risk score в FraudControlService при новых угрозах
- [ ] InsiderThreatService добавляет паттерны угроз
- [ ] Behavioral Biometrics усиливает liveness detection при новых биометрических угрозах
- [ ] Security team получает уведомления о критических угрозах
- [ ] Создаются задачи на внедрение мер по Приказу №21

---

## Как продемонстрировать использование БДУ инспектору

### Шаг 1: Показать документ модели угроз

```bash
# Откройте документ
cat docs/compliance/ISPDN_THREAT_MODEL_FSTEC_BDU.md
```

**Ключевые моменты:**
- Показать таблицу с ID угроз из БДУ (УБИ.131, BDU:2026-00650 и т.д.)
- Показать релевантность для CatVRF
- Показать меры защиты по Приказу №21

### Шаг 2: Показать базу данных угроз

```bash
# Подключитесь к базе данных
php artisan tinker

# Покажите угрозы
App\Models\FstecThreat::relevant()->get();

# Покажите критические угрозы
App\Models\FstecThreat::relevant()->highRisk()->get();
```

**Ключевые моменты:**
- Показать, что угрозы синхронизированы из БДУ
- Показать дату последней синхронизации (synced_at)
- Показать релевантность для CatVRF (is_relevant)

### Шаг 3: Показать механизм синхронизации

```bash
# Запустите синхронизацию
php artisan fstec:sync-threats

# Покажите результат
```

**Ключевые моменты:**
- Показать, что сервис работает
- Показать логи синхронизации
- Показать, что новые угрозы автоматически добавляются

### Шаг 4: Показать интеграцию с FraudControlService

```bash
php artisan tinker

# Получите risk adjustment для угрозы
$service = app(App\Services\Security\FstecBduService::class);
$service->getThreatRiskAdjustment('УБИ.131');
```

**Ключевые моменты:**
- Показать, что risk score автоматически обновляется
- Показать интеграцию с существующими сервисами безопасности

### Шаг 5: Показать акт определения УЗ-3

```bash
# Откройте акт
cat docs/compliance/UZ3_CLASS_DETERMINATION_ACT_TEMPLATE.md
```

**Ключевые моменты:**
- Показать обоснование класса защиты УЗ-3
- Показать ссылки на БДУ
- Показать меры защиты по Приказу №21

---

## Конфигурация

### Настройка интервала синхронизации

В `config/security.php` (создайте файл):

```php
return [
    'fstec_bdu' => [
        'sync_interval_hours' => 24, // Интервал синхронизации
        'api_url' => env('FSTEC_BDU_API_URL', 'https://bdu.fstec.ru/api/v1'),
        'auto_sync' => env('FSTEC_BDU_AUTO_SYNC', true),
    ],
];
```

### Настройка уведомлений

Для уведомлений о новых критических угрозах используйте существующий NotificationService:

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);
$notificationService->notifySecurityTeam($threat);
```

---

## Тестирование

### Запуск unit тестов

```bash
php artisan test --filter FstecBduServiceTest
```

### Покрытие тестами

Тесты покрывают:
- Синхронизацию угроз (создание, обновление)
- Пропуск синхронизации при недавнем запуске
- Принудительную синхронизацию
- Расчет релевантности угроз
- Расчет уровня риска
- Получение risk adjustment
- Фильтрацию угроз по уровню риска
- Получение угроз, требующих немедленного действия
- Проверку на новые биометрические угрозы
- Обновление глобального risk score

---

## Мониторинг

### Prometheus Metrics

Добавьте метрики для мониторинга:

```php
use Prometheus\CollectorRegistry;

$registry = app(CollectorRegistry::class);

// Количество релевантных угроз
$relevantThreatsCounter = $registry->getOrRegisterCounter(
    'fstec',
    'relevant_threats_total',
    'Total number of relevant threats from BDU'
);

// Количество критических угроз
$criticalThreatsCounter = $registry->getOrRegisterCounter(
    'fstec',
    'critical_threats_total',
    'Total number of critical threats from BDU'
);

// Время последней синхронизации
$lastSyncGauge = $registry->getOrRegisterGauge(
    'fstec',
    'last_sync_timestamp',
    'Timestamp of last BDU sync'
);
```

### Логи

Логи синхронизации записываются в Laravel logs:

```bash
tail -f storage/logs/laravel.log | grep FSTEC
```

---

## Troubleshooting

### Проблема: Синхронизация не работает

**Решение:**
1. Проверьте подключение к интернету
2. Проверьте настройки кэша
3. Запустите с флагом `force: true`

```bash
php artisan fstec:sync-threats --force
```

### Проблема: Угрозы не помечаются как релевантные

**Решение:**
1. Проверьте список `affected_systems` в коде FstecBduService
2. Добавьте нужные системы в массив `$catvrfSystems`

### Проблема: Risk adjustment не обновляется

**Решение:**
1. Проверьте, что FraudControlService инжектирован в FstecBduService
2. Проверьте настройки кэша
3. Проверьте логи на наличие ошибок

---

## Ссылки

- **БДУ ФСТЭК:** https://bdu.fstec.ru/
- **Приказ №21 ФСТЭК:** https://fstec.ru/ru/documents/695
- **152-ФЗ:** http://www.consultant.ru/document/cons_doc_LAW_149753/
- **ФЗ-323:** http://www.consultant.ru/document/cons_doc_LAW_121895/
- **Постановление №1119:** http://government.ru/docs/23925/

---

## Контакты

- **Security Officer:** security@catvrf.ru
- **DSO (Должностное лицо по защите информации):** dso@catvrf.ru
- **ФСТЭК:** https://fstec.ru/

---

## Изменения

| Версия | Дата | Описание |
|--------|------|----------|
| 1.0 | 23.04.2026 | Первая версия интеграции БДУ ФСТЭК |
