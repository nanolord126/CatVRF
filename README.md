# 🐱 CatVRF — Мультивертикальный AI-маркетплейс

> **Версия:** 2026 Q2 · **Статус:** Active Development · **Лицензия:** MIT  
> **Production Readiness:** 7.0/10 — Высокая готовность, финальные доработки

CatVRF — это **масштабируемый мультитенантный маркетплейс** с поддержкой **38 бизнес-вертикалей** (красота, еда, медицина, такси, отели, логистика, CRM и десятки других). Каждая вертикаль имеет собственный AI-конструктор, B2C/B2B логику, антифрод-систему, кошелёк, IoT-интеграцию и compliance-мониторинг.

---

## 📖 Для тех, кто открыл проект впервые

Представь огромный онлайн-торговый центр, где под одной крышей работает маникюрный салон, ресторан, агентство недвижимости, химчистка, такси и ещё сто с лишним бизнесов. Каждый из них — **вертикаль**: отдельный домен со своими моделями данных, правилами, ценообразованием и AI-помощником.

При этом все они пользуются **единой инфраструктурой**: одной системой оплаты, одним антифродом, одним механизмом доставки, одним кошельком и одной системой уведомлений. Бизнес «Салон Анны» полностью изолирован от «Ресторана Ивана» — они находятся в одной базе данных, но никогда не видят данные друг друга. Это достигается через **мультиарендность (multi-tenancy)**: у каждого бизнеса есть `tenant_id`, который автоматически подставляется в каждый запрос к базе данных через глобальный Eloquent-scope.

Если ты разработчик — думай об этом как о **Laravel-монолите с доменной архитектурой**, строгими правилами на уровне архитектуры, ClickHouse-аналитикой для миллиардов событий и ML-прослойкой для рекомендаций и антифрода.

---

## ☁️ Облачная разработка (без локального Docker)

**Рекомендация:** Используйте облачное решение для разработки, чтобы не потреблять ресурсы локальной машины. Проект полностью настроен для работы в GitHub Codespaces и Gitpod.

### GitHub Codespaces (рекомендуется)

**Преимущества:**
- ✅ Мгновенный запуск (2-3 минуты)
- ✅ 60 бесплатных часов/месяц
- ✅ Глубокая интеграция с GitHub
- ✅ VS Code в браузере
- ✅ Предустановленный Docker и PHP 8.3

**Как начать:**
1. Откройте репозиторий на GitHub
2. Нажмите "Code" → "Codespaces" → "Create codespace on main"
3. Дождитесь создания среды (2-3 минуты)
4. Среда автоматически настроит зависимости и запустит миграции

**Стоимость:**
- Бесплатно: 60 часов/месяц
- Платно: $0.18/час после лимита

### Gitpod (альтернатива)

**Преимущества:**
- ✅ 50 бесплатных часов/месяц
- ✅ Поддержка GitHub, GitLab, Bitbucket
- ✅ VS Code или JetBrains в браузере

**Как начать:**
1. Откройте https://gitpod.io/#https://github.com/nanolord126/CatVRF
2. Дождитесь создания среды
3. Среда автоматически настроит зависимости

**Стоимость:**
- Бесплатно: 50 часов/месяц
- Платно: $0.07/час (Standard)

### VS Code Remote + VPS (долгосрочный)

Для долгосрочной разработки дешевле использовать VPS:
- Hetzner: $4.5/мес (2GB RAM, неограниченное время)
- DigitalOcean: $5/мес (1GB RAM, неограниченное время)

**Подробнее:** См. [cloud-development-setup.md](cloud-development-setup.md)

---

## 🚀 Ключевые возможности

**Multi-tenancy.** Каждый бизнес — это отдельный тенант. Данные одного тенанта физически недоступны другому. Изоляция обеспечивается пакетом `stancl/tenancy` и глобальными Eloquent-scope'ами во всех моделях.

**38 бизнес-вертикалей.** Красота, еда, медицина, такси, отели, логистика, фитнес, CRM, маркетплейс и десятки других доменов. Каждый домен живёт в `modules/{Vertical}/` и следует Clean Architecture + DDD.

**AI-конструкторы.** У каждой вертикали есть собственный сервис-оркестратор (`...ConstructorService`), который принимает фото или параметры, отправляет их в OpenAI Vision / GigaChat Vision, получает анализ, смешивает с профилем вкусов пользователя (`UserTasteProfile`) и возвращает персонализированные рекомендации товаров или услуг вместе со ссылкой на AR/3D-превью.

**Антифрод + ML.** Перед каждой мутацией (платёж, изменение баланса, крупный заказ) вызывается `FraudControlService::check($dto)`. Внутри он применяет жёсткие правила (hard rules) и затем ML-модель (`FraudMLService`), которая возвращает score от 0.0 до 1.0. При score выше 0.85 операция блокируется, а команде безопасности уходят уведомления во все каналы.

**Система кошельков.** Деньги на платформе движутся только через `WalletService`. Никаких прямых UPDATE к таблице балансов. Каждая операция — это запись в `balance_transactions` с типом (deposit, withdrawal, bonus, payout и т.д.), `correlation_id` и `DB::transaction()`.

**B2C и B2B.** Физические лица (B2C) и юридические лица/ИП (B2B) имеют разные цены, разные комиссии, разные правила резервирования товаров и разные условия оплаты. B2B-клиенты могут получать кредитный лимит, отсрочку платежа и доступ к оптовым API-ключам.

**Аналитика через ClickHouse.** Все события (просмотры, добавления в корзину, покупки, использование AI, рекламные клики) пишутся в ClickHouse в анонимизированном виде. Это позволяет строить дашборды по миллиардам событий за секунды.

**Реалтайм через Echo + Redis.** Обновления позиции курьера, изменения остатков товара, входящие сообщения — всё это транслируется через Laravel Echo (WebSocket) и Redis, без долгого polling.

**IoT-интеграция (Restaurant vertical).** Поддержка MQTT, Modbus TCP, WebSocket для умных ресторанов: датчики температуры, весовые датчики, умные холодильники, KDS-дисплеи, реалтайм-мониторинг оборудования.

**Compliance-мониторинг.** Поддержка 152-ФЗ (персональные данные), ФЗ-115 (AML/KYC), ФЗ-161 (национальная платёжная система), 54-ФЗ (онлайн-кассы). Интеграция с Росфинмониторингом.

**BigData Observability.** 9-слойная архитектура мониторинга: PipelineHealth, DataFreshness, CLVModelDrift, QueryPerformance, Prometheus exporter, OpenTelemetry tracing, self-healing.

**CRM-система (CatCRM).** Полноценная CRM-система для управления клиентами, лидами, сделками, аналитикой. Интеграция с внешними CRM-системами (AmoCRM, Bitrix24), автоматическое распределение лидов, сегментация клиентов, RFM-аналитика.

---

## 🏗️ Архитектура

### Жизненный цикл запроса

Каждый HTTP-запрос проходит через строго упорядоченный middleware-конвейер, прежде чем добраться до контроллера:

```
HTTP-запрос
     │
     ▼
┌─────────────────────────────────────────────────────────┐
│ 1. CorrelationIdMiddleware                               │
│    Читает X-Correlation-ID из заголовка или генерирует  │
│    UUID. Прокидывает его во все логи, события, джобы.   │
├─────────────────────────────────────────────────────────┤
│ 2. auth:sanctum                                         │
│    Проверяет Bearer-токен. Отдаёт 401, если невалиден.  │
├─────────────────────────────────────────────────────────┤
│ 3. TenantMiddleware                                     │
│    Определяет тенанта из поддомена или заголовка.       │
│    Устанавливает tenant() глобально на запрос.          │
├─────────────────────────────────────────────────────────┤
│ 4. B2CB2BMiddleware                                     │
│    Проверяет: есть ли inn + business_card_id?           │
│    Если да — B2B-режим (оптовые цены, кредит).          │
│    Если нет — B2C-режим (розница, предоплата).          │
├─────────────────────────────────────────────────────────┤
│ 5. RateLimitingMiddleware                               │
│    B2C: 100 req/min. B2B: 500 req/min.                  │
│    При превышении → 429, запись в security_events.      │
├─────────────────────────────────────────────────────────┤
│ 6. FraudCheckMiddleware                                 │
│    Предварительный скоринг (IP, устройство, частота).   │
│    Явно подозрительные запросы блокируются здесь.       │
├─────────────────────────────────────────────────────────┤
│ 7. AgeVerificationMiddleware (где нужно)                │
│    Проверяет возраст для алкоголя, табака и т.д.        │
└─────────────────────────────────────────────────────────┘
     │
     ▼
  Controller (только роутинг + вызов сервиса, max 8 методов)
     │
     ▼
  Service (вся бизнес-логика)
     │
     ├─► FraudControlService::check($dto)      ← обязательно
     ├─► DB::transaction(function() { ... })   ← обязательно
     ├─► Model::create / update / delete
     ├─► AuditService::log(...)                ← обязательно
     └─► event(new SomethingHappenedEvent(...))
     │
     ▼
  Model (Eloquent + глобальный tenant-scope)
     │
     ▼
  PostgreSQL / Redis / ClickHouse
```

### 9-слойная архитектура домена

Каждая из 33+ вертикалей строго следует одному шаблону. Отступление от него — reject PR:

```
app/Domains/{Vertical}/
├── Models/
│   └── Salon.php          — Eloquent + global scope tenant_id + booted()
│                             Обязательные поля: uuid, correlation_id,
│                             tags (json), tenant_id, business_group_id
│
├── DTOs/
│   └── CreateSalonDto.php — final readonly class. Только конструктор.
│                             Методы: from(Request), toArray().
│                             Никакого состояния, никакой бизнес-логики.
│
├── Services/
│   ├── SalonService.php   — final readonly class. Только constructor injection.
│   │                         Методы: create, update, delete, list, getById.
│   │                         Перед каждой мутацией: fraud->check() + DB::transaction()
│   └── AI/
│       └── BeautyImageConstructorService.php — AI-оркестратор вертикали
│
├── Requests/
│   └── CreateSalonRequest.php — валидация входных данных, авторизация
│
├── Resources/
│   └── SalonResource.php  — трансформация модели в JSON для API
│
├── Events/
│   └── SalonCreatedEvent.php — fired после успешного создания в транзакции
│
├── Listeners/
│   └── OnSalonCreated.php — реакция: уведомление, индексация, ML-обновление
│
├── Jobs/
│   └── SomeLongRunningJob.php — фоновая задача (очередь, Horizon)
│
└── Filament/
    ├── Resources/
    │   └── SalonResource.php — CRUD в панели управления тенанта
    └── Pages/
        └── SalonDashboard.php — кастомная страница с метриками
```

### Правило глобального scope в каждой модели

Это самое важное правило. Без него данные тенантов будут смешиваться:

```php
final class Salon extends Model
{
    protected static function booted(): void
    {
        // Каждый SELECT автоматически добавляет WHERE tenant_id = ?
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        // UUID генерируется автоматически при создании
        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        // Автоматический аудит-лог на все события модели
        static::created(fn ($m)  => app(AuditService::class)->logModelEvent('created',  $m));
        static::updated(fn ($m)  => app(AuditService::class)->logModelEvent('updated',  $m, $m->getOriginal(), $m->getChanges()));
        static::deleted(fn ($m)  => app(AuditService::class)->logModelEvent('deleted',  $m));
    }
}
```

---

## 🛠️ Технологический стек

### Backend

**PHP 8.3+** с `declare(strict_types=1)` в каждом файле. Строгая типизация обязательна — это позволяет поймать большой класс ошибок ещё на этапе анализа.

**Laravel 11.x** — основной фреймворк. Используется всё: Eloquent, Events, Queue, Broadcasting, Mail, Notifications, Sanctum, Horizon.

**Filament 3.x** — генерирует три отдельные admin-панели (Admin, Tenant, B2B) с нуля. Ни один из пользовательских кабинетов не делается на Filament — только на Livewire 3.

**stancl/tenancy 3.7** — пакет мультиарендности. Каждый тенант имеет изолированные данные через глобальные scope'ы и middleware. В конфиге `config/tenancy.php` настроены модели, роуты и инициализаторы.

**bavix/laravel-wallet** — управление кошельками и балансами. Поверх него написан собственный `WalletService`, который добавляет fraud-check, аудит и correlation_id.

**spatie/laravel-permission 6.x** — RBAC. Роли: super-admin, tenant-owner, b2b-manager, courier, customer и др.

**spatie/laravel-data 4.x** — основа для DTO. Все Data Transfer Objects наследуют от `Spatie\LaravelData\Data` или пишутся вручную как `final readonly class`.

**spatie/laravel-medialibrary 11.x** — загрузка и хранение медиафайлов (фото товаров, документы, фото для AI-конструктора).

### Frontend

**Livewire 3 + Alpine.js** — все личные кабинеты пользователей (B2C и B2B). Реактивность без написания отдельного SPA. Компоненты лежат в `app/Livewire/`.

**Vue 3 + Vite** — сложные AR/3D-компоненты (виртуальная примерка одежды, 3D-тур по квартире, AR-примерка причёски). Подключается точечно там, где нужна тяжёлая интерактивность.

**Tailwind CSS 4** — утилитарные классы для всего UI.

### Базы данных

**PostgreSQL 16** — основная база данных. Все бизнес-данные: пользователи, заказы, кошельки, модели, аудит-логи. Используется `lockForUpdate()` в транзакциях для предотвращения race conditions (особенно в кошельках и инвентаре).

**Redis 7+** — четыре роли одновременно:
- **Кэш** — профили пользователей, результаты AI-конструкторов (TTL 3600 сек), остатки товаров (TTL 60 сек)
- **Очереди** — все фоновые задачи (уведомления, ML-расчёты, аудит-логи, геотрекинг)
- **Сессии** — авторизация и данные сессий
- **Rate limiting** — счётчики запросов по IP и user_id

**ClickHouse** — колоночная база данных для аналитики и ML. Хранит:
- `anonymized_behavior` — все пользовательские события (обезличенные)
- `security_events` — все события безопасности
- `audit_logs_archive` — архив аудит-логов старше 30 дней
- `marketing_events`, `ad_impressions`, `ad_clicks` — рекламная аналитика

### Инфраструктура

**Docker + Laravel Sail** — локальная разработка. `docker-compose.yml` поднимает: app (PHP-FPM), nginx, PostgreSQL, Redis.

**Laravel Horizon** — мониторинг очередей. Дашборд на `/horizon`. Отдельные воркеры для разных очередей: `default`, `audit-logs`, `fraud-notifications`, `ml`, `emails`.

**AWS S3 / MinIO** — объектное хранилище для медиафайлов. В dev используется MinIO (self-hosted), в prod — AWS S3.

---

## ⚡ Быстрый старт

### Требования

Для запуска через Docker нужно только:
- Docker Desktop 4.x+
- Git

Для локального запуска:
- PHP 8.3+ с расширениями: `pdo_pgsql`, `redis`, `gd`, `zip`, `mbstring`
- Composer 2.x
- PostgreSQL 16
- Redis 7+
- Node.js 20+ и npm

### Вариант 1 — Docker (рекомендуется для быстрого старта)

```bash
# 1. Клонировать репозиторий
git clone https://github.com/your-org/CatVRF.git
cd CatVRF

# 2. Скопировать конфиг окружения
cp .env.example .env

# 3. Поднять все контейнеры в фоне
docker-compose up -d

# 4. Установить PHP-зависимости внутри контейнера
docker-compose exec app composer install

# 5. Сгенерировать ключ шифрования приложения
docker-compose exec app php artisan key:generate

# 6. Запустить все миграции и базовые сидеры
docker-compose exec app php artisan migrate --seed

# 7. Собрать фронтенд
docker-compose exec app npm install && npm run build

# Готово! Открыть в браузере: http://localhost:8000
```

### Вариант 2 — Локально (без Docker)

```bash
git clone https://github.com/your-org/CatVRF.git
cd CatVRF

# Скопировать и настроить окружение
cp .env.example .env
# ⚠️ Обязательно заполни: DB_*, REDIS_*, OPENAI_API_KEY

# Установить зависимости
composer install
npm install

# Инициализация
php artisan key:generate
php artisan migrate --seed

# Запустить в трёх отдельных терминалах:
```

**Терминал 1** — веб-сервер (с hot-reload):
```bash
php artisan octane:start --watch
```

**Терминал 2** — воркер очередей (уведомления, AI, аудит):
```bash
php artisan horizon
```

**Терминал 3** — сборка фронтенда с watch:
```bash
npm run dev
```

### Тестовые данные

После `migrate --seed` в базе есть базовый набор данных. Для конкретной вертикали используй отдельные сидеры:

```bash
# Красота: 1 салон, 3 мастера, 5 услуг, 10 бронирований
php artisan db:seed --class=BeautySeeder

# Еда: 3 ресторана, 20 блюд, 5 заказов
php artisan db:seed --class=FoodSeeder

# Тестовый пользователь (admin@catvrf.ru / password)
php artisan db:seed --class=UserSeeder
```

---

## 🔧 Переменные окружения

Все настройки проекта задаются через `.env`. Ниже полный список обязательных и важных переменных с пояснениями:

```env
# ════════════════════════════════════════════════
#  ПРИЛОЖЕНИЕ
# ════════════════════════════════════════════════

APP_NAME=CatVRF
APP_ENV=local              # local | staging | production
APP_KEY=                   # ОБЯЗАТЕЛЬНО: php artisan key:generate
APP_DEBUG=true             # В production СТРОГО false
APP_URL=http://localhost:8000
APP_LOCALE=ru
APP_TIMEZONE=Europe/Moscow

# Соль для анонимизации данных (GDPR/ФЗ-152).
# Меняется раз в год через AnnualAnonymizationJob.
# НИКОГДА не коммить реальное значение в git!
APP_ANONYMIZATION_SALT=change-me-every-year
APP_AUDIT_SALT=another-secret-salt

# ════════════════════════════════════════════════
#  БАЗА ДАННЫХ (PostgreSQL)
# ════════════════════════════════════════════════

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=catvrf
DB_USERNAME=catvrf
DB_PASSWORD=secret

# ════════════════════════════════════════════════
#  REDIS (кэш + очереди + сессии + rate-limit)
# ════════════════════════════════════════════════

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Драйверы — всё через Redis для производительности
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=redis

# ════════════════════════════════════════════════
#  CLICKHOUSE (аналитика, ML, аудит-архив)
# ════════════════════════════════════════════════

CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_DATABASE=catvrf_analytics
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=

# ════════════════════════════════════════════════
#  ПЛАТЁЖНЫЕ ШЛЮЗЫ
# ════════════════════════════════════════════════

# Тинькофф (основной)
TINKOFF_API_KEY=
TINKOFF_API_SECRET=
TINKOFF_TERMINAL_KEY=

# СБП
SBP_MERCHANT_ID=
SBP_SECRET_KEY=

# Сбер
SBER_CLIENT_ID=
SBER_CLIENT_SECRET=

# ════════════════════════════════════════════════
#  AI / OPENAI
# ════════════════════════════════════════════════

# Основной ключ для GPT-4o Vision (AI-конструкторы)
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o
OPENAI_ORGANIZATION=

# GigaChat (альтернатива для РФ)
GIGACHAT_AUTH_KEY=
GIGACHAT_SCOPE=GIGACHAT_API_PERS

# ════════════════════════════════════════════════
#  УВЕДОМЛЕНИЯ
# ════════════════════════════════════════════════

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@catvrf.ru
MAIL_FROM_NAME=CatVRF

# Telegram (для критических fraud-уведомлений)
TELEGRAM_BOT_TOKEN=
TELEGRAM_SECURITY_CHAT_ID=   # куда слать Critical-события

# Push (Firebase Cloud Messaging)
FIREBASE_SERVER_KEY=

# SMS (Twilio или sms.ru)
SMS_PROVIDER=sms_ru          # sms_ru | twilio
SMS_RU_API_KEY=
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=

# Slack (для команды безопасности)
SLACK_SECURITY_WEBHOOK_URL=

# ════════════════════════════════════════════════
#  ХРАНЕНИЕ ФАЙЛОВ
# ════════════════════════════════════════════════

FILESYSTEM_DISK=s3           # local в dev, s3 в prod

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=catvrf-media
AWS_URL=

# MinIO (для локальной разработки)
MINIO_ENDPOINT=http://localhost:9000
MINIO_KEY=minio
MINIO_SECRET=minio123
MINIO_BUCKET=catvrf

# ════════════════════════════════════════════════
#  TENANCY (мультиарендность)
# ════════════════════════════════════════════════

TENANCY_CENTRAL_DOMAINS=localhost,catvrf.ru
```

---

## 📁 Структура проекта

```
CatVRF/
│
├── app/
│   │
│   ├── Domains/                    ← все 33+ бизнес-вертикалей
│   │   │                             Каждая — отдельный домен,
│   │   │                             строго 9 слоёв
│   │   ├── Beauty/
│   │   │   ├── Models/
│   │   │   │   ├── Salon.php
│   │   │   │   ├── Master.php
│   │   │   │   ├── Service.php
│   │   │   │   └── Appointment.php
│   │   │   ├── DTOs/
│   │   │   │   ├── CreateAppointmentDto.php
│   │   │   │   └── AnalyzeFaceDto.php
│   │   │   ├── Services/
│   │   │   │   ├── BeautyService.php
│   │   │   │   ├── BeautyAppointmentService.php
│   │   │   │   └── AI/
│   │   │   │       └── BeautyImageConstructorService.php
│   │   │   ├── Requests/
│   │   │   ├── Resources/
│   │   │   ├── Events/
│   │   │   ├── Listeners/
│   │   │   ├── Jobs/
│   │   │   └── Filament/
│   │   │
│   │   ├── Food/                   ← аналогично Beauty
│   │   ├── Furniture/
│   │   ├── Taxi/
│   │   ├── Medical/
│   │   ├── Hotels/
│   │   ├── Fitness/
│   │   ├── Travel/
│   │   ├── RealEstate/
│   │   └── ... (ещё 118 вертикалей)
│   │
│   ├── Services/                   ← глобальные, не привязанные к вертикали
│   │   ├── FraudControlService.php     ← вызывается перед каждой мутацией
│   │   ├── FraudMLService.php          ← ML-скоринг (XGBoost / LightGBM)
│   │   ├── AuditService.php            ← запись в audit_logs (async, через Queue)
│   │   ├── WalletService.php           ← все операции с балансом
│   │   ├── RecommendationService.php   ← персональные рекомендации
│   │   ├── NotificationService.php     ← роутинг уведомлений по каналам
│   │   ├── NotificationChannelService.php ← email/push/sms/telegram отправка
│   │   ├── UserAddressService.php      ← хранение до 5 адресов пользователя
│   │   ├── BusinessGroupService.php    ← управление B2B-филиалами
│   │   ├── AI/
│   │   │   └── AIConstructorService.php   ← универсальный оркестратор AI
│   │   ├── ML/
│   │   │   ├── UserBehaviorAnalyzerService.php
│   │   │   ├── UserTasteAnalyzerService.php
│   │   │   ├── NewUserColdStartService.php
│   │   │   ├── ReturningUserDeepProfileService.php
│   │   │   ├── AnonymizationService.php
│   │   │   └── BigDataAggregatorService.php
│   │   ├── Fraud/
│   │   │   └── FraudNotificationService.php
│   │   ├── Delivery/
│   │   │   ├── CourierService.php
│   │   │   ├── DeliveryService.php
│   │   │   ├── GeotrackingService.php
│   │   │   └── RouteOptimizationService.php
│   │   ├── Payment/
│   │   │   └── PaymentGatewayService.php
│   │   ├── Wallet/
│   │   ├── Bonus/
│   │   └── Marketing/
│   │       ├── MarketingCampaignService.php
│   │       ├── NewsletterService.php
│   │       └── AdEngineService.php
│   │
│   ├── Filament/                   ← три отдельные панели управления
│   │   ├── Admin/                  ← /admin (суперадмины)
│   │   ├── Tenant/                 ← /tenant (владельцы бизнеса)
│   │   ├── B2B/                    ← /b2b (юрлица, филиалы)
│   │   └── Courier/                ← /courier (курьеры)
│   │
│   ├── Http/
│   │   ├── Controllers/            ← тонкие контроллеры, только вызов сервисов
│   │   └── Middleware/
│   │       ├── CorrelationIdMiddleware.php
│   │       ├── TenantMiddleware.php
│   │       ├── B2CB2BMiddleware.php
│   │       ├── RateLimitingMiddleware.php
│   │       ├── FraudCheckMiddleware.php
│   │       └── AgeVerificationMiddleware.php
│   │
│   ├── Livewire/                   ← личные кабинеты пользователей (не Filament)
│   │   └── User/
│   │       ├── Dashboard.php
│   │       ├── AIConstructor.php
│   │       ├── Wallet.php
│   │       ├── Orders.php
│   │       └── Addresses.php
│   │
│   └── Models/                     ← общие модели
│       ├── User.php
│       ├── Tenant.php
│       └── BusinessGroup.php
│
├── routes/                         ← маршруты
│   ├── api.php                     ← базовые маршруты
│   ├── beauty.api.php              ← /api/v1/beauty/*
│   ├── food.api.php                ← /api/v1/food/*
│   ├── b2b.beauty.api.php          ← /api/b2b/v1/beauty/*
│   ├── wallet.api.php              ← /api/v1/wallet/*
│   ├── payment.api.php             ← /api/v1/payments/*
│   └── ... (100+ файлов маршрутов по вертикалям)
│
├── database/
│   ├── migrations/                 ← 150+ миграций (по одной на сущность)
│   ├── factories/                  ← фабрики для тестов и сидеров
│   └── seeders/                    ← начальные данные
│
├── config/
│   ├── verticals.php               ← реестр всех 33+ вертикалей
│   ├── fraud.php                   ← пороги и правила антифрода
│   ├── bonuses.php                 ← правила начисления бонусов
│   ├── recommendations.php         ← настройки ML-рекомендаций
│   ├── taste_ml.php                ← настройки ML-вкусов
│   └── ...
│
├── tests/
│   ├── Feature/                    ← интеграционные тесты (полный цикл)
│   └── Unit/                       ← юнит-тесты (каждый сервис отдельно)
│
├── docker-compose.yml
├── .github/
│   └── copilot-instructions.md    ← «Библия проекта» (архитектурные правила)
└── composer.json
```

---

## 🖥️ Панели управления (Filament)

Проект использует **три полностью отдельные Filament-панели**. Личные кабинеты конечных пользователей — на Livewire 3 (не Filament).

### Admin Panel (`/admin`)

Доступна только суперадминистраторам. Видит **все** тенанты, все транзакции, все события безопасности. Может заходить в любой тенант, делать финансовые корректировки, управлять ML-моделями.

Основные разделы: Tenant Management, User Management, Fraud Dashboard (live), Security Events, Payouts Management, ML Model Versions, Analytics (GMV, DAU, конверсия).

### Tenant Panel (`/tenant`)

Доступна владельцу бизнеса. Работает строго в рамках своего `tenant_id`. Видит только свои данные.

Основные разделы:
- **Dashboard** — реалтайм-метрики (GMV, заказы, новые клиенты, конверсия)
- **AI-конструкторы** — запуск прямо из панели, просмотр результатов
- **Wallet** — баланс, история транзакций, вывод средств
- **Products / Services** — управление каталогом вертикали
- **Orders** — все заказы с фильтрами и экспортом
- **Marketing** — кампании, рассылки, рекламный бюджет (списывается из Wallet)
- **Staff** — сотрудники, смены, зарплаты (через PayrollService)
- **Analytics** — ClickHouse-дашборд с графиками

### B2B Panel (`/b2b`)

Доступна юрлицам и ИП (по наличию `inn` и активной `BusinessGroup`).

Расширенный функционал по сравнению с B2C:
- Оптовые цены и MOQ (минимальные количества заказа)
- Кредитный лимит и отсрочка платежа (7–30 дней)
- Множественные филиалы (`BusinessGroup`) с переключением в сессии
- API-ключи (`X-B2B-API-Key`) для интеграции с 1С и ERP
- Расширенные отчёты: оборот, кредит, сверка
- Массовые заказы и импорт через Excel

### Личный кабинет пользователя (`/cabinet`)

Сделан на **Livewire 3 + Vue 3**. Не Filament. Это принципиально — Filament не предназначен для конечных пользователей.

Включает:
- Переключение режима B2C ↔ B2B (если есть бизнес-карта с ИНН)
- Запуск AI-конструкторов (загрузка фото → результат + AR)
- Сохранённые дизайны и результаты AI (`user_ai_designs`)
- Wallet: баланс, бонусы, история операций
- Заказы и бронирования
- До 5 сохранённых адресов (`UserAddressService`)
- Профиль вкусов и персональные рекомендации
- 2FA: подключение/отключение, управление устройствами

---

## 🌐 API

### Структура URL-пространства

```
# B2C API (через Sanctum Bearer Token)
GET    /api/v1/{vertical}/...
POST   /api/v1/{vertical}/...

# B2B API (через X-B2B-API-Key заголовок)
GET    /api/b2b/v1/{vertical}/...
POST   /api/b2b/v1/{vertical}/...
POST   /api/b2b/v1/{vertical}/bulk      ← массовые операции
GET    /api/b2b/v1/reports/...          ← отчёты

# Платежи
POST   /api/v1/payments/init
POST   /api/v1/payments/webhook         ← публичный, без auth
POST   /api/v1/payments/refund

# Кошелёк
GET    /api/v1/wallet/balance
GET    /api/v1/wallet/transactions
POST   /api/v1/wallet/payout
```

### Обязательные HTTP-заголовки

Каждый запрос **обязан** содержать:

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {sanctum_token}

# Уникальный ID для трейсинга. Если не передан — генерируется автоматически.
# Он проходит через все логи, события, джобы и ответы.
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000

# Ключ идемпотентности для POST/PUT-запросов (создание, изменение).
# Повторный запрос с тем же ключом вернёт тот же результат без повторного выполнения.
X-Idempotency-Key: 7c9e6679-7425-40de-944b-e07fc1f90ae7
```

Для B2B API вместо Bearer Token:

```http
X-B2B-API-Key: b2b_64символа_случайных
```

### Пример: создание бронирования в Beauty

**Запрос:**

```bash
curl -X POST https://your-domain.ru/api/v1/beauty/appointments \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..." \
  -H "X-Correlation-ID: $(uuidgen)" \
  -H "X-Idempotency-Key: $(uuidgen)" \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 3,
    "master_id": 7,
    "starts_at": "2026-05-15 14:30:00",
    "comment": "Хочу натуральный цвет"
  }'
```

**Ответ (201 Created):**

```json
{
  "data": {
    "id": 891,
    "uuid": "550e8400-e29b-41d4-a716-446655440055",
    "status": "confirmed",
    "starts_at": "2026-05-15T14:30:00+03:00",
    "ends_at":   "2026-05-15T16:00:00+03:00",
    "price": 2500,
    "master": {
      "id": 7,
      "name": "Анна Петрова",
      "rating": 4.9,
      "photo_url": "https://cdn.catvrf.ru/masters/7/avatar.jpg"
    },
    "service": {
      "id": 3,
      "name": "Окрашивание",
      "duration_minutes": 90
    },
    "salon": {
      "id": 2,
      "name": "Салон «Облака»",
      "address": "ул. Ленина, 45"
    }
  },
  "meta": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "timestamp": "2026-04-12T10:00:00+03:00"
  }
}
```

**Ошибка (422 при конфликте слотов):**

```json
{
  "message": "Выбранное время уже занято",
  "errors": {
    "starts_at": ["Мастер недоступен с 14:30 до 16:00"]
  },
  "meta": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

### Пример: B2B массовый заказ

```bash
curl -X POST https://your-domain.ru/api/b2b/v1/food/orders/bulk \
  -H "X-B2B-API-Key: b2b_abc123..." \
  -H "X-Correlation-ID: $(uuidgen)" \
  -H "Content-Type: application/json" \
  -d '{
    "orders": [
      { "dish_id": 12, "quantity": 100, "delivery_date": "2026-05-01" },
      { "dish_id": 34, "quantity": 50,  "delivery_date": "2026-05-01" }
    ],
    "payment_terms": "deferred_14_days"
  }'
```

---

## 🤖 AI-конструкторы

AI-конструктор — это сервис-оркестратор в `app/Domains/{Vertical}/Services/AI/`. Каждая вертикаль обязана его иметь — это `PRODUCTION MANDATORY`.

### Полный процесс работы

```
1. Пользователь загружает фото (или вводит параметры)
         │
         ▼
2. FraudControlService::check() — проверка квоты и подозрительности
         │
         ▼
3. OpenAI Vision (GPT-4o) / GigaChat Vision
   Prompt: "Проанализируй лицо. Определи: тип, тон кожи, цвет волос..."
   → Возвращает JSON с анализом
         │
         ▼
4. UserTasteAnalyzerService::getProfile($userId)
   Читает user_taste_profiles: любимые стили, цвета, ценовой диапазон,
   история покупок. Мерджит с результатом Vision API.
         │
         ▼
5. RecommendationService::getFor{Vertical}($profile, $userId)
   Выбирает подходящие товары/услуги из Inventory.
   Учитывает: наличие, цены (B2C/B2B), рейтинг.
         │
         ▼
6. InventoryService::getAvailableStock($productId) для каждой рекомендации
   Товары без наличия — помечаются in_stock: false (серые на UI)
         │
         ▼
7. Генерация AR/3D-ссылки (Ready Player Me / AR.js / Blender)
         │
         ▼
8. DB::transaction() — сохранение результата в user_ai_designs
         │
         ▼
9. Redis::set("user_ai_designs:{$userId}", $result, ttl: 3600)
         │
         ▼
10. AuditService::log('ai_constructor_used', AIConstruction::class, $id)
         │
         ▼
11. Возврат AIConstructionResult (DTO):
    {
      vertical, type, style_profile,
      recommendations: [{ product_id, name, price, in_stock, ar_url }],
      ar_link, cost_estimate, correlation_id, confidence_score
    }
```

### Пример запроса к Beauty AI-конструктору

```bash
curl -X POST /api/v1/beauty/ai-constructor \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Correlation-ID: $(uuidgen)" \
  -F "photo=@my-selfie.jpg"
```

```json
{
  "data": {
    "style_profile": {
      "face_type": "oval",
      "skin_tone": "warm_beige",
      "hair_color": "dark_brown",
      "eyebrow_shape": "arched",
      "skin_condition": "combination",
      "estimated_age": 28
    },
    "recommendations": [
      {
        "type": "haircut",
        "name": "Удлинённое каре",
        "master_id": 5,
        "master_name": "Ольга",
        "price": 2500,
        "in_stock": true,
        "ar_try_on_url": "https://ar.catvrf.ru/hair/kate-bob/user-99"
      },
      {
        "type": "coloring",
        "name": "Балаяж тёплый",
        "master_id": 3,
        "price": 5800,
        "in_stock": true,
        "ar_try_on_url": "https://ar.catvrf.ru/color/balayage-warm/user-99"
      }
    ],
    "ar_link": "https://ar.catvrf.ru/full-look/beauty/user-99",
    "cost_estimate": 8300,
    "confidence_score": 0.94
  }
}
```

### UserTasteProfile — мозг всех конструкторов

Таблица `user_taste_profiles` обновляется раз в неделю через `MLRecalculateJob`. Хранит:

- Любимые категории по вертикалям (beauty: 0.9, food: 0.4...)
- Предпочтения по стилю, цветам, размерам
- Ценовой диапазон (min/max по истории покупок)
- Диетические ограничения и аллергены (для Food)
- Любимые бренды и мастера
- Цветотип (для Fashion и Beauty)

Каждый AI-конструктор **обязан** вызывать `UserTasteAnalyzerService::getProfile($userId)` и мерджить результат с данными от Vision API.

---

## 🛡️ Система безопасности и Fraud Control

### Принцип работы

`FraudControlService::check($dto)` вызывается **перед каждой мутацией** — это одно из железных правил архитектуры. Вызов не опциональный, пропуск — reject PR.

```php
// Пример из SalonService:
public function create(CreateSalonDto $dto): Salon
{
    // 1. Сначала всегда fraud-check
    $this->fraud->check($dto);

    // 2. Только после этого — транзакция с мутацией
    return DB::transaction(function () use ($dto) {
        $salon = Salon::create($dto->toArray());
        // ...
    });
}
```

### Два уровня проверки

**Уровень 1 — Hard Rules (быстрый, синхронный):**

| Правило | Добавляет к score |
|---|---|
| Сумма операции > 50 000 ₽ при первой операции | +0.6 |
| Смена устройства за последние 24 часа | +0.3 |
| Более 10 операций за последние 5 минут | +0.4 |
| Геолокация изменилась на > 500 км за 1 час | +0.5 |
| Новый аккаунт (< 7 дней) + сумма > 10 000 ₽ | +0.4 |
| IP из стоп-листа | +0.8 |

**Уровень 2 — ML Score (асинхронный, точный):**

`FraudMLService::scoreOperation($dto)` извлекает 40+ feature'ов (сумма, частота, устройство, время суток, история, поведение) и скармливает их в XGBoost/LightGBM модель, обученную на исторических данных о мошенничестве. Возвращает float от 0.0 до 1.0.

Модель переобучается **ежедневно** в 03:00 через `MLRecalculateJob`. Версии моделей хранятся в `fraud_model_versions` и `storage/models/fraud/`.

**Fallback:** если ML-сервис недоступен — используются только hard rules.

### Решение по итоговому score

```
Итоговый score = hard_rules_score + ml_score

score < 0.40  →  INFO    — операция разрешена, только audit-лог
score 0.40–0.65  →  WARNING — операция разрешена, In-app + Email пользователю
score 0.65–0.85  →  HIGH    — операция идёт на Review, Push + Telegram
                               владельцу бизнеса и команде безопасности
score > 0.85  →  CRITICAL  — операция ЗАБЛОКИРОВАНА, выброс FraudBlockedException
                               Немедленно: In-app + Email + Push + SMS + Telegram + Slack
                               всем: пользователь, владелец, security, admin
```

### Хранение событий

Все проверки (даже разрешённые) записываются в таблицу `fraud_attempts`:

```php
// Структура fraud_attempts:
// tenant_id, user_id, operation_type, ip_address,
// device_fingerprint (SHA256), correlation_id,
// ml_score, ml_version, features_json (все 40+ фич),
// decision (allow/block/review), blocked_at, reason
```

Для аналитики события дублируются в ClickHouse-таблицу `security_events`.

### Уведомления о фроде

Все уведомления отправляются **асинхронно** через `FraudNotificationJob` в очереди `fraud-notifications`. Используется `FraudNotificationService`, который определяет severity, собирает список получателей и каналов, создаёт запись в `fraud_notifications`, ставит джоб в очередь.

`NotificationChannelService` реализует отправку по каждому каналу: in-app, email, push, telegram, sms, slack.

### Мониторинг безопасности в реалтайм

`SecurityMonitoringService::logEvent($dto)` вызывается из всех критических мест:
- `AuthService` — неудачный вход, слишком много попыток
- `RateLimitingMiddleware` — превышение лимита
- `PaymentService` — подозрительный платёж
- `WalletService` — подозрительный вывод
- `2FA` — неудачная попытка кода

В Filament Admin Panel есть `SecurityDashboard` с виджетами: Failed Logins, Fraud Attempts, Rate Limit Violations, Critical Events Chart.

---

## 💳 Кошельки и платежи

### Концептуальное разделение

| Термин | Что это | Где хранится |
|---|---|---|
| **Payment** | Внешний платёж через банковский шлюз | `payment_transactions` |
| **Wallet** | Внутренний счёт на платформе | `wallets` + `balance_transactions` |
| **Balance** | Текущий остаток (только для чтения) | Вычисляется из `balance_transactions` |
| **Bonus** | Виртуальные рубли для покупок на платформе | `bonuses` + `bonus_transactions` |

Payment — это деньги снаружи (карта, СБП). Wallet — это деньги внутри платформы. Они никогда не смешиваются в коде: PaymentService → webhook → WalletService::credit().

### Полный поток оплаты

```
1. Пользователь нажимает «Оплатить»
         │
         ▼
2. FraudControlService::check($dto)                  ← ОБЯЗАТЕЛЬНО
         │
         ▼
3. IdempotencyService::check($key) — уже оплачено?   ← не дать списать дважды
         │
         ▼
4. PaymentService::initPayment($dto)
   → Создаёт payment_transaction (status: pending)
   → Вызывает PaymentGateway::init() (Tinkoff / SBP / Sber)
   → Возвращает ссылку на оплату банку
         │
         ▼
5. Пользователь оплачивает в банке
         │
         ▼
6. Банк присылает webhook на POST /api/v1/payments/webhook
         │
         ▼
7. PaymentService::handleWebhook($payload)
   → Верифицирует подпись шлюза
   → Обновляет payment_transaction (status: captured)
         │
         ▼
8. DB::transaction():
   WalletService::credit($walletId, $amount, 'deposit', $correlationId)
   → Создаёт balance_transaction (type: deposit)
   → Обновляет wallets.current_balance
   → Инвалидирует кэш Redis wallet:{walletId}
         │
         ▼
9. BonusService::award($userId, $rules) — начисляет бонусы по правилам
         │
         ▼
10. event(new PaymentCompletedEvent($payment, $correlationId))
    → Listener: уведомить пользователя
    → Listener: обновить статус заказа
    → Listener: запустить логику вертикали (подтвердить бронирование и т.д.)
```

### Правило WalletService

```php
// ✅ ПРАВИЛЬНО — всегда через сервис
$this->walletService->credit($walletId, 500.00, 'bonus', $dto->correlationId);
$this->walletService->debit($walletId, 1000.00, 'purchase', $dto->correlationId);

// ❌ НИКОГДА ТАК — reject PR
Wallet::where('id', $id)->update(['current_balance' => DB::raw('current_balance + 500')]);
```

Внутри `WalletService` всегда: `lockForUpdate()` + `DB::transaction()` + обновление Redis-кэша.

### Правила корзины

Корзина — отдельная система с жёсткими правилами:

- **1 продавец = 1 корзина.** Если пользователь добавляет товары от разных продавцов — создаются разные корзины.
- **Максимум 20 корзин** на одного пользователя одновременно. Хранятся в IndexedDB (браузер) + Redis (сервер).
- **Резерв 20 минут.** При добавлении товара `InventoryService::reserve()` создаёт запись в `reservations` с `expires_at = now() + 20 min`. `ReservationCleanupJob` запускается каждую минуту и снимает просроченные резервы.
- **Ценообразование.** Если цена товара выросла с момента добавления — показывается новая цена. Если упала — остаётся старая. Пользователь никогда не заплатит меньше, чем ожидал при добавлении.
- **Серые товары.** Товары без наличия отображаются в grayscale, кнопка «В корзину» скрыта.

### Виды транзакций в `balance_transactions`

```
deposit        — пополнение (с банковской карты через Payment)
withdrawal     — списание (покупка, оплата услуги)
bonus          — начисление бонусов
bonus_spend    — трата бонусов
commission     — комиссия платформы
refund         — возврат средств
payout         — выплата продавцу / курьеру
hold           — заморозка средств (до подтверждения услуги)
release_hold   — размораживание
payroll        — выплата зарплаты сотруднику
marketing_spend — списание рекламного бюджета
```

### Бонусная система

`BonusService::award()` вызывается после каждой успешной операции. Правила начисления хранятся в `config/bonuses.php` и в модели `BonusRule` (можно настраивать из Tenant Panel без деплоя):

- Процент от суммы покупки
- Бонус за реферала
- Бонус за первый заказ
- Бонус за AR-примерку
- Бонус за использование AI-конструктора

B2C клиенты могут только тратить бонусы внутри платформы. B2B клиенты с золотым тиром — могут выводить бонусы на счёт.

---

## 🏢 B2C и B2B

### Определение типа клиента

```php
// В B2CB2BMiddleware — ТОЛЬКО такая логика, никакой другой
$isB2B = $request->has('inn') && $request->has('business_card_id');
```

### Различия

| Параметр | B2C (физлицо) | B2B (юрлицо/ИП) |
|---|---|---|
| Ценообразование | Розничная цена | Оптовая цена (ниже на 15–40%) |
| Комиссия платформы | 14% | 8–12% в зависимости от tier |
| Оплата | Полная предоплата | Аванс + отсрочка 7–30 дней |
| Кредитный лимит | Отсутствует | Настраивается для каждого BusinessGroup |
| Минимальный заказ (MOQ) | 1 единица | По договору (обычно 5–50 шт) |
| Резерв товара | 20 минут (корзина) | До 7 дней (по договору) |
| API-авторизация | Bearer Sanctum Token | `X-B2B-API-Key` заголовок |
| Бонусы | Только внутри платформы | Можно выводить (Gold/Platinum tier) |
| Выплата поставщику | 4–7 рабочих дней | 7–14 рабочих дней |
| Панель управления | Личный кабинет (Livewire) | B2B Panel (Filament) + API |
| Массовые операции | Нет | Bulk-заказы, Excel-импорт |
| Отчёты | Базовые (история заказов) | Расширенные (оборот, кредит, сверка) |

### BusinessGroup — филиалы B2B

Один B2B-клиент может иметь несколько юрлиц/филиалов. Каждый — отдельная `BusinessGroup` с собственным ИНН, реквизитами, кредитным лимитом и кошельком:

```php
// Переключение между филиалами — через сессию
// Происходит в SwitchBusinessGroupMiddleware:
session(['active_business_group_id' => $group->id]);
```

Все операции внутри B2B-панели автоматически привязываются к активной `BusinessGroup`.

### B2B API

Доступ к B2B API предоставляется через API-ключи (модель `B2BApiKey`). Ключи создаются в B2B Panel, имеют гранулярные права (orders.read, orders.write, reports, stock) и expire date.

```http
# Все B2B-запросы идут через этот заголовок
X-B2B-API-Key: b2b_hX9mK2pL8nQ4jJ1r...
```

Rate limit для B2B: 500 запросов в минуту (против 100 у B2C).

---

## 🔄 ML и персонализация

### Разделение новых и постоянных пользователей

Вся ML-логика персонализации начинается с `UserBehaviorAnalyzerService::classifyUser($userId)`:

| Критерий | Новый пользователь | Постоянный пользователь |
|---|---|---|
| Возраст аккаунта | ≤ 7 дней | > 7 дней |
| Кол-во сессий | ≤ 3 | ≥ 4 |
| Сумма покупок | 0 ₽ | ≥ 1 000 ₽ |
| ML-сервис | `NewUserColdStartService` | `ReturningUserDeepProfileService` |
| Feature vector | device + geo + first actions | embeddings + taste_profile + LTV |
| Точность | ~70% (на основе похожих) | ~95% (личная история) |

**Cold Start (новый пользователь):** система смотрит на похожих пользователей в том же регионе с похожим устройством и похожим поведением в первых сессиях. Рекомендует популярное в нужном сегменте.

**Returning (постоянный):** используются embeddings поведения, история покупок, сохранённый `UserTasteProfile`, LTV-сегмент, churn risk.

### Анонимизация данных (GDPR / ФЗ-152)

Перед любой записью в ClickHouse и перед обучением ML-моделей все данные проходят через `AnonymizationService`:

- `user_id` → `sha256(user_id + salt)`. Соль меняется ежегодно.
- Точный адрес → город/регион (generalization)
- K-anonymity: минимум 5 пользователей в любой группе
- `AnnualAnonymizationJob` удаляет raw-данные старше 365 дней

```php
// Запрещено — user_id НИКОГДА не попадает в ClickHouse после 7 дней
// Разрешено — только anonymized_user_id (хеш)
$this->bigData->insertEvent([
    'anonymized_user_id' => hash('sha256', $userId . config('app.anonymization_salt')),
    'vertical' => 'beauty',
    'action' => 'view',
    // ...
]);
```

---

## 🌐 IoT-интеграция (Restaurant Vertical)

Restaurant vertical поддерживает полноценную IoT-интеграцию для умных ресторанов:

### Поддерживаемые протоколы

- **MQTT** — основной протокол для реалтайм-телеметрии (датчики температуры, весы, KDS)
- **Modbus TCP** — промышленное оборудование (умные холодильники, конвектоматы)
- **WebSocket** — реалтайм дашборды и KDS-дисплеи
- **HTTP/REST** — современные смарт-устройства

### Основные компоненты

- `IoTDevice` — модель устройства с alert_level (normal, warning, critical)
- `IoTTelemetry` — телеметрия с устройств (температура, вес, статус)
- `MqttClientService` — MQTT клиент (php-mqtt/client)
- `ModbusClientService` — Modbus TCP операции
- `IoTHubService` — оркестратор IoT-устройств
- `IoTRealTimeMonitor` — Livewire компонент для реалтайм мониторинга
- `IoTAlertTriggered` — событие для алертов

### Типы устройств

- Температурные датчики (холодильники, витрины)
- Весовые датчики (продуктовые весы)
- KDS-дисплеи (Kitchen Display System)
- Умные холодильники
- Конвектоматы и печи
- POS-терминалы

### Конфигурация

```env
# .env.example.iot
MQTT_BROKER_HOST=localhost
MQTT_BROKER_PORT=1883
MQTT_USERNAME=catvrf_iot
MQTT_PASSWORD=secret
MQTT_TLS_ENABLED=false

MODBUS_HOST=192.168.1.100
MODBUS_PORT=502

IOT_ALERTS_ENABLED=true
IOT_ALERT_WEBHOOK_URL=https://hooks.slack.com/services/...
```

**Документация:** [RESTAURANT_IOT_SETUP.md](modules/Restaurant/RESTAURANT_IOT_SETUP.md)

---

## 🛡️ Compliance-мониторинг

Система поддерживает российское законодательство в сфере персональных данных и платежей:

### Поддерживаемые федеральные законы

- **152-ФЗ** — Персональные данные (анонимизация, consent management, Roskomnadzor)
- **ФЗ-115** — AML/KYC мониторинг (risk scoring, Росфинмониторинг, подозрительные операции)
- **ФЗ-161** — Национальная платёжная система (лимиты транзакций)
- **54-ФЗ** — Онлайн-кассы (фискализация, retry логика)

### Compliance API endpoints

```text
GET    /api/compliance/personal-data/check          — проверка 152-ФЗ
GET    /api/compliance/personal-data/audit-report    — аудит-отчёт
POST   /api/compliance/pii/deletion-request          — запрос на удаление
GET    /api/compliance/aml/check                     — AML/KYC проверка
GET    /api/compliance/aml/user-checks               — проверки пользователя
POST   /api/compliance/aml/report-to-rosfinmonitoring — отчёт в Росфинмониторинг
GET    /api/compliance/fiscal/receipts               — фискальные чеки (54-ФЗ)
POST   /api/compliance/fiscal/retry                  — повторная отправка чека
```

### Доменная интеграция

ComplianceController интегрируется с доменными сервисами:
- `App\Domains\Payments\AML\AMLService` — AML/KYC логика
- `AmlCheck`, `SuspiciousOperation` — доменные модели
- `ReportToRosfinmonitoringJob` — асинхронная отправка отчётов

### Frontend компоненты

- `ComplianceDashboard.vue` — общий dashboard с compliance score
- `AMLChecksViewer.vue` — ФЗ-115 AML/KYC с фильтрацией и риск-скорингом
- `FiscalReceiptsViewer.vue` — 54-ФЗ фискальные чеки с retry
- `PaymentRulesManagement.vue` — ФЗ-161 правила транзакций

**Документация:** [docs/compliance/](docs/compliance/)

---

## 📊 BigData Observability

9-слойная архитектура мониторинга BigData-систем:

### Структура

```text
modules/BigData/
├── Domain/
│   ├── Entities/          PipelineHealth, DataFreshness, CLVModelDrift, QueryPerformance
│   ├── Enums/             MonitoringEnums (HealthStatus, FreshnessStatus, DriftStatus)
│   ├── Events/            AlertFired, PipelineStatusChanged, CLVDriftDetected
│   ├── Interfaces/        MonitoringRepositoryInterface, AlertEvaluatorInterface
│   ├── ValueObjects/      AlertResult, MetricThreshold
│   └── DTOs/
├── Application/
│   ├── Services/          BigDataMonitoringFacade, BigDataAlertEvaluator
│   ├── Jobs/              EvaluateAlertsJob, SelfHealJob, MaintenanceJob
│   └── Listeners/
├── Infrastructure/
│   ├── ClickHouse/        ClickHouseClient, ClickHouseService
│   ├── Kafka/             KafkaConsumerJob, KafkaProducerService
│   ├── Exporters/         BigDataExporter (Prometheus format)
│   ├── Repositories/      ClickHouseMonitoringRepository
│   └── Tracing/           BigDataTracingService, BigDataTracingMiddleware
└── Presentation/
    ├── Http/Controllers/  MetricsController, MonitoringController
    └── Routes/            monitoring.php
```

### API Usage

```php
BigData::monitor()->getPipelineHealth()
BigData::monitor()->getDataFreshness('seller_metrics')
BigData::monitor()->getCLVModelDrift()
BigData::monitor()->getQueryPerformance('top_products')
BigData::monitor()->alertIfLagOver('kafka.raw_events', 300)
BigData::monitor()->evaluateAlerts()
BigData::monitor()->selfHeal()
```

### BigData API endpoints

```text
GET    /metrics/bigdata                    — Prometheus scrape (no auth)
GET    /api/bigdata/monitoring/snapshot    — Full snapshot
GET    /api/bigdata/monitoring/pipeline    — Pipeline health
GET    /api/bigdata/monitoring/freshness   — Data freshness
GET    /api/bigdata/monitoring/clv-drift   — CLV drift
GET    /api/bigdata/monitoring/query-perf  — Query performance
POST   /api/bigdata/monitoring/self-heal   — Self-healing
POST   /api/bigdata/monitoring/maintenance — CH maintenance
```

### Observability Stack

- **Prometheus** — метрики (monitoring/prometheus/prometheus.yml)
- **Grafana** — дашборды (monitoring/grafana/dashboards/)
- **Alertmanager** — алерты (monitoring/alertmanager/)
- **OpenTelemetry** — распределённый трейсинг (config/otel.php)

**Документация:** [docs/BIGDATA_SRE_RUNBOOK.md](docs/BIGDATA_SRE_RUNBOOK.md)

---

## 🏢 CatCRM — CRM-система

Полноценная CRM-система для управления клиентами, лидами, сделками и аналитикой.

### Основные возможности

- **Управление клиентами** — CustomerService с полным CRUD, сегментацией, историей взаимодействий
- **Управление лидами** — автоматическое распределение лидов, lead scoring, конвертация в сделки
- **Управление сделками** — B2B сделки, интеграция с Inventory, автоматическое создание котировок
- **Интеграции** — AmoCRM, Bitrix24 через CRMIntegrationService
- **RFM-аналитика** — сегментация клиентов по Recency, Frequency, Monetary
- **Автоматизация** — авто-сегментация клиентов, автоматические отчёты
- **B2B интеграции** — инвентарь, склад, персонал, складская логистика

### CRM API endpoints

```text
GET    /api/crm/customers              — список клиентов
POST   /api/crm/customers              — создание клиента
PUT    /api/crm/customers/{id}         — обновление клиента
GET    /api/crm/leads                  — список лидов
POST   /api/crm/leads                  — создание лида
POST   /api/crm/leads/{id}/convert     — конвертация в сделку
GET    /api/crm/deals                  — список сделок
POST   /api/crm/deals                  — создание сделки
GET    /api/crm/analytics/rfm          — RFM-аналитика
POST   /api/crm/integrations/sync      — синхронизация с внешней CRM
```

### Интегрированные сервисы

- `CustomerService` — управление клиентами
- `CRMInventoryIntegrationService` — интеграция с инвентарём
- `CRMStaffIntegrationService` — интеграция с персоналом
- `CRMWarehouseIntegrationService` — интеграция со складом
- `AutoSegmentCustomersJob` — авто-сегментация
- `SendCustomerAnalyticsReportJob` — отчёты

**Документация:** [CRM_INTEGRATION_GUIDE.md](CRM_INTEGRATION_GUIDE.md), [CRM_PRODUCTION_READINESS.md](CRM_PRODUCTION_READINESS.md)

---

## 📦 Список вертикалей (38 модулей)

### Продуктовые вертикали (28)

- **BeautyMasters** — салоны красоты, парикмахерские, косметология, маникюр/педикюр, татуаж
- **Restaurant** — рестораны, кафе, бары, столовые (с IoT-интеграцией: MQTT, Modbus, датчики температуры, KDS-дисплеи)
- **Supermarket** — супермаркеты, продуктовые магазины, бакалея, свежие продукты
- **Hotels** — отели, хостелы, гостевые дома, апарт-отели, бронирование номеров
- **Taxi** — такси, каршеринг, пассажирские перевозки, геотрекинг водителей
- **RealEstate** — недвижимость, аренда и продажа жилья, коммерческая недвижимость
- **Dental** — стоматология, зубные клиники, ортодонтия, имплантация
- **VetGrooming** — груминг для животных, стрижка, уход за шерстью, купание
- **Veterinary** — ветеринарные клиники, диагностика, лечение животных
- **Fitness** — фитнес-центры, спортзалы, йога, кроссфит, персональные тренировки
- **Fashion** — мода, одежда, обувь, аксессуары, виртуальная примерка (AR)
- **Flowers** — цветочные магазины, букеты, доставка цветов, подписка на цветы
- **Media** — медиа-контент, фото- и видеосъёмка, монтаж, студии
- **Video** — видео-конференции, стриминг, онлайн-встречи, вебинары
- **Auto** — автотранспорт, аренда автомобилей, техобслуживание, автосервис
- **Marketplace** — маркетплейс, агрегатор товаров и услуг от разных продавцов
- **Loyalty** — программа лояльности, бонусы, кэшбэк, уровни участников
- **Promo** — промо-акции, скидки, купоны, специальные предложения
- **PromoCampaign** — рекламные кампании, маркетинговые акции, таргетинг
- **Contraindications** — противопоказания к услугам, медицинские ограничения
- **Recommendation** — рекомендательная система, персонализированные предложения
- **DemandForecast** — прогнозирование спроса, аналитика трендов
- **Geo** — геолокация, карты, определение местоположения, геозоны
- **Analytics** — аналитика, отчёты, дашборды, метрики бизнеса
- **BigData** — BigData мониторинг, обработка больших данных, ML-аналитика
- **FraudDetection** — антифрод, защита от мошенничества, скоринг рисков
- **AIConstructor** — AI-конструкторы для всех вертикалей (Vision API, рекомендации)
- **Core** — ядро системы, общая функциональность, базовые сущности

### Инфраструктурные модули (10)

- **CatCRM** — CRM-система, управление клиентами, лидами, сделками, интеграция с AmoCRM/Bitrix24
- **Wallet** — кошельки и балансы, транзакции, holds, выплаты
- **Payment** — платежная система, интеграция с банками (Тинькофф, СБП, Сбер), вебхуки
- **Cart** — корзина покупок, резервирование товаров, расчёт итоговой суммы
- **Inventory** — управление инвентарём, остатки, резервы, FIFO
- **Warehouse** — складская логистика, зоны, приёмка, отгрузка, инвентаризация
- **Bonuses** — бонусная система, начисление и списание бонусов, правила
- **Commissions** — комиссии, расчёт вознаграждений, выплаты поставщикам

---

## 📝 Audit Service

Централизованный аудит-логинг интегрирован в 25+ сервисов 20 вертикалей.

### Trait: WithAuditLogging

```php
use App\Traits\WithAuditLogging;

final readonly class OrderService
{
    use WithAuditLogging;

    public function create(CreateOrderDto $dto): Order
    {
        $order = Order::create($dto->toArray());

        // Логирование создания
        $this->logCreated(
            entity: Order::class,
            entityId: $order->id,
            context: [
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
                'amount' => $dto->amount,
            ]
        );

        return $order;
    }
}
```

### Методы трейта

- `logCreated()` — логирование создания сущности
- `logUpdated()` — логирование обновления
- `logDeleted()` — логирование удаления
- `logAction()` — логирование кастомных действий
- `logPayment()` — логирование платежей
- `logAuth()` — логирование аутентификации
- `generateCorrelationId()` — генерация correlation ID

### Интегрированные сервисы (25+)

- Restaurant: OrderService, ReservationService
- Payment: PaymentService (modules и app)
- Wallet: WalletService (modules и app)
- BeautyMasters: AppointmentService
- Taxi: TaxiRideService, TaxiService
- RealEstate: PropertyBookingService
- Fashion: FashionRecommendationEngineService
- Hotels: BookingService
- Loyalty: LoyaltyService
- Analytics: RFMService
- Dental: TreatmentPlanService
- Flower: DemandForecastService
- Video: VideoRoomService
- VetGrooming: GroomingService
- Media: MediaUploadService
- Fitness: MembershipService
- Inventory: FIFOShelfLifeService
- Commissions: FinancesIntegrationService
- Contraindications: ContraindicationService
- Bonuses: WalletIntegrationService
- CatCRM: CustomerService

---

## 📊 Аналитика и маркетинг

### Аналитика

`AnalyticsService` агрегирует метрики из ClickHouse через `BigDataAggregatorService`. Все метрики — tenant-aware (владелец бизнеса видит только свои данные):

- **GMV** — суммарный оборот за период
- **Orders Count** — количество заказов
- **Conversion Rate** — сессии / заказы
- **ARPU** — средняя выручка на пользователя
- **New vs Returning** — соотношение новых и постоянных
- **AI Constructor Usage** — сколько раз пользовались конструктором
- **Churn Risk** — доля пользователей с риском оттока

### Маркетинговые кампании

`MarketingCampaignService::createCampaign($dto)`:
1. `FraudControlService::check()` — бюджет не должен быть подозрительным
2. `WalletService::debit()` — рекламный бюджет списывается из кошелька тенанта
3. Создаётся Campaign с targeting JSON (UserTasteProfile + behavior patterns)
4. `AuditService::log()` — фиксируем факт создания

Targeting строится только на **обезличенных** данных: taste score, is_new_user, price_range, city_hash, device_type, LTV segment. Никаких raw user_id в рекламных отчётах.

### Рассылки

`NewsletterService` поддерживает сегментацию и 4 канала:
- **Email** — через Mailgun / SendGrid
- **Push** — через Firebase Cloud Messaging
- **SMS** — через sms.ru / Twilio
- **In-app** — через Laravel Echo (WebSocket)

A/B-тестирование тем и шаблонов встроено. Открытия и клики трекаются в `newsletter_opens` и `newsletter_clicks`.

---

## 🧪 Тесты

### Запуск тестов

```bash
# Запустить все тесты
php artisan test

# Запустить тесты отдельной вертикали
php artisan test tests/Feature/Beauty/

# Запустить с отчётом о покрытии
php artisan test --coverage tests/Feature/Beauty/

# Запустить только юнит-тесты
php artisan test tests/Unit/

# Запустить конкретный тест-кейс
php artisan test tests/Feature/Beauty/BookingTest::test_customer_can_create_booking

# Запустить параллельно (быстрее)
php artisan test --parallel
```

### Что тестируется

**Feature-тесты** покрывают полный цикл:
- Создание бронирования (Beauty): HTTP-запрос → сервис → БД → событие → ответ
- Цикл оплаты: initPayment → webhook → credit кошелька → бонусы
- Fraud-check: высокий score → блокировка → уведомление
- B2B bulk-заказ с проверкой кредитного лимита
- AI-конструктор с мок-ответом от OpenAI

**Unit-тесты** покрывают каждый сервис изолированно:
- `FraudControlServiceTest` — правила и пороги скоринга
- `WalletServiceTest` — credit, debit, hold, race conditions
- `AnonymizationServiceTest` — user_id не попадает в ClickHouse
- `BonusServiceTest` — правила начисления и лимиты

### Мок OpenAI в тестах

```php
// Не нужен реальный ключ — мокируем клиент
public function test_beauty_ai_constructor_returns_recommendations(): void
{
    $this->mock(OpenAI\Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('vision->analyze')->andReturn([
            'face_type' => 'oval',
            'skin_tone' => 'warm',
            'confidence' => 0.95,
        ]);
    });

    $response = $this->postJson('/api/v1/beauty/ai-constructor', [
        'photo' => UploadedFile::fake()->image('selfie.jpg'),
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.style_profile.face_type', 'oval')
             ->assertJsonStructure(['data' => ['recommendations', 'ar_link']]);
}
```

---

## 📏 Правила для разработчиков

Каждое из следующих правил является обязательным. Нарушение любого — мгновенный reject PR без обсуждений.

### 1. Строгая типизация

```php
<?php
// ПЕРВАЯ строка каждого PHP-файла — без исключений
declare(strict_types=1);
```

### 2. Классы — final и readonly

```php
// ✅ Правильно
final readonly class SalonService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
    ) {}
}

// ❌ Неправильно — классы не final, свойства не readonly
class SalonService
{
    protected FraudControlService $fraud;
}
```

### 3. Никаких Facades и статических вызовов

```php
// ❌ ЗАПРЕЩЕНО — reject PR
Auth::user();
Cache::get('key');
Log::info('message');
DB::table('users')->get();
response()->json([]);
request()->input('field');
config('app.name');
auth()->id();

// ✅ ТОЛЬКО constructor injection
final readonly class MyService
{
    public function __construct(
        private \Illuminate\Contracts\Auth\Guard $auth,
        private \Illuminate\Contracts\Cache\Repository $cache,
        private \Psr\Log\LoggerInterface $logger,
    ) {}
}
```

### 4. Fraud-check + транзакция перед каждой мутацией

```php
// ✅ Правильный шаблон метода с мутацией
public function create(CreateSalonDto $dto): Salon
{
    // Шаг 1: всегда проверяем на мошенничество
    $this->fraud->check($dto);

    // Шаг 2: вся мутация внутри транзакции
    return DB::transaction(function () use ($dto) {
        $salon = Salon::create($dto->toArray());

        // Шаг 3: аудит-лог после успешной операции
        $this->audit->log('salon_created', Salon::class, $salon->id,
            [], $salon->toArray()
        );

        // Шаг 4: событие для оповещения других подсистем
        event(new SalonCreatedEvent($salon, $dto->correlationId));

        return $salon;
    });
}
```

### 5. correlation_id в каждом логе и событии

```php
// ✅ Правильно
Log::channel('audit')->info('Operation completed', [
    'salon_id'       => $salon->id,
    'correlation_id' => $dto->correlationId,   // ← ОБЯЗАТЕЛЬНО
    'tenant_id'      => $dto->tenantId,         // ← ОБЯЗАТЕЛЬНО
    'user_id'        => auth()->id(),
]);

// ❌ Неправильно — нет correlation_id, нет tenant_id
Log::info('Salon created: ' . $salon->id);
```

### 6. Глобальный scope в каждой модели

```php
protected static function booted(): void
{
    // ← ОБЯЗАТЕЛЬНО в каждой модели домена
    static::addGlobalScope('tenant', function ($query) {
        $query->where('tenant_id', tenant()->id);
    });
}
```

### 7. Обязательные поля в каждой таблице мутаций

```php
// В каждой миграции таблицы с бизнес-данными:
$table->uuid('uuid')->unique();            // ← публичный идентификатор
$table->string('correlation_id')->nullable()->index(); // ← трейсинг
$table->json('tags')->nullable();          // ← гибкая категоризация
$table->foreignId('tenant_id')->constrained(); // ← изоляция тенантов
$table->foreignId('business_group_id')->nullable()->constrained(); // ← B2B
```

### 8. Запрещённые паттерны

```php
// ❌ Всё это — немедленный reject:
return null;                          // явный возврат null
throw new Exception("Not implemented"); // заглушки
// TODO: реализовать позже            // технический долг
// FIXME: здесь баг                  // игнорируемые баги
// HACK: временное решение            // костыли
public function doSomething() {}      // пустые методы
if (false) { }                        // мёртвый код
```

---

## ❓ FAQ

**Q: С чего начать, если я новый разработчик на проекте?**

A: Запусти Docker, прочитай этот README до конца. Затем открой `app/Domains/Beauty/` — это образцовая вертикаль, на неё можно ориентироваться при написании новых. Потом прочитай `.github/copilot-instructions.md` — там полный свод архитектурных правил («Библия проекта»). После этого напиши первый тест для любого существующего метода, чтобы освоиться с инструментарием.

**Q: Где находятся все архитектурные правила?**

A: В `.github/copilot-instructions.md`. Это не опциональный документ — он читается перед каждым PR.

**Q: Как добавить новую бизнес-вертикаль?**

A: Создай папку `app/Domains/{VerticalName}/` с 9 слоями по образцу `app/Domains/Beauty/`. Добавь запись в `config/verticals.php`. Создай AI-конструктор в `Services/AI/`. Добавь миграции с обязательными полями (`uuid`, `correlation_id`, `tags`, `tenant_id`, `business_group_id`). Создай маршруты в `routes/{vertical}.api.php`. Напиши Feature-тест. Создай Filament-ресурс в `Filament/Tenant/Resources/`.

**Q: Почему нельзя писать `Auth::user()` или `Cache::get()`?**

A: Facades и статические вызовы — это скрытые зависимости. Их нельзя заменить в тестах без магии. В multi-tenant окружении они могут вернуть данные не того тенанта. Только constructor injection — это позволяет тестировать каждый класс изолированно, передав мок вместо реального сервиса.

**Q: Где настраивается порог блокировки fraud?**

A: В `config/fraud.php` (жёсткие правила) и в `FraudControlService` (логика). ML-модель обучается ежедневно и хранится в `storage/models/fraud/`. Версии моделей отслеживаются в таблице `fraud_model_versions`.

**Q: Как тестировать AI-функционал без реального OpenAI-ключа?**

A: Мокируй клиент через `$this->mock(OpenAI\Client::class, ...)`. Пример есть в `tests/Feature/Beauty/AIConstructorTest.php`. В `.env.testing` ставь `OPENAI_API_KEY=fake-key` — тесты не будут делать реальные HTTP-запросы при наличии мока.

**Q: Что такое тенант и зачем он нужен?**

A: Тенант — это один изолированный бизнес на платформе. «Салон Анны» — тенант. «Ресторан Иван» — другой тенант. Они живут в одной базе данных, но не видят данные друг друга. Изоляция обеспечивается: (1) пакетом `stancl/tenancy`, (2) глобальным Eloquent-scope'ом `WHERE tenant_id = ?` в каждой модели, (3) `TenantMiddleware`, который устанавливает контекст текущего тенанта на каждый HTTP-запрос.

**Q: Можно ли использовать Filament для личного кабинета пользователя?**

A: Нет. Filament — это инструмент для административных панелей. Личные кабинеты пользователей (`/cabinet`) делаются строго на Livewire 3 + Vue 3. Это архитектурное решение, не опциональное.

**Q: Почему у каждой таблицы есть `uuid` рядом с `id`?**

A: `id` — внутренний автоинкремент для JOIN'ов и foreign keys (быстро). `uuid` — публичный идентификатор, который отдаётся в API и хранится в событиях. Это предотвращает enumeration-атаки (нельзя перебрать `id=1,2,3...` по API) и позволяет генерировать идентификаторы на стороне клиента.

**Q: Что такое `correlation_id` и зачем он в каждом логе?**

A: `correlation_id` — это уникальный UUID, который генерируется для каждого HTTP-запроса (в `CorrelationIdMiddleware`) и прокидывается через все логи, события, фоновые задачи и HTTP-ответы. Когда что-то сломается, по одному `correlation_id` можно найти все логи, все события и весь путь конкретного запроса — от входящего HTTP до записи в базу данных.

---

## 📞 Ресурсы

| Ресурс | Описание |
|---|---|
| [.github/copilot-instructions.md](.github/copilot-instructions.md) | Полный свод архитектурных правил («Библия проекта») |
| [QUICKSTART.md](QUICKSTART.md) | Быстрый старт с примерами curl-запросов |
| [START_HERE.md](START_HERE.md) | Начало работы для новых разработчиков |
| [cloud-development-setup.md](cloud-development-setup.md) | Настройка облачной разработки (Codespaces, Gitpod) |
| [docs/SECURITY.md](docs/SECURITY.md) | Политика безопасности и правила раскрытия уязвимостей |
| [docs/SECURITY_IMPLEMENTATION_GUIDE.md](docs/SECURITY_IMPLEMENTATION_GUIDE.md) | Детальное руководство по внедрению мер безопасности |
| [docs/compliance/](docs/compliance/) | Документация по compliance (152-ФЗ, ФЗ-115, ФЗ-161, 54-ФЗ) |
| [docs/BIGDATA_SRE_RUNBOOK.md](docs/BIGDATA_SRE_RUNBOOK.md) | SRE runbook для BigData мониторинга |
| [modules/Restaurant/RESTAURANT_IOT_SETUP.md](modules/Restaurant/RESTAURANT_IOT_SETUP.md) | Настройка IoT для Restaurant vertical |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Общая архитектура проекта |
| [CANONICAL_SERVICES.md](CANONICAL_SERVICES.md) | Канонические сервисы проекта |
| GitHub Issues | Баги, задачи, предложения |
| Laravel Horizon | `/horizon` — мониторинг очередей (только в production) |
| Laravel Telescope | `/telescope` — отладка запросов (только в development) |

---

<p align="center">
  Сделано с ❤️ · Laravel 11 · PHP 8.3 · 33+ вертикалей · Active Development
</p>

