# CatVRF - Главное меню

**Версия**: 2026 Q2 | **Статус**: Active Development | **Дата**: 2026-04-30

---

## Добро пожаловать в CatVRF

**CatVRF** — мультивертикальный AI-маркетплейс с 33+ бизнес-вертикалями (красота, еда, медицина, такси, отели и др.). Каждая вертикаль имеет собственный AI-конструктор, B2C/B2B логику, антифрод-систему и кошелёк.

**Ключевые особенности:**
- Multi-tenancy с изоляцией данных
- Clean Architecture + DDD
- AI-конструкторы для каждой вертикали
- Антифрод + ML скоринг
- ClickHouse аналитика
- Реалтайм через WebSocket

### Выберите вашу роль

---

## Менеджер / Стейкхолдер

**Потребуется**: 15 минут

### Шаг 1: Обзор проекта (5 мин)

Прочитайте: **[README.md](README.md)**

**Вы узнаете:**
- Обзор архитектуры
- 33+ вертикали
- Технологический стек
- Production readiness: 5.5/10

### Шаг 2: План миграции (10 мин)

Прочитайте: **[MODULES_MIGRATION_PLAN.md](MODULES_MIGRATION_PLAN.md)**

**Вы узнаете:**
- Полный план миграции из app/Domains в modules/
- Приоритеты (8 недель, 4 фазы)
- 9-слойная Clean Architecture структура
- Стратегия для Payment, Wallet, Auto, Fashion

---

## Разработчик

**Потребуется**: 2 часа

### Шаг 1: Быстрый старт (10 мин)

Прочитайте: **[QUICKSTART.md](QUICKSTART.md)**

**Вы получите:**
- Быстрый запуск Beauty вертикали
- Настройка окружения
- Запуск тестов

### Шаг 2: Архитектура (30 мин)

Прочитайте: **[README.md](README.md)** → разделы "Архитектура" и "9-слойная архитектура домена"

**Вы узнаете:**
- Clean Architecture + DDD
- 9 слоев домена
- Global scope для tenant isolation
- Middleware pipeline

### Шаг 3: Канонические сервисы (15 мин)

Прочитайте: **[CANONICAL_SERVICES.md](CANONICAL_SERVICES.md)**

**Вы узнаете:**
- Какие сервисы являются каноническими
- Какие дубликаты нужно удалить
- Стратегия миграции

### Шаг 4: Рефакторинг вертикалей (30 мин)

Прочитайте: **[VERTICAL_REFACTORING_MIGRATION.md](VERTICAL_REFACTORING_MIGRATION.md)**

**Вы узнаете:**
- План миграции к 16 super-verticals
- Стратегия modules/ vs app/Domains/
- Timeline оценки

### Шаг 5: CRM интеграция (20 мин)

Прочитайте: **[CRM_INTEGRATION_GUIDE.md](CRM_INTEGRATION_GUIDE.md)**

**Вы узнаете:**
- B2B лиды и сделки
- Интеграция со складом и инвентарем
- Автоматическое назначение персонала

### Шаг 6: BigData (15 мин)

Прочитайте: **[docs/BIGDATA_SETUP.md](docs/BIGDATA_SETUP.md)**

**Вы узнаете:**
- Lambda Architecture
- ClickHouse + Redis Streams
- PySpark jobs

---

## DevOps / Системный администратор

**Потребуется**: 1 час

### Шаг 1: Быстрый старт (10 мин)

Прочитайте: **[QUICKSTART.md](QUICKSTART.md)**

### Шаг 2: BigData SRE Runbook (20 мин)

Прочитайте: **[docs/BIGDATA_SRE_RUNBOOK.md](docs/BIGDATA_SRE_RUNBOOK.md)**

**Вы узнаете:**
- Алерты и восстановление
- ClickHouse maintenance
- Kafka consumer lag
- Self-healing автоматика

### Шаг 3: Tenant Panel (15 мин)

Прочитайте: **[TENANT_PANEL_ARCHITECTURE.md](TENANT_PANEL_ARCHITECTURE.md)**

**Вы узнаете:**
- Единая панель тенанта
- CRM + Склад + Инвентарь
- B2B/B2C операции

### Шаг 4: Compliance (15 мин)

Прочитайте файлы в **[docs/compliance/](docs/compliance/)**

**Вы узнаете:**
- 152-ФЗ персональные данные
- ФЗ-115 AML/KYC
- ФЗ-161 национальная платежная система
- 54-ФЗ фискализация

---

## QA / Тестировщик

**Потребуется**: 30 минут

### Шаг 1: Тестовая инфраструктура (10 мин)

Статус: **ИСПРАВЛЕНО** ✅

PHP окружение настроено (OpenSSL включён, composer работает). Тесты готовы к запуску.

### Шаг 2: Инвентаризация аудит (20 мин)

Прочитайте: **[INVENTORY_AUDIT_REPORT.md](INVENTORY_AUDIT_REPORT.md)**

**Вы узнаете:**
- Какие проблемы найдены
- Какие исправлены
- Readiness score: 7.5/10

---

## Я потерялся - помогите

### Для быстрого понимания

1. **[README.md](README.md)** - Обзор проекта (10 мин)
2. **[QUICKSTART.md](QUICKSTART.md)** - Быстрый старт (5 мин)
3. **[CANONICAL_SERVICES.md](CANONICAL_SERVICES.md)** - Канонические сервисы (5 мин)

---

## Мобильная версия

**Шпаргалка** (2 мин):

```
Обзор:
→ README.md

Быстрый старт:
→ QUICKSTART.md

Архитектура:
→ README.md → "Архитектура"

Канонические сервисы:
→ CANONICAL_SERVICES.md

Миграции:
→ MIGRATION_REPORT.md
```

---
ц "
1. **[README.md](README.md)** (10 мин)
2. **[QUICKSTART.md](QUICKSTART.md)** (5 мин)
3. Готово!

### Сценарий: "Нужно понять архитектуру"

1. **[README.md](README.md)** → "Архитектура" (20 мин)
2. **[CANONICAL_SERVICES.md](CANONICAL_SERVICES.md)** (10 мин)
3. **[VERTICAL_REFACTORING_MIGRATION.md](VERTICAL_REFACTORING_MIGRATION.md)** (15 мин)

### Сценарий: "Хочу развернуть в production"

1. **[README.md](README.md)** → "Технологический стек" (10 мин)
2. **[MIGRATION_REPORT.md](MIGRATION_REPORT.md)** (10 мин)
3. **[docs/BIGDATA_SETUP.md](docs/BIGDATA_SETUP.md)** (15 мин)

### Сценарий: "Интеграция CRM"

1. **[CRM_INTEGRATION_GUIDE.md](CRM_INTEGRATION_GUIDE.md)** (20 мин)
2. **[TENANT_PANEL_ARCHITECTURE.md](TENANT_PANEL_ARCHITECTURE.md)** (10 мин)

---

## Часто задаваемые вопросы

### "Какие вертикали есть в проекте?"

**[README.md](README.md)** → раздел "33+ бизнес-вертикали"

### "Какая архитектура используется?"

**[README.md](README.md)** → раздел "Архитектура" и "9-слойная архитектура домена"

### "Какие сервисы канонические?"

**[CANONICAL_SERVICES.md](CANONICAL_SERVICES.md)**

### "Какой статус миграции?"

**[MIGRATION_REPORT.md](MIGRATION_REPORT.md)**

### "Почему не работают тесты?"

**[MOCKERY_CONSOLE_ISSUE_ANALYSIS.md](MOCKERY_CONSOLE_ISSUE_ANALYSIS.md)**

### "Как настроить BigData?"

**[docs/BIGDATA_SETUP.md](docs/BIGDATA_SETUP.md)**

### "Как работает tenant isolation?"

**[README.md](README.md)** → раздел "Правило глобального scope в каждой модели"

### "Как интегрировать CRM?"

**[CRM_INTEGRATION_GUIDE.md](CRM_INTEGRATION_GUIDE.md)**

---

## Текущие известные проблемы

**Полный анализ от 30.04.2026: найдено 12 проблем разного уровня критичности**

| Проблема | Уровень | Статус | Решение |
|----------|---------|--------|---------|
| Тестовая инфраструктура сломана (Mockery Console) | Critical | BLOCKED | Требуется исправление PHP окружения (openssl) |
| Дублирование кода (app/Domains vs modules/) | Critical | Active | Миграция в modules/, удаление дубликатов |
| Незавершенная миграция архитектуры | Critical | Active | Завершить миграцию Payment, Wallet, Auto, Fashion |
| Синтаксические ошибки в коде | High | Исправлено | Глобальный поиск не выявил ошибок |
| Обнавреутендартов CatVRF |выie|н еы  custoеrкlе исправленияes |
| Отсутствие Repository implementations | High | Active | Создать Eloquent реализации в Infrastructure |
| Устаревшая документация | Medium | Active | Обновить START_HERE.md, создать ARCHITECTURE.md |
| Отсутствие интеграционных тестов | Medium | Blocked | Зависит от исправления тестовой инфраструктуры |
| Конфликтующая документация | Medium | Active | Унифицировать ар✅рИСПРАВЛЕНО ны8.4с OSSL, compoerработает 
| BigData сложная setup | Medium | Active | Docker compose для локальной разработки |созMODULES_MIGRATION_PLAN.md
| Избыточная документация compliance | Low | ActivIn ProgrтssрукPayment/Wallet чусаичноь и сировоныт(gatewть, OFDAtomic)
| Отсутствие unified API documentatio | ✅ ИСПРАВЛЕНОn | Low | Ac ь4Aduplicate/loggerrerrors

**Production Readiness: 4/10** (снижено с 5.5/10 из-за критических проблем)
 InProgrssлен MODULES_MIGRATION_PLAN.mdн
---RaytoStart|Таяра испавлена
Pendng
## Быстрые ссылки

| Для кого | Документ | Время |
|----------|----------|-------|
| **Менеджеры** | [READM5.5.md](REулучш.md) |4 минеемы с тстаи исправлены
| **Разработчики** | [CANONICAL_SERVICES.md](CANONICAL_SERVICES.md) | 10 мин |
| **DevOps** | [docs/BIGDATA_SRE_RUNBOOK.md](docs/BIGDATA_SRE_RUNBOOK.md) | 20 мин |
| **QA** | [MOCKERY_CONSOLE_ISSUE_ANALYSIS.md](MOCKERY_CONSOLE_ISSUE_ANALYSIS.md) | 10 мин |
| **Все** | [QUICKSTART.md](QUICKSTART.md) | 5 мин |

---

## Готовы начать?

### Вариант 1: Быстро (15 мин)

1. [README.md](README.md) (10 мин)
2. [QUICKSTART.md](QUICKSTART.md) (5 мин)

### Вариант 2: Полно (2 часа)

Следуйте своей роли выше

### Вариант 3: Глубоко (4+ часа)

Все документы по вашему профилю

---

**Версия**: 2026 Q1
**Статус**: Active Development
**Production Readiness**: 4/10 (критические проблемы с тестами и дублированием кода)
**Дата**: 2026-04-30

**Удачной работы с CatVRF!**
25.5справлены,мгця впрцессе