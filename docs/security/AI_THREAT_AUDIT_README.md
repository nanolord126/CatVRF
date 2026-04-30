# Руководство по аудиту ИИ-угроз БДУ ФСТЭК для CatVRF

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Назначение:** Подготовка к аудиту Роскомнадзора/ФСТЭК по ИИ-угрозам из БДУ

---

## Обзор

Этот документ описывает, как продемонстрировать интеграцию угроз ИИ из БДУ ФСТЭК в модель угроз CatVRF во время аудита Роскомнадзора или ФСТЭК.

**Ключевые моменты:**
- CatVRF мониторит 12 ИИ-угроз из нового раздела БДУ ФСТЭК (декабрь 2025)
- Автоматическая реакция на новые угрозы через ThreatModelService
- Обоснование УЗ-3 с учётом ИИ-угроз
- Соответствие Приказу ФСТЭК №21

---

## Быстрый старт для аудитора

### 1. Демонстрация синхронизации с БДУ

```bash
# Показать таблицу угроз в базе данных
php artisan tinker
>>> App\Models\FstecThreat::where('threat_type', 'ИИ')->count()
=> 12

>>> App\Models\FstecThreat::where('fstec_id', 'УБИ.ИИ-004')->first()
=> App\Models\FstecThreat {#...}
   fstec_id: "УБИ.ИИ-004"
   name: "Adversarial Attacks on AI Models"
   risk_level: "critical"
   ...
```

### 2. Демонстрация автоматической реакции

```bash
# Показать отчёт о соответствии
php artisan tinker
>>> app(App\Services\Security\ThreatModelService::class)->getComplianceReport()
=> [
     "total_threats_monitored" => 24,
     "ai_threats_monitored" => 12,
     "critical_threats" => 8,
     "bdu_reference" => "https://bdu.fstec.ru/ section \"Угрозы безопасности информации систем искусственного интеллекта\"",
     ...
   ]
```

### 3. Демонстрация risk adjustment

```bash
# Показать risk adjustment для behavioural_scoring
php artisan tinker
>>> app(App\Services\Security\FstecBduService::class)->getAIThreatRiskAdjustment('behavioral_scoring')
=> 0.15
```

---

## Чек-лист подготовки к аудиту

### Документация

- [ ] **Модель угроз ИИ** (`docs/security/AI_THREAT_MODEL_FSTEC_BDU.md`)
  - 12 ИИ-угроз из БДУ с детальным описанием
  - Релевантность для CatVRF
  - Меры защиты по Приказу №21
  - Обоснование УЗ-3

- [ ] **Акт определения УЗ-3** (`docs/compliance/UZ3_ACT_TEMPLATE.md`)
  - Обоснование выбора УЗ-3
  - Ссылки на раздел ИИ БДУ
  - Соответствие законодательству (152-ФЗ, ФЗ-323)
  - Подписи ответственных лиц

- [ ] **Реестр ИИ-систем** (приложение к акту УЗ-3)
  - Behavioral Biometrics Service
  - Voice Biometrics Service
  - Deepfake Detection Service
  - Fraud ML Service
  - Insider Threat ML Service
  - AI Diagnostics Service
  - AI Recommendations Service
  - AI Moderation Service

### Код

- [ ] **FstecBduService** (`app/Services/Security/FstecBduService.php`)
  - Синхронизация с БДУ ФСТЭК
  - 12 ИИ-угроз в `fetchAIThreatsFromBdu()`
  - Методы для получения ИИ-угроз
  - Risk adjustment для AI-систем

- [ ] **ThreatModelService** (`app/Services/Security/ThreatModelService.php`)
  - Анализ ландшафта угроз
  - Автоматическая реакция на новые ИИ-угрозы
  - Генерация рекомендаций
  - Отчёт для аудита

- [ ] **FstecThreat** (`app/Models/FstecThreat.php`)
  - Модель угроз БДУ
  - Scopes для фильтрации
  - Расчёт risk score

- [ ] **NewAIThreatDetected** (`app/Events/Security/NewAIThreatDetected.php`)
  - Событие для уведомления о новых ИИ-угрозах

### Тесты

- [ ] **AIThreatModelTest** (`tests/Feature/Security/AIThreatModelTest.php`)
  - 20 тестов для симуляции ИИ-угроз
  - Тесты синхронизации с БДУ
  - Тесты автоматической реакции
  - Тесты compliance report

---

## Скрипты для демонстрации

### Скрипт 1: Синхронизация угроз из БДУ

```bash
#!/bin/bash
# scripts/demonstrate-bdu-sync.sh

echo "=== Синхронизация угроз из БДУ ФСТЭК ==="
php artisan fstec:sync-threats --force

echo ""
echo "=== Статистика по угрозам ==="
php artisan tinker --execute="
\$ai = App\Models\FstecThreat::where('threat_type', 'ИИ')->count();
\$critical = App\Models\FstecThreat::where('threat_type', 'ИИ')->where('risk_level', 'critical')->count();
echo 'AI threats: ' . \$ai . PHP_EOL;
echo 'Critical AI threats: ' . \$critical . PHP_EOL;
"
```

### Скрипт 2: Анализ ландшафта угроз

```bash
#!/bin/bash
# scripts/demonstrate-threat-analysis.sh

echo "=== Анализ ландшафта угроз ==="
php artisan tinker --execute="
\$service = app(App\Services\Security\ThreatModelService::class);
\$report = \$service->analyzeThreatLandscape();
echo json_encode(\$report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"
```

### Скрипт 3: Compliance report для аудитора

```bash
#!/bin/bash
# scripts/generate-compliance-report.sh

echo "=== Compliance Report ==="
php artisan tinker --execute="
\$service = app(App\Services\Security\ThreatModelService::class);
\$report = \$service->getComplianceReport();
echo json_encode(\$report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"
```

---

## Ответы на типичные вопросы аудитора

### Q1: Как вы учитываете ИИ-угрозы из БДУ ФСТЭК?

**Ответ:**
Мы автоматически синхронизируемся с БДУ ФСТЭК через `FstecBduService`. В декабре 2025 ФСТЭК добавил отдельный раздел "Угрозы безопасности информации систем искусственного интеллекта". Мы интегрировали 12 угроз из этого раздела, включая 4 критических (data poisoning, adversarial attacks, model inversion, training data extraction).

**Доказательства:**
- `app/Services/Security/FstecBduService.php::fetchAIThreatsFromBdu()`
- `docs/security/AI_THREAT_MODEL_FSTEC_BDU.md` — таблица с 12 угрозами
- База данных: таблица `fstec_threats`, `threat_type = 'ИИ'`

---

### Q2: Как система реагирует на новые ИИ-угрозы?

**Ответ:**
`ThreatModelService` автоматически реагирует на новые критические ИИ-угрозы:
1. Повышает risk score для затронутых AI-систем
2. Добавляет рекомендации в очередь review
3. Уведомляет super-admin через событие `NewAIThreatDetected`
4. Обновляет глобальный risk adjustment для FraudControlService

**Демонстрация:**
```php
$service = app(ThreatModelService::class);
$result = $service->reactToNewAIThreats();
// Возвращает: critical_count, actions_taken
```

---

### Q3: Почему выбран УЗ-3?

**Ответ:**
УЗ-3 обоснован 4 критериями:
1. **Биометрические ПДн** — behavioral biometrics, voice biometrics, liveness detection
2. **Использование ИИ** — AI diagnostics, behavioral auth, fraud ML
3. **ИИ-угрозы из БДУ** — 12 угроз, 4 критических (ссылка на раздел ИИ БДУ)
4. **Объём субъектов** — > 10,000 пользователей

**Доказательства:**
- `docs/compliance/UZ3_ACT_TEMPLATE.md` — акт определения УЗ-3
- `docs/security/AI_THREAT_MODEL_FSTEC_BDU.md` — модель угроз

---

### Q4: Какие меры защиты реализованы для ИИ-угроз?

**Ответ:**
Мы реализовали меры по Приказу ФСТЭК №21 для каждой ИИ-угрозы:

| Угроза | Меры | Статус |
|--------|------|--------|
| Adversarial Attacks | М.2.6, М.2.7, М.6.1 | 🟡 Частично |
| Data Poisoning | М.4.3, М.6.1, М.6.4 | 🟢 Реализовано |
| Prompt Injection | М.2.7, М.3.3, М.6.1 | 🟡 Частично |
| Model Inversion | М.4.3, М.6.1, М.6.4 | 🟢 Реализовано |

**Дополнительные меры:**
- Differential privacy для защиты от model inversion
- Adversarial training для behavioral models
- Ensemble models для liveness detection
- Guardrails и moderation layer для LLM

---

### Q5: Как доказать, что система мониторит БДУ?

**Ответ:**
1. **Логи синхронизации:** `storage/logs/laravel.log` — поиск "FSTEC BDU sync"
2. **База данных:** таблица `fstec_threats` с полями `source_updated_at`, `synced_at`
3. **Отчёт:** `ThreatModelService::getComplianceReport()` — поле `bdu_sync_date`
4. **Тесты:** `tests/Feature/Security/AIThreatModelTest.php` — 20 тестов

**Демонстрация:**
```bash
grep "FSTEC BDU sync" storage/logs/laravel.log
```

---

## Подготовка окружения для аудита

### 1. Запуск миграций

```bash
php artisan migrate
```

### 2. Синхронизация угроз из БДУ

```bash
php artisan fstec:sync-threats --force
```

### 3. Запуск тестов

```bash
php artisan test --filter AIThreatModelTest
```

### 4. Генерация compliance report

```bash
php artisan tinker --execute="
\$service = app(App\Services\Security\ThreatModelService::class);
\$report = \$service->getComplianceReport();
file_put_contents('compliance_report.json', json_encode(\$report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
"
```

---

## Артефакты для предоставления аудитору

### Обязательные

1. **Модель угроз ИИ** (`docs/security/AI_THREAT_MODEL_FSTEC_BDU.md`)
2. **Акт определения УЗ-3** (`docs/compliance/UZ3_ACT_TEMPLATE.md`)
3. **Compliance report** (сгенерированный JSON)
4. **Логи синхронизации БДУ** (`storage/logs/laravel.log`)

### Опциональные

1. **Результаты тестов** (`phpunit.xml` или вывод `php artisan test`)
2. **Реестр ИИ-систем** (таблица в документации)
3. **Схема обработки ПДн с ИИ** (если существует)

---

## Контактные лица

| Роль | ФИО | Email | Телефон |
|------|-----|-------|---------|
| CISO | | | |
| DPO | | | |
| Security Engineer | | | |

---

## Ссылки

- [БДУ ФСТЭК](https://bdu.fstec.ru/)
- [Приказ ФСТЭК №21](https://fstec.ru/ru/document/789848)
- [152-ФЗ](http://www.consultant.ru/document/cons_doc_LAW_149753/)
- [ФЗ-323](http://www.consultant.ru/document/cons_doc_LAW_121895/)
- [Модель угроз ИИ](docs/security/AI_THREAT_MODEL_FSTEC_BDU.md)
- [Акт УЗ-3](docs/compliance/UZ3_ACT_TEMPLATE.md)

---

## Часто встречающиеся проблемы

### Проблема: Тесты не проходят

**Решение:**
```bash
# Очистить кэш
php artisan cache:clear
php artisan config:clear

# Пересоздать базу данных
php artisan migrate:fresh --seed

# Запустить тесты снова
php artisan test --filter AIThreatModelTest
```

### Проблема: Нет угроз в базе данных

**Решение:**
```bash
# Принудительная синхронизация
php artisan fstec:sync-threats --force

# Проверить результат
php artisan tinker --execute="echo App\Models\FstecThreat::count();"
```

### Проблема: Compliance report пустой

**Решение:**
```bash
# Сначала синхронизировать угрозы
php artisan fstec:sync-threats --force

# Затем сгенерировать отчёт
php artisan tinker --execute="
\$service = app(App\Services\Security\ThreatModelService::class);
\$report = \$service->getComplianceReport();
print_r(\$report);
"
```

---

## Заключение

CatVRF полностью интегрировал ИИ-угрозы из БДУ ФСТЭК в модель угроз ИСПДн. Система автоматически мониторит 12 ИИ-угроз, реагирует на новые критические угрозы и генерирует отчёты для аудита Роскомнадзора/ФСТЭК.

**Ключевые достижения:**
- ✅ 12 ИИ-угроз из БДУ ФСТЭК (декабрь 2025)
- ✅ Автоматическая синхронизация и реакция
- ✅ Обоснование УЗ-3 с учётом ИИ-угроз
- ✅ Соответствие Приказу ФСТЭК №21
- ✅ 20 тестов для валидации
- ✅ Готовая документация для аудита
