# CatVRF - Архитектура проекта

**Дата:** 2026-04-30  
**Версия:** 2026 Q1  
**Статус:** Active Development (Migration in Progress)

---

## Обзор

CatVRF — мультивертикальный AI-маркетплейс с 33+ бизнес-вертикалями. Проект находится в процессе миграции от плоской архитектуры к модульной структуре с Clean Architecture + DDD.

**Текущее состояние:**
- Production Readiness: 4/10 (снижено из-за критических проблем)
- 33+ модуля в `modules/` (новая архитектура)
- 90+ доменов в `app/Domains/` (legacy архитектура)
- Миграция в процессе (частично завершена)
- Тестовая инфраструктура: BLOCKED (PHP environment issue)

---

## Двойная архитектура (Текущее состояние)

### Legacy: `app/Domains/`

**Структура:** Плоская доменная архитектура

```
app/Domains/
├── Payment/           (112 файлов)
├── Wallet/            (35 файлов)
├── Auto/              (313 файлов)
├── Beauty/            (304 файлов)
├── Fashion/           (258 файлов)
├── Restaurant/        (194 файлов)
├── Taxi/              (183 файлов)
├── Travel/            (301 файлов)
├── RealEstate/        (240 файлов)
├── ... и еще 80+ доменов
└── Shared/            (2659 файлов - общий код)
```

**Проблемы:**
- Дублирование кода между вертикалями
- Отсутствие четкого разделения слоев
- Сложность в поддержке
- Нарушение DRY принципа

---

### Новая: `modules/` (Clean Architecture + DDD)

**Структура:** 9-слойная архитектура для каждого модуля

```
modules/
├── Payment/           (57 файлов) - Clean Architecture
├── Wallet/            (57 файлов) - Clean Architecture
├── Auto/              (26 файлов) - частично мигрирован
├── Fashion/           (47 файлов) - частично мигрирован
├── BeautyMasters/     (168 файлов) - Clean Architecture
├── BigData/           (86 файлов) - Clean Architecture
├── CatCRM/            (173 файлов) - Clean Architecture
├── Inventory/         (36 файлов) - Clean Architecture
├── Restaurant/        (193 файлов) - Clean Architecture
├── Taxi/              (49 файлов) - Clean Architecture
├── ... и еще 23+ модулей
```

**Структура модуля (9 слоев):**

```
modules/{Vertical}/
├── Domain/                    # Бизнес-логика (сущности, VOs, интерфейсы)
│   ├── Entities/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Events/
│   ├── Interfaces/            # Repository interfaces
│   ├── ValueObjects/
│   ├── DTOs/
│   └── Repositories/          # (пустой - только интерфейсы)
├── Application/               # Use cases и оркестрация
│   ├── Services/
│   ├── Jobs/
│   ├── Listeners/
│   └── Policies/
├── Infrastructure/            # Техническая реализация
│   ├── Models/                # Eloquent модели
│   ├── Repositories/          # Repository implementations
│   ├── Providers/
│   ├── Cache/
│   └── External/
├── Presentation/              # HTTP/GraphQL/WebSocket
│   ├── Http/Controllers/
│   ├── Http/Requests/
│   ├── Http/Resources/
│   └── Routes/
├── Filament/                  # Admin panel
│   └── Resources/
└── {Vertical}ServiceProvider.php
```

---

## Канонические сервисы

### PaymentService

**Каноническая версия:** `modules/Payment/Application/Services/PaymentService.php`

**Дубликаты (устаревшие):**
- app/Services/Payment/PaymentService.php (deprecated)
- app/Domains/Payment/Services/PaymentService.php (deprecated)

**Функциональность:**
- Smart routing платежей
- Escrow механизмы
- Split payments
- Payouts
- Outbox pattern для надежности

---

### FraudControlService

**Каноническая версия:** `app/Services/FraudControlService.php`

**Функциональность:**
- Hard rules проверки
- ML скоринг
- Integration с FraudMLService
- Вызов ПЕРЕД любой мутацией данных

---

### WalletService

**Каноническая версия:** `modules/Wallet/Services/WalletService.php`

**Дубликаты (устаревшие):**
- app/Services/Wallet/WalletService.php (deprecated)

**Функциональность:**
- Депозиты и снятия
- Баланс
- Transaction history
- Атомарные транзакции через DB::transaction()

---

## Multi-tenancy

### Реализация

**Библиотека:** `stancl/tenancy`

**Изоляция данных:**
- Global Eloquent scope на всех моделях
- `tenant_id` в каждой таблице
- Middleware для автоматического scope

**Правило (из MEMORY[user_global]):**
```php
// Каждая модель должна иметь:
protected static function booted()
{
    static::addGlobalScope('tenant', function ($query) {
        if (tenancy()->initialized) {
            $query->where('tenant_id', tenant('id'));
        }
    });
}
```

---

## AI Конструкторы

### Реализация

**Файлы:** `modules/AIConstructor/`, `app/Domains/AI/`

**Функциональность:**
- OpenAI Vision / GigaChat Vision
- Персонализированные рекомендации для каждой вертикали
- Асинхронные вызовы через Jobs (никогда в транзакциях)
- Circuit breaker + retries + moderation

**Правила (из MEMORY[user_global]):**
- LLM вызовы ТОЛЬКО асинхронно через очередь
- Никогда в синхронной транзакции
- Анонимизация медицинских данных перед внешними LLM
- Guardrails и moderation

---

## Антифрод система

### Компоненты

**FraudControlService** (app/Services/FraudControlService.php)
- Hard rules проверки
- Вызов ПЕРЕД любой мутацией

**FraudMLService** (app/Services/FraudMLService.php)
- ML скоринг
- Обучение на исторических данных

**Правила (из MEMORY[user_global]):**
- Fraud check — первое действие в любом публичном методе сервиса
- Блокировка транзакций при high risk

---

## BigData и Аналитика

### Стек

**Storage:** ClickHouse (OLAP)
**Streaming:** Redis Streams / Confluent Cloud
**Batch Processing:** PySpark
**Cache:** Redis

### Архитектура

```
Speed Layer:
  Laravel Events → Redis Streams → ClickHouse (real-time)

Batch Layer:
  PySpark Jobs → ClickHouse (daily aggregates)

Serving Layer:
  ClickHouse + Materialized Views
```

**Файлы:**
- `modules/BigData/` - модуль (Clean Architecture)
- `docs/BIGDATA_SETUP.md` - настройка
- `docs/BIGDATA_SRE_RUNBOOK.md` - SRE runbook

---

## CRM Система

### Реализация

**Модуль:** `modules/CatCRM/`

**Функциональность:**
- B2C клиенты
- B2B лиды и сделки
- Интеграция со складом (Warehouse)
- Интеграция с инвентарем (Inventory)
- Автоматическое назначение персонала

**Файлы:**
- `CRM_INTEGRATION_GUIDE.md` - гайд по интеграции
- `CRM_PRODUCTION_READINESS.md` - чеклист production readiness

---

## Compliance и Безопасность

### Федеральные законы (РФ)

**152-ФЗ:** Персональные данные
- Анонимизация PII
- Audit logging
- Roskomnadzor готовность

**ФЗ-115:** AML/KYC
- Проверка клиентов
- Risk scoring
- Rosfinmonitoring reporting

**ФЗ-161:** Национальная платежная система
- Transaction limits
- Платежные правила

**54-ФЗ:** Фискализация
- ККТ интеграция
- Фискальные чеки

**Файлы:** `docs/compliance/` (18 файлов)

---

## Тестовая инфраструктура

### Текущий статус

**СТАТУС:** 🔴 CRITICAL - СЛОМАНА

**Проблема:** Mockery Console issue блокирует все тесты

**Ошибка:**
```
BadMethodCallException: Received Mockery_1_Illuminate_Console_OutputStyle::askQuestion(), 
but no expectations were specified
```

**Причина:** Framework-level проблема в Pest Laravel plugin / Laravel test framework

**Решение:**
1. Сообщить разработчикам Pest Laravel plugin
2. Рассмотреть downgrade до Laravel 10.x или PHP 8.2
3. Переключиться на PHPUnit без Pest

**Файл:** `MOCKERY_CONSOLE_ISSUE_ANALYSIS.md`

---

## Технологический стек

### Backend

- **PHP:** 8.3+
- **Framework:** Laravel 11.x
- **Multi-tenancy:** stancl/tenancy
- **Queue:** Horizon + Redis
- **Cache:** Redis (with tags)
- **Database:** PostgreSQL 16
- **OLAP:** ClickHouse
- **Real-time:** Laravel Echo + WebSocket (Reverb)

### Frontend

- **Framework:** Vue.js 3
- **Admin Panel:** Filament 3.x
- **Styling:** Tailwind CSS
- **State:** Pinia

### DevOps

- **Containerization:** Docker
- **Orchestration:** Kubernetes (k8s/)
- **Monitoring:** Prometheus + Grafana
- **Tracing:** OpenTelemetry
- **CI/CD:** GitHub Actions

---

## Статус миграции

### Завершенные миграции

✅ **Payment** - modules/Payment (Clean Architecture)
- SmartRoutingService
- EscrowService
- SplitPaymentService
- PayoutService
- OutboxService

✅ **Wallet** - modules/Wallet (Clean Architecture)
- WalletService
- Repository interfaces
- Infrastructure models

✅ **BeautyMasters** - modules/BeautyMasters (Clean Architecture)
- AppointmentService
- Domain entities
- Complete 9-layer structure

✅ **BigData** - modules/BigData (Clean Architecture)
- BigDataMonitoringFacade
- BigDataFacade
- ClickHouse integration
- PySpark jobs

✅ **CatCRM** - modules/CatCRM (Clean Architecture)
- CustomerService
- LeadService
- DealService
- Integration listeners

✅ **Inventory** - modules/Inventory (Clean Architecture)
- FIFOShelfLifeService
- Domain entities (InventoryItem, InventoryBatch)
- Repository interfaces

### Частично мигрированные

⚠️ **Auto** - modules/Auto (26/55 файлов)
- Часть функциональности в modules/
- Остальное в app/Domains/Auto/

⚠️ **Fashion** - modules/Fashion (47/50 файлов)
- Почти полная миграция
- Несколько файлов в app/Domains/Fashion/

⚠️ **Restaurant** - modules/Restaurant (193 файла)
- Clean Architecture
- Но app/Domains/Restaurant еще существует

### Не мигрированные

🔴 **Остальные 20+ доменов** в app/Domains/
- Education
- Travel
- RealEstate
- Taxi
- И многие другие

---
испрл(н основе аналза от 30.04.2026)
## План завершения миграции
ипаленя12
### Фаза 1: Критические сервисы (2-3 недели)
**Испртестовую нфструктуру**- BLOCKER #1
1. ЗаИспррвить PHP окружение (oицnysl xson)
   - Или использовать Secker/WSL для запуска тестов
   - Попробовать dowcgrade Laravels/mst или переключиться на PHPUein/PaymentService.php
   - Слобщть ap issue tracker Pest LaravelDplugin/Payment/
   - Обновить все ссылки
** архитектуры** - BLOCKER#2
2. ЗаОпрееешить final tмrgцt (modulall)
   - Мигрировать Ptymn, t, Auo, Fashon
   - Удалить дубликаты из app/Servicet
/WalletService.php
### Фаза 2: Стабилизация (2-4 аедели)

3. **Испраить aсинтаксичеpки/ ошибки**
   - ✅ ГлобальныйDпоиmк не вaявиi ошибоn
   - Добавsть lint checks в CI/CD/Wallet/
   - Обновить все ссылки
4**Обнодокуент**
  - ✅ Обновить STRT_HERE.md
3. За✅ Создать/обноветь ARCHITECTURE.md
   - Ашхивить мигруциюре Autдокументы

5.**Дбаить Repository implementations**
   - Для всех моиурей
в  - Через Service Prсvвders
   - В Iшfraиtrяc ure layer файлов
   - Удалить app/Domains/Auto/
3Улучшеи
### Фаза 2: Активные вертикали (1-2 месяца)
6**Добавть интеацнные есты**
 - Кричски пути
   - API контракты
   - Зависит от Phese 1
aurant
6. **Усилить complixnc со стандартами**
   - PHPStan culom ruls8. Мигрировать RealEstate
 -Pr-ommit hooks
   -Codreviewchecklist
### Фаза 3: Legacy cleanup (1 месяц)
8**простBigDat setu**
   - ocker cpo
 -УMockpрaжSм для ev
11. Обновить CANONICAL_SERVICES.md

---

## Known Issues
**Полный анализ от 30.04.2026: найдено 12 проблем**

 (Critical)
### Критические
🔴 BLOCKED
   - Mcery Conso issuблокиует все тесты
   - Дполнительно: PHP 8.4.20 ез openssl расширения
   - Требуется исправние PHP окружения или использовние Docker/WSL

1. **Тестовая инфраструктур🔴 ACTIVE
   - а сломана** - framework параллельные структуры-level проблема
   - PaymentService: 9 копий в разных местах
   - FraudControlService: 6+ копий
   - WalletService: множественные реализации
   - Нарушение DRY принципа

2. **Дублирование кода** -  архитектурыapp/D🔴iACTIVE
   - Payment: частичнs  игрироваs (5 сервис m)
o  - Wallet: частичdоles/ (50 файлов)
  - Auto: 26/55 файлов в modules/
   - Fashion: 7/50файлов modul/
   - Beau:odus/BauyMsers существует, но app/Doma/Beautyещеес

###Сьзне(Hgh)

4. **Систакссчестиеpsшибки в iодrem - ✅ ИСПРАВЛЕНО
  entГлnбаs*ный поис* не-выя ьлооштбок еипа Lni::, Lo(:
   - Возможныр ошибфи были испсавлены ранее
 в Domain
5. **Конфликтующая докуме CatVRFнт - 🔴 ACTIVE
  ациН** - несколько версий архитектурыService::check() перед мутацией
   - Не все сервисы используют DB::transaction()
   - Не все сервисы интегрированы с Auditce
   - LLM вызовы могут быть в синхронных транзакциях
   - Требуется PHPStan ustom ruls для проверки

6. **Отсутствие Repository implementations** - 🔴 ACTIVE6. **Нарушения стандартов** - не все сервисы вызывают FraudControlService
   - Domain/Repositories/ пустой в некоторых модулях
   - Repository интерфейсы созданы, но implementations отсутствуют
   - Нарушение Clean Architecture

 (Medium)
### Средние
Устаревшая документация** - 🔴 ACTIVE
   - START_HERE.md держит информацию о НДС/фискализации (не актуально)
   - Конфликтующая документация межд
**ProductionуReadiness:** 4/10README.md, VERTICAL_REFACTORING_MIGRATION.md, CANONICAL_SERVICES.md
   - Требуется единый 
**Анализ:** Полный аудит проведен 30.04.2026, найдено 12 проблемARCHITECTURE.md (этот файл)

8. **🔴 BLOCKED
    Звиситот иправения тествой инфраструктуры
   - Невозожно верифицировть итеграцию между вертикалями
   - Выски рискрегрессй

9. **Коликтующая документция** - 🔴 ACTIVE
   - README.md опиывае 9-слойную ахитеу
   - VERTICAL_REFACTORING_MIGRATION.md описывает 16 super-verticals
   - CANONICAL_SERVICES.md описвает канонические сервисы
   - Требуется унификация

10. **Отсутствие интеграционн - 🔴 ACTIVE
 ы  х Тестов** - из-за сл(WSLмon Windows)
    - Требует анной и
    - Требует Confluent Cloud или Redis Streams
    - Требует Grafana Cloud
    - Сложная локальная разработка

### Малозначительные (Low)нфраструктуры

11. **BigData слодокументация жная setup***- 🔴 ACTIVE
    - 18 файлов в docs/compliance/152-fz/
    - Включая шаблоны * - требов
    - Информуетонный шум

12. **Отсутствие unified API documentationCl - 🔴 ACTIVE
   ic API endpoints разбросаны по разнымkrouteuse лам
    - Нет центра+из Pанной документации
    - Требуется OpenAPI/SwaggerySpark
9. **Избыточная compliance документация** - 18 файлов

---

## Рекомендации

### Для разработчиков

1. **Использовать только modules/** для нового кода
2. **Не создавать код в app/Domains/**
3. **Следовать Clean Architecture + DDD**
4. **Вызывать FraudControlService::check() перед мутациями**
5. **Использовать DB::transaction() для финансовых операций**
6. **Интегрировать AuditService через WithAuditLogging trait**

### Для DevOps

1. **Приоритет #1: исправить тестовую инфраструктуру**
2. **Настроить CI/CD для тестирования модулей**
3. **Мониторить миграцию через metrics**
4. **Подготовить rollback plan для каждого этапа миграции**

### Для менеджеров

1. **Выделить ресурсы на завершение миграции** (4-6 месяцев)
2. **Приоритет: критические сервисы (Payment, Wallet)**
3. **Планировать downtime для миграции больших доменов**
4. **Регулярные status update meetings**

---

## Ссылки на документацию

- [README.md](README.md) - Обзор проекта
- [CANONICAL_SERVICES.md](CANONICAL_SERVICES.md) - Канонические сервисы
- [VERTICAL_REFACTORING_MIGRATION.md](VERTICAL_REFACTORING_MIGRATION.md) - План миграции
- [MIGRATION_REPORT.md](MIGRATION_REPORT.md) - Отчет о миграции
- [START_HERE.md](START_HERE.md) - Главное меню
- [QUICKSTART.md](QUICKSTART.md) - Быстрый старт
- [MOCKERY_CONSOLE_ISSUE_ANALYSIS.md](MOCKERY_CONSOLE_ISSUE_ANALYSIS.md) - Проблема с тестами

---

**Версия:** 2026 Q1  
**Последнее обновление:** 2026-04-30  
**Автор:** CatVRF Team
