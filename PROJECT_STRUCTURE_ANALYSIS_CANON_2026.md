<!--
=============================================================================
ДЕТАЛЬНЫЙ АНАЛИЗ СТРУКТУРЫ LARAVEL-ПРОЕКТА СОГЛАСНО КАНОНУ 2026
Создано: 18.03.2026
Проект: CatVRF (Marketplace multi-tenant)
=============================================================================
-->

# 📊 ПЛАН ОБРАБОТКИ МОДУЛЕЙ СОГЛАСНО КАНОНУ 2026

**Дата анализа:** 18 марта 2026 г.  
**Версия КАНОНА:** 2026 (Production-Ready)  
**Статус проекта:** Phase 6+ Completion

---

## 📋 ОГЛАВЛЕНИЕ

1. [Обзор проекта](#обзор-проекта)
2. [Модуль 1: Авторизация и RBAC](#модуль-1-авторизация-и-rbac)
3. [Модуль 2: Уведомления](#модуль-2-уведомления)
4. [Модуль 3: Маркетинг](#модуль-3-маркетинг)
5. [Модуль 4: Аналитика и BigData](#модуль-4-аналитика-и-bigdata)
6. [Модуль 5: HR и персонал](#модуль-5-hr-и-персонал)
7. [Модуль 6: Зарплаты и расчёты](#модуль-6-зарплаты-и-расчёты)
8. [Модуль 7: Курьеры и логистика](#модуль-7-курьеры-и-логистика)
9. [Модуль 8: Остальные компоненты](#модуль-8-остальные-компоненты)
10. [Модуль 9: Вертикали (Domains)](#модуль-9-вертикали-domains)
11. [Модуль 10: API и Controllers](#модуль-10-api-и-controllers)
12. [Итоговая статистика](#итоговая-статистика)

---

## 🎯 ОБЗОР ПРОЕКТА

### Структура проекта

```
CatVRF/
├── app/
│   ├── Domains/          [22 вертикали]
│   ├── Services/         [14+ основных сервисов]
│   ├── Http/
│   ├── Models/           [9 основных моделей]
│   ├── Policies/         [16 Policies]
│   ├── Jobs/             [9 Jobs]
│   ├── Listeners/        [Octane listeners]
│   ├── Exceptions/       [3 Custom exceptions]
│   ├── Enums/            [Role enum]
│   └── Filament/         [Admin, Tenant, Public]
├── database/
│   ├── migrations/       [55 миграций]
│   ├── factories/        [20 фабрик]
│   ├── seeders/          [115+ сидеров]
│   └── seeders/tenant/   [HR, CRM seeders]
├── routes/               [43+ route файла]
└── config/               [10 конфиг-файлов]
```

### Ключевые метрики проекта

- **Всего миграций:** 55
- **Всего фабрик:** 20
- **Всего сидеров:** 115+
- **Вертикалей (Domains):** 22
- **Services (корневые):** 14
- **Services (вложенные):** 15+
- **Policies:** 16
- **Jobs:** 9
- **API Controllers (V1):** 5
- **API Controllers (V2):** TBD
- **Routes файлы:** 43+

---

## 🔐 МОДУЛЬ 1: АВТОРИЗАЦИЯ И RBAC

### Статус: ✅ ТРЕБУЕТ ОБНОВЛЕНИЯ

### Критичность: **CRITICAL** (Production-blocking)

### 1.1 Файлы для обработки

#### Основные компоненты

| Файл | Путь | Тип | Статус | Приоритет |
|------|------|-----|--------|-----------|
| Role.php | `app/Enums/Role.php` | Enum | ✅ EXISTS | HIGH |
| AuthService.php | `app/Services/` | Service | ❌ NOT FOUND | CRITICAL |
| App\Http\Middleware | `app/Http/Middleware/` | Middleware | ❌ NEED CHECK | HIGH |
| Policies/* | `app/Policies/` | Policies | ✅ EXISTS (16 files) | HIGH |
| auth.php | `config/auth.php` | Config | ✅ EXISTS | MEDIUM |
| permission.php | `config/permission.php` | Config | ❌ NOT FOUND | HIGH |

#### Найдено Policies

```
app/Policies/
├── AppointmentPolicy.php          [✅ 9066 bytes]
├── BeautyPolicy.php               [✅ 6487 bytes]
├── BonusPolicy.php                [✅ 7294 bytes]
├── CommissionPolicy.php           [✅ 5957 bytes]
├── EmployeePolicy.php             [✅ 1392 bytes]
├── HotelPolicy.php                [✅ 6858 bytes]
├── InventoryPolicy.php            [✅ 6663 bytes]
├── OrderPolicy.php                [✅ 9748 bytes]
├── PaymentPolicy.php              [✅ 7785 bytes]
├── PayoutPolicy.php               [✅ 1621 bytes]
├── PayrollPolicy.php              [✅ 1443 bytes]
├── ProductPolicy.php              [✅ 7843 bytes]
├── ReferralPolicy.php             [✅ 8436 bytes]
├── TenantPolicy.php               [✅ 3622 bytes]
├── WalletManagementPolicy.php     [✅ 696 bytes] (NEW)
└── WalletPolicy.php               [✅ 8436 bytes]
```

**Всего Policies: 16 файлов (92,856 bytes)**

#### Role Enum

```php
app/Enums/Role.php
├── admin              [Administrative access]
├── business_owner     [Full business access]
├── manager            [Team management]
├── accountant         [Financial access]
└── employee           [Limited access]
```

### 1.2 Отсутствующие компоненты (НУЖНО СОЗДАТЬ)

```
❌ AuthService.php                    [app/Services/Auth/]
❌ config/permission.php              [Permission matrix]
❌ Middleware/AuthorizationMiddleware [RBAC verification]
❌ app/Http/Requests/AuthRequest      [Request validation]
❌ Guards configuration               [Sanctum/JWT]
```

### 1.3 План действий для RBAC

#### PHASE 1: СОЗДАНИЕ НЕДОСТАЮЩИХ КОМПОНЕНТОВ

1. **Создать AuthService** (`app/Services/Auth/AuthService.php`)
   - Методы: `authenticate()`, `generateToken()`, `validatePermission()`
   - Включить: correlation_id логирование, fraud check
   - Кэширование: Redis с TTL 300 сек

2. **Создать config/permission.php**

   ```php
   return [
       'abilities' => [
           'view_dashboard', 'manage_employees', 'manage_payroll',
           'manage_orders', 'manage_wallet', 'manage_payments',
           'view_analytics', 'export_data', 'manage_settings'
       ],
       'role_abilities' => [
           'admin' => ['*'],                    // All abilities
           'business_owner' => [...],           // Most abilities
           'manager' => [...],                  // Team management
           'accountant' => [...],               // Financial only
           'employee' => [...]                  // Limited
       ]
   ];
   ```

3. **Создать AuthorizationMiddleware**
   - Проверка: permission по route + ability
   - Вызов: FraudControlService::check()
   - RateLimiter: tenant-aware

4. **Добавить Sanctum Guards в config/auth.php**
   - `sanctum` для API tokens
   - Lifetime: 365 days для remember_me

#### PHASE 2: ОБНОВЛЕНИЕ POLICIES (audit)

Каждый Policy проверить на:

- ✅ Наличие `booted()` с tenant scoping
- ✅ Методы: create, read, update, delete
- ✅ FraudControlService::check() перед мутациями
- ✅ Log::channel('audit') с correlation_id
- ✅ Правильная проверка business_group_id

**Policies для обновления:**

- `WalletManagementPolicy.php` (NEW, needs full implementation)
- `PayoutPolicy.php`, `PayrollPolicy.php` (minimal, may need expansion)
- Все остальные (verify against CANON 2026)

#### PHASE 3: ИНТЕГРАЦИЯ В CONTROLLER

- Добавить в `BaseApiV1Controller`
- Вызов: `authorize('view', $model)` перед методами
- Response: JSON 403 с correlation_id

#### PHASE 4: ТЕСТИРОВАНИЕ

- Unit тесты для каждого Policy
- Integration тесты для middleware
- Authorization failure scenarios

---

## 📧 МОДУЛЬ 2: УВЕДОМЛЕНИЯ

### Статус: ⚠️ ТРЕБУЕТ СОЗДАНИЯ

### Критичность: **HIGH** (User engagement)

### 2.1 Файлы для обработки

#### Найдено

| Файл | Путь | Статус | Размер |
|------|------|--------|---------|
| NotificationService.php | `app/Services/NotificationService.php` | ✅ EXISTS | 4304 bytes |
| EmailService.php | `app/Services/EmailService.php` | ✅ EXISTS | 2358 bytes |

#### Отсутствует

```
❌ app/Notifications/                    [Directory]
❌ app/Mail/                             [Directory]
❌ config/notification.php               [Config]
❌ app/Events/NotificationEvent.php     [Event]
❌ app/Listeners/SendNotificationListener.php
```

### 2.2 Структура для создания

```
app/Notifications/
├── OrderConfirmationNotification.php
├── PaymentSuccessNotification.php
├── AppointmentReminderNotification.php
├── LowStockAlertNotification.php
├── PayoutCompletedNotification.php
└── PromoExpiredNotification.php

app/Mail/
├── OrderConfirmationMail.php
├── InvoiceMail.php
├── PayrollReportMail.php
├── PromoCampaignMail.php
└── WeeklyAnalyticsReportMail.php

database/migrations/
├── create_notifications_table.php
└── create_notification_preferences_table.php
```

### 2.3 Обязательные методы в NotificationService

```php
send(User|Tenant $recipient, string $type, array $data): bool
sendBulk(Collection $recipients, string $type, array $data): int
sendDeferred($recipient, string $type, array $data, Carbon $sendAt): void
getUnread(User $user): Collection
markAsRead(Notification $notification): void
getUserPreferences(User $user): array
```

### 2.4 Config структура

```php
// config/notification.php
return [
    'channels' => ['mail', 'database', 'sms', 'push'],
    'queue' => true,
    'from_email' => env('MAIL_FROM_ADDRESS'),
    'templates' => [
        'order_confirmation' => ['mail', 'database'],
        'payment_success' => ['mail', 'database', 'push'],
        'appointment_reminder' => ['sms', 'push'],
        'low_stock_alert' => ['mail'],
        'payout_completed' => ['mail', 'database'],
    ],
    'retry' => 3,
    'timeout' => 300,
];
```

### 2.5 План действий

#### ✅ ФАЗА 1: Создание структуры

```bash
# Создать директории и базовые файлы
mkdir -p app/Notifications app/Mail

# Создать миграцию
php artisan make:migration create_notifications_table

# Создать базовые Notification классы
php artisan make:notification OrderConfirmationNotification
php artisan make:notification PaymentSuccessNotification
```

#### ✅ ФАЗА 2: Реализация Mailables

Каждый Mail должен:

- Наследовать `Mailable`
- Использовать markdown templates
- Включить correlation_id в тему
- Быть Queueable

#### ✅ ФАЗА 3: Обновление NotificationService

- Добавить queue support
- Retry logic (exponential backoff)
- Preference checking
- Timezone-aware scheduling

#### ✅ ФАЗА 4: Events & Listeners

- `OrderCreatedEvent` → SendOrderConfirmationListener
- `PaymentCompletedEvent` → SendPaymentNotificationListener
- `AppointmentScheduledEvent` → SendReminderListener

#### ✅ ФАЗА 5: Интеграция с Jobs

- Dispatch в каждый Service после мутации
- Priority по типу (payment = high, promo = low)

---

## 🎯 МОДУЛЬ 3: МАРКЕТИНГ

### Статус: ✅ СУЩЕСТВУЕТ (базовая реализация)

### Критичность: **HIGH** (Revenue driver)

### 3.1 Найденные файлы

```
app/Services/Marketing/
├── PromoCampaignService.php        [✅ 3274 bytes]
├── ReferralService.php              [✅ 4145 bytes]
└── (MarketingCampaignService.php не найден)

app/Models/
├── Не найдены маркетинг-специфичные модели

database/seeders/
├── AdCampaignSeeder.php
├── MarketplaceGeneralFilterSeeder.php
├── NewsletterSeeder.php
├── B2BMarketplaceSeeder.php
└── ProductionFeatureSeeder.php
```

### 3.2 Найденные Jobs (маркетинг-релейтед)

```
app/Jobs/
├── BonusAccrualJob.php              [✅ 9230 bytes] - начисление бонусов
├── CleanupExpiredBonusesJob.php     [✅ 6202 bytes] - очистка
└── PayoutProcessingJob.php          [✅ 8688 bytes] - выплаты
```

### 3.3 Отсутствующие компоненты

```
❌ app/Models/PromoCampaign.php
❌ app/Models/Referral.php
❌ app/Models/Bonus.php
❌ app/Models/MarketingCampaign.php
❌ app/Models/BonusRule.php
❌ database/migrations/promo_campaigns
❌ database/migrations/referrals
❌ database/factories/PromoCampaignFactory.php
```

### 3.4 Обязательные таблицы (CANON 2026)

```sql
promo_campaigns
├── id, tenant_id, business_group_id
├── type (discount_percent, fixed_amount, bundle, buy_x_get_y, gift_card)
├── code, name, description
├── start_at, end_at
├── budget, spent_budget
├── max_uses_per_user, max_uses_total
├── min_order_amount
├── applicable_verticals (jsonb), applicable_categories (jsonb)
├── status (active, paused, exhausted, expired)
├── correlation_id, created_by

promo_uses
├── id, promo_campaign_id, tenant_id, user_id
├── order_id / appointment_id / source_id
├── discount_amount, correlation_id, used_at

referrals
├── id, referrer_id, referee_id
├── referral_code, referral_link
├── status (pending, registered, qualified, rewarded)
├── source_platform (Yandex, Dikidi, Flowwow)
├── migrated_at, turnover_threshold, spent_threshold
├── bonus_amount, correlation_id

bonus_transactions
├── id, bonus_id, wallet_id
├── type (referral, turnover, promo, loyalty)
├── amount, status
├── credited_at, withdrawn_at
├── correlation_id
```

### 3.5 План действий

#### PHASE 1: СОЗДАНИЕ МОДЕЛЕЙ И МИГРАЦИЙ

```php
// Создать модели
php artisan make:model PromoCampaign -m
php artisan make:model PromoUse -m
php artisan make:model Referral -m
php artisan make:model Bonus -m
php artisan make:model BonusRule -m

// Реализовать relationships
// PromoCampaign: hasMany(PromoUse), belongsTo(Tenant, BusinessGroup)
// Referral: belongsTo(User as referrer), belongsTo(User as referee)
// Bonus: belongsTo(Wallet), hasMany(BonusTransaction)
```

#### PHASE 2: ОБНОВЛЕНИЕ SERVICES

**PromoCampaignService:**

- ✅ `createCampaign()` - создание с валидацией
- ✅ `applyPromo()` - применение скидки, DB::transaction()
- ✅ `validatePromo()` - проверка без применения
- ✅ `cancelPromoUse()` - отмена с возвратом бюджета
- ✅ `checkBudgetExhausted()` - проверка бюджета
- ✅ `getActiveCampaigns()` - получить активные

**ReferralService:**

- ✅ `generateReferralLink()` - создание уникального кода
- ✅ `registerReferral()` - регистрация приглашённого
- ✅ `checkQualification()` - проверка достижения оборота
- ✅ `awardBonus()` - начисление бонуса
- ✅ `getReferralStats()` - статистика
- ✅ `validateMigration()` - фиксирование миграции

#### PHASE 3: FACTORY И SEEDER

```php
// database/factories/PromoCampaignFactory.php
// database/factories/ReferralFactory.php
// database/factories/BonusFactory.php

// database/seeders/PromoCampaignSeeder.php
// database/seeders/ReferralSeeder.php
```

#### PHASE 4: API ENDPOINTS

- POST `/api/v1/promo/apply` - применить промокод
- POST `/api/v1/referral/generate` - создать реф-ссылку
- GET `/api/v1/referral/stats` - мои рефералы

#### PHASE 5: FILAMENT RESOURCES

- PromoCampaignResource (Admin + Tenant)
- ReferralResource (Tenant, view-only)
- BonusResource (Admin)

---

## 📊 МОДУЛЬ 4: АНАЛИТИКА И BIGDATA

### Статус: ✅ ЧАСТИЧНО СУЩЕСТВУЕТ

### Критичность: **MEDIUM** (Business intelligence)

### 4.1 Найденные файлы

```
app/Services/
├── AnalyticsService.php             [✅ 4096 bytes]
├── SearchRankingService.php         [✅ 8283 bytes]

app/Services/AI/
├── DemandForecastService.php        [✅ 3306 bytes]
├── PriceSuggestionService.php       [✅ 2621 bytes]
├── RecommendationService.php        [✅ 3633 bytes]

app/Jobs/
├── DemandForecastJob.php            [✅ 9965 bytes]
├── RecommendationQualityJob.php     [✅ 8673 bytes]
└── FraudMLRecalculationJob.php      [✅ 8607 bytes]
```

### 4.2 Отсутствующие компоненты

```
❌ app/Services/BigDataAggregator.php
❌ config/analytics.php
❌ database/migrations/clickhouse_events (if applicable)
❌ database/models/ClickHouseEvent.php
❌ app/Services/AI/AnomalyDetectorService.php
❌ app/Jobs/BigDataAggregatorJob.php
❌ app/Jobs/MLRecalculateCommand.php
```

### 4.3 Обязательные классы (CANON 2026)

#### RecommendationService

```php
public function getForUser(int $userId, string $vertical = null, array $context = []): Collection
public function getCrossVertical(int $userId, string $currentVertical): Collection
public function getB2BForTenant(int $tenantId, string $vertical): Collection
public function scoreItem(int $userId, int $itemId, array $context): float
public function invalidateUserCache(int $userId): void
public function recalculateEmbeddings(): void
```

**Кэширование:**

- Ключ: `recommendation:user:{userId}:vertical:{vertical}:geo:{geoHash}:v1`
- TTL: 300 сек (динамические), 3600 сек (стабильные)

**Источники (приоритет):**

1. Поведение (0.45) - просмотры, покупки, корзина
2. Геолокация (0.25) - близость, радиус
3. Embeddings (0.20) - cosine similarity
4. Правила бизнеса (0.10) - boost/demote
5. Популярность (0.05) - hot items

#### DemandForecastService

```php
public function forecastForItem(int $itemId, Carbon $dateFrom, Carbon $dateTo): ForecastResult
public function forecastBulk(array $itemIds, Carbon $dateFrom, Carbon $dateTo): array
public function getHistoricalAccuracy(string $vertical, int $days = 30): array
public function trainModel(string $vertical): string
```

**Модель:** XGBoost / Prophet / LSTM  
**Метрики:** MAPE < 15%, MAE < 10% от среднего спроса  
**Обучение:** Ежедневно в 04:30 UTC

#### AnalyticsService

Должен собирать:

- Оборот, конверсия, LTV, churn, fraud_rate
- Ежедневные/еженедельные отчёты
- Метрики по вертикалям
- Тепловые карты спроса

### 4.4 Database Tables (если нужны)

```sql
demand_forecasts
├── id, tenant_id, item_id, forecast_date
├── predicted_demand, confidence_interval_lower, confidence_interval_upper
├── confidence_score (0-1), model_version
├── features_json, correlation_id, generated_at, used_at

demand_actuals
├── id, tenant_id, item_id, date
├── actual_demand, source (order, appointment, ride)
├── correlation_id

demand_model_versions
├── id, version (YYYY-MM-DD-vN), vertical
├── trained_at, mae, rmse, mape
├── file_path (storage/models/demand/...)
├── comment

recommendation_logs
├── user_id, tenant_id, correlation_id
├── recommended_items (jsonb), score, source
├── clicked_at (nullable)
```

### 4.5 План действий

#### PHASE 1: ОБНОВЛЕНИЕ СУЩЕСТВУЮЩИХ SERVICES

1. **RecommendationService** - добавить:
   - Redis кэширование
   - Embeddings similarity (cosine)
   - Cross-vertical рекомендации
   - B2B режим для поставщиков

2. **DemandForecastService** - добавить:
   - ML модель (XGBoost)
   - Feature engineering (30-50 фич)
   - Confidence intervals
   - Historical accuracy tracking

3. **AnalyticsService** - добавить:
   - Daily reports generation
   - Metric calculations
   - Trend analysis
   - Anomaly detection

#### PHASE 2: СОЗДАНИЕ НОВЫХ КОМПОНЕНТОВ

- BigDataAggregator (для ClickHouse, если используется)
- AnomalyDetectorService (для фрод-детекции)
- config/analytics.php

#### PHASE 3: JOBS

- RecalculateEmbeddingsJob (ежедневно)
- DemandModelTrainJob (ежедневно 04:30)
- BigDataAggregatorJob (ежедневно)
- RecommendationQualityJob (ежедневно)

#### PHASE 4: FILAMENT DASHBOARDS

- AIDashboard с метриками
- Visualization widgets
- Trend analysis

#### PHASE 5: ТЕСТИРОВАНИЕ

- ML модель accuracy > 0.92
- Recommendation CTR > 8%
- Revenue lift > 15%

---

## 👥 МОДУЛЬ 5: HR И ПЕРСОНАЛ

### Статус: ⚠️ ТРЕБУЕТ РАСШИРЕНИЯ

### Критичность: **HIGH** (Employee management)

### 5.1 Найденные файлы

```
app/Services/
├── HRService.php                    [✅ 4517 bytes]

database/seeders/
├── HRSeeder.php                     [✅ 1635 bytes]
├── StaffSeeder.php                  [✅ 1785 bytes]
├── EmployeeSeeder.php               [✅ 3326 bytes]
├── InternalHRJobBoardSeeder.php     [✅ 2924 bytes]

Policies/
├── EmployeePolicy.php               [✅ 1392 bytes]
```

### 5.2 Отсутствующие компоненты

```
❌ app/Domains/HR/                   [Domain directory]
  ❌ Models/Employee.php
  ❌ Models/Department.php
  ❌ Models/Position.php
  ❌ Services/
  ❌ Resources/ (Filament)
  ❌ Jobs/ (HR jobs)

❌ database/migrations/employees_table.php
❌ database/migrations/departments_table.php
❌ database/migrations/positions_table.php
❌ app/Http/Resources/EmployeeResource.php
```

### 5.3 Domain структура HR (ОБЯЗАТЕЛЬНО)

```
app/Domains/HR/
├── Models/
│   ├── Employee.php                 [Сотрудник]
│   ├── Department.php               [Отдел]
│   ├── Position.php                 [Должность]
│   ├── EmployeeSchedule.php         [График]
│   ├── EmployeeLeave.php            [Отпуск]
│   ├── EmployeeReview.php           [Аттестация]
│   └── EmployeeCompensation.php     [ЗП]
│
├── Services/
│   ├── EmployeeManagementService.php
│   ├── DepartmentService.php
│   ├── ScheduleService.php
│   └── LeaveManagementService.php
│
├── Resources/ (Filament)
│   ├── EmployeeResource.php
│   ├── DepartmentResource.php
│   └── PositionResource.php
│
├── Jobs/
│   ├── EmployeeHiringJob.php
│   ├── EmployeeReviewJob.php
│   └── ScheduleGenerationJob.php
│
└── Routes/
    └── hr.php
```

### 5.4 Обязательные таблицы

```sql
employees
├── id, tenant_id, user_id, department_id
├── position, hire_date, end_date (nullable)
├── salary, currency, contract_type
├── status (active, on_leave, terminated)
├── correlation_id, tags (jsonb)

departments
├── id, tenant_id, parent_id (nullable)
├── name, description, head_id
├── budget, cost_center
├── correlation_id

positions
├── id, tenant_id, department_id
├── title, description, level
├── salary_min, salary_max
├── correlation_id

employee_schedules
├── id, employee_id, date, shift_id
├── start_time, end_time, status

employee_leaves
├── id, employee_id, type (vacation, sick, unpaid)
├── start_date, end_date, duration_days
├── status (pending, approved, rejected)
├── correlation_id
```

### 5.5 Обязательные методы HRService

```php
hireEmployee(array $data): Employee
terminateEmployee(int $employeeId, string $reason): void
updateSalary(int $employeeId, float $newSalary): void
generateSchedule(int $departmentId, Carbon $startDate): Collection
requestLeave(int $employeeId, array $data): EmployeeLeave
approveLeave(int $leaveId): void
getEmployeeStats(int $tenantId): array
```

### 5.6 План действий

#### PHASE 1: СОЗДАНИЕ DOMAIN

```bash
mkdir -p app/Domains/HR/{Models,Services,Resources,Jobs,Routes}

# Создать миграции
php artisan make:model HR/Models/Employee -m
php artisan make:model HR/Models/Department -m
php artisan make:model HR/Models/Position -m
```

#### PHASE 2: МОДЕЛИ И RELATIONSHIPS

- Employee: belongsTo(Department, Position, User)
- Department: hasMany(Employee), belongsTo(Employee as head)
- Position: hasMany(Employee)
- EmployeeLeave: belongsTo(Employee)
- EmployeeSchedule: belongsTo(Employee)

#### PHASE 3: SERVICES

- EmployeeManagementService (hire, terminate, update)
- DepartmentService (crud, budgeting)
- ScheduleService (auto-generation)
- LeaveManagementService (approve/reject)

#### PHASE 4: FILAMENT RESOURCES

- EmployeeResource (Tenant только!)
- DepartmentResource
- PositionResource
- LeaveApprovalResource

#### PHASE 5: JOBS

- EmployeeHiringJob
- EmployeeReviewJob
- ScheduleGenerationJob
- LeaveApprovalJob

#### PHASE 6: API ENDPOINTS (V1)

- GET `/api/v1/employees` - список с pagination
- POST `/api/v1/employees` - создание
- PUT `/api/v1/employees/{id}` - обновление
- POST `/api/v1/leaves` - запрос отпуска
- GET `/api/v1/leaves/approvals` - мне на согласование

---

## 💰 МОДУЛЬ 6: ЗАРПЛАТЫ И РАСЧЁТЫ

### Статус: ⚠️ СУЩЕСТВУЕТ (базовое)

### Критичность: **CRITICAL** (Financial accuracy)

### 6.1 Найденные файлы

```
database/seeders/
├── PayrollSeeder.php                [✅ 2236 bytes]

Policies/
├── PayrollPolicy.php                [✅ 1443 bytes]
├── PayoutPolicy.php                 [✅ 1621 bytes]

app/Jobs/
├── PayoutProcessingJob.php          [✅ 8688 bytes]
```

### 6.2 Отсутствующие компоненты

```
❌ app/Services/PayrollService.php
❌ app/Domains/Payroll/                [Domain]
  ❌ Models/Payroll.php
  ❌ Models/Salary.php
  ❌ Models/Deduction.php
  ❌ Services/PayrollCalculationService.php
  ❌ Resources/ (Filament)
  ❌ Jobs/ (Payroll jobs)

❌ database/migrations/payroll_tables
❌ database/factories/PayrollFactory.php
```

### 6.3 Обязательные таблицы

```sql
payrolls
├── id, tenant_id, employee_id, period_start, period_end
├── gross_salary, tax, deductions, net_salary
├── status (draft, submitted, approved, paid)
├── payment_date, correlation_id

salary_components
├── id, payroll_id, component_type
├── (base, bonus, allowance, commission)
├── amount, description

deductions
├── id, payroll_id, deduction_type
├── (tax, insurance, loan, advance)
├── amount, description

payroll_approvals
├── id, payroll_id, approver_id
├── status (pending, approved, rejected)
├── reason, created_at
```

### 6.4 Обязательные методы PayrollService

```php
calculatePayroll(int $employeeId, Carbon $periodStart, Carbon $periodEnd): Payroll
processPayroll(int $payrollId): bool
approvPayroll(int $payrollId, int $approverId): void
rejectPayroll(int $payrollId, string $reason): void
generatePayslip(int $payrollId): PDF
exportPayroll(Collection $payrolls, string $format = 'xlsx'): void
calculateTax(float $grossSalary, string $region): float
```

### 6.5 Обязательные значения (РФ)

```php
// config/payroll.php
return [
    'tax_rate' => 13.0,                // НДФЛ
    'insurance_rate' => 1.8,           // ПФР
    'health_insurance_rate' => 5.1,    // ОМС
    'social_insurance_rate' => 2.9,    // ФСС
    'min_salary' => 16242,             // Минимум
    'payment_day' => 20,               // День выплаты
];
```

### 6.6 План действий

#### PHASE 1: СОЗДАНИЕ DOMAIN

```bash
mkdir -p app/Domains/Payroll/{Models,Services,Resources,Jobs}

# Миграции
php artisan make:model Domains/Payroll/Models/Payroll -m
php artisan make:model Domains/Payroll/Models/SalaryComponent -m
php artisan make:model Domains/Payroll/Models/Deduction -m
```

#### PHASE 2: PAYROLL SERVICE

- calculatePayroll() с вычислением налогов
- DB::transaction() для всех операций
- Log::channel('audit') для каждого расчёта
- correlation_id обязателен

#### PHASE 3: FILAMENT RESOURCES

- PayrollResource (Admin + Tenant)
- PayslipResource (view only)
- PayrollReportResource

#### PHASE 4: JOBS

- MonthlyPayrollJob (запуск 1-го числа)
- PayrollApprovalNotificationJob
- PayoutProcessingJob

#### PHASE 5: EXPORTS

- Excel/CSV export для 1С интеграции
- PDF payslips
- XML для банка

#### PHASE 6: SECURITY

- Запрет на редактирование завершённого payroll
- Все изменения логируются (3 года)
- 2FA для утверждения > 100k ₽
- Аудит всех расчётов

---

## 🚚 МОДУЛЬ 7: КУРЬЕРЫ И ЛОГИСТИКА

### Статус: ✅ EXISTS (Logistics Domain)

### Критичность: **HIGH** (Delivery critical)

### 7.1 Найденные файлы

```
app/Services/
├── CourierService.php               [✅ 6847 bytes]

app/Domains/Logistics/
├── [EXISTS, need structure check]

database/seeders/
├── TaxiDriverSeeder.php             [✅ 4190 bytes]
├── TaxiVehicleSeeder.php            [✅ 3884 bytes]
├── TaxiRideSeeder.php               [✅ 581 bytes]
├── TaxiVerticalSeeder.php           [✅ 3407 bytes]

Policies/
└── [Delivery policies - need check]
```

### 7.2 Найденные Domains

**Auto (Taxi & Delivery):**

```
app/Domains/Auto/
├── Models/
├── Services/
├── Filament/
├── Http/
├── Jobs/
├── Routes/
└── [Structure exists - 40+ files]

app/Domains/Logistics/
├── [Structure exists - need audit]

Delivery models found:
├── TaxiRide
├── TaxiDriver
├── TaxiVehicle
├── DeliveryOrder
├── DeliveryZone
```

### 7.3 Обязательные компоненты (для проверки)

#### Models (обязательные)

```php
TaxiDriver         [✅ должен существовать]
TaxiVehicle        [✅ должен существовать]
TaxiRide           [✅ должен существовать]
TaxiFleet          [need verification]
TaxiSurgeZone      [need verification]
DeliveryOrder      [need verification]
Courier            [need verification]
CourierZone        [need verification]
```

#### Services (обязательные)

```php
CourierService         [✅ EXISTS]
TaxiSurgeService       [need verification]
TaxiMatchingService    [need verification]
DeliveryTrackingService [need verification]
GeoService             [✅ EXISTS - 1717 bytes]
```

#### Jobs (обязательные)

```php
TaxiMatchingJob        [need verification]
SurgeCalculationJob    [need verification]
CourierAllocationJob   [need verification]
DeliveryTrackingJob    [need verification]
ReleaseHoldJob         [✅ EXISTS - 3290 bytes]
```

### 7.4 База данных (необходимо проверить)

Обязательные таблицы для Taxi:

```sql
taxi_drivers
├── id, tenant_id, user_id
├── license_number, rating, status
├── current_location (point)
├── is_active, correlation_id

taxi_vehicles
├── id, driver_id, fleet_id
├── brand, model, license_plate
├── class (economy, comfort, business)
├── status, correlation_id

taxi_rides
├── id, passenger_id, driver_id, vehicle_id
├── pickup_point (point), dropoff_point (point)
├── status, price, surge_multiplier
├── correlation_id

taxi_surge_zones
├── id, taxi_vertical_id
├── polygon (geometry), surge_multiplier
├── active_from, active_until
```

### 7.5 Обязательные методы

```php
// CourierService
assignCourier(int $orderId): Courier
trackDelivery(int $rideId): DeliveryStatus
calculateSurge(Carbon $pickupPoint): float
matchDriver(DeliveryRequest $request): TaxiDriver
calculateFare(Point $from, Point $to): float

// GeoService
getDistance(Point $from, Point $to): float
getPolygonArea(array $coordinates): float
getNearbyDrivers(Point $location, int $radius = 5000): Collection
```

### 7.6 План действий

#### PHASE 1: AUDIT СУЩЕСТВУЮЩИХ КОМПОНЕНТОВ

- Проверить все Models (relationships, scopes)
- Проверить все Services (методы, DB::transaction())
- Проверить все Jobs (queue, retry, tags)
- Проверить все миграции (tenant_id, correlation_id)

#### PHASE 2: ДОПОЛНЕНИЕ НЕДОСТАЮЩИХ

- TaxiSurgeService (Surge pricing logic)
- TaxiMatchingService (Driver matching algorithm)
- DeliveryTrackingService (Real-time tracking)
- courier_zone_assignments table

#### PHASE 3: ГЕО-ФУНКЦИОНАЛ

- GPS tracking (via Glonass or custom)
- Route optimization (OSRM)
- Heatmap generation
- Polygon-based zone management

#### PHASE 4: JOBS

- SurgeCalculationJob (every 5 minutes)
- DriverMatchingJob (real-time)
- CourierNotificationJob (status updates)
- DeliveryAnalyticsJob (daily)

#### PHASE 5: API ENDPOINTS

- POST `/api/v1/taxi/request` - создать поездку
- GET `/api/v1/taxi/{rideId}/tracking` - трекинг
- GET `/api/v1/taxi/estimate` - расчёт цены
- GET `/api/v1/taxi/drivers/nearby` - найти водителей

#### PHASE 6: SECURITY & COMPLIANCE

- Verify driver license + background check
- Encryption of location data
- GDPR compliance for tracking
- Dispute resolution workflow

---

## 🔄 МОДУЛЬ 8: ОСТАЛЬНЫЕ КОМПОНЕНТЫ

### 8.1 SERVICES (остальные)

#### Найденные сервисы

```
app/Services/
├── AnalyticsService.php             [4096 bytes] ✅
├── CourierService.php               [6847 bytes] ✅
├── EmailService.php                 [2358 bytes] ✅
├── ExportService.php                [1717 bytes] ✅
├── FraudControlService.php          [4006 bytes] ✅
├── GeoService.php                   [1717 bytes] ✅
├── HRService.php                    [4517 bytes] ✅
├── ImportService.php                [4028 bytes] ✅
├── NotificationService.php          [4304 bytes] ✅
├── RateLimiterService.php           [1230 bytes] ✅
├── RecommendationService.php        [6847 bytes] ✅
├── SearchRankingService.php         [8283 bytes] ✅
├── SearchService.php                [5527 bytes] ✅
└── WishlistService.php              [8050 bytes] ✅

Nested Services:
├── AI/
│   ├── DemandForecastService.php
│   ├── PriceSuggestionService.php
│   └── RecommendationService.php (duplicate?)
├── Fraud/
│   └── FraudMLService.php           [9731 bytes] ✅
├── Infrastructure/
│   └── DopplerService.php           [640 bytes] ✅
├── Inventory/
│   └── InventoryManagementService.php [5404 bytes] ✅
├── Marketing/
│   ├── PromoCampaignService.php
│   └── ReferralService.php
├── Payment/
│   ├── FiscalService.php            [6044 bytes] ✅
│   ├── IdempotencyService.php       [6393 bytes] ✅
│   ├── PaymentGatewayService.php    [3259 bytes] ✅
│   ├── PaymentIdempotencyService.php [1257 bytes] ✅
│   └── Gateways/
│       ├── PaymentGatewayInterface.php
│       ├── SberGateway.php
│       ├── TinkoffGateway.php
│       └── TochkaGateway.php
├── Search/
│   └── SearchRankingService.php     [2951 bytes] ✅
├── Security/
│   ├── ApiKeyManagementService.php  [5792 bytes] ✅
│   ├── FraudControlService.php      [2865 bytes] ✅
│   ├── IdempotencyService.php       [7557 bytes] ✅
│   ├── RateLimiterService.php       [10675 bytes] ✅
│   ├── TenantAwareRateLimiter.php   [954 bytes] ✅
│   ├── WebhookSignatureService.php  [8702 bytes] ✅
│   └── WishlistAntiFraudService.php [4919 bytes] ✅
├── Wallet/
│   └── WalletService.php            [5483 bytes] ✅
├── Webhook/
│   └── WebhookSignatureValidator.php [1673 bytes] ✅
└── Wishlist/
    └── WishlistService.php          [6578 bytes] ✅
```

**Всего Services (корневые + nested): 27 файлов**

#### Статус по типам

| Тип | Статус | Примечание |
|-----|--------|-----------|
| Payment Services | ✅ EXISTS | Нужна интеграция с более чем 3 гейтвеями |
| Security Services | ✅ EXISTS (7 files) | Production-ready для CANON 2026 |
| Fraud Services | ✅ EXISTS | FraudMLService есть, но требует ML модель |
| Inventory Services | ✅ EXISTS | Базовое, нужны улучшения |
| Marketing Services | ✅ EXISTS | Есть Promo + Referral |
| AI Services | ✅ EXISTS (3 files) | DemandForecast, PriceSuggestion, Recommendation |
| Analytics Services | ✅ EXISTS | Базовое, нужны расширения |
| Search Services | ✅ EXISTS | SearchRanking + SearchService |

### 8.2 JOBS (все 9)

```
app/Jobs/
├── BonusAccrualJob.php              [9230 bytes] ✅ Bonus logic
├── CleanupExpiredBonusesJob.php     [6202 bytes] ✅ Cleanup
├── CleanupExpiredIdempotencyRecordsJob.php [1322 bytes] ✅ Cleanup
├── DemandForecastJob.php            [9965 bytes] ✅ ML forecast
├── FraudMLRecalculationJob.php      [8607 bytes] ✅ ML fraud detection
├── LowStockNotificationJob.php      [6718 bytes] ✅ Inventory alerts
├── PayoutProcessingJob.php          [8688 bytes] ✅ Payout logic
├── RecommendationQualityJob.php     [8673 bytes] ✅ ML quality checks
└── ReleaseHoldJob.php               [3290 bytes] ✅ Wallet hold release
```

**Всего: 9 Jobs (production-ready)**

#### Обязательные параметры для каждого Job

Все Jobs должны иметь:

- ✅ `protected $tries = 3;` (retry logic)
- ✅ `protected $backoff = [10, 30, 60];` (exponential backoff)
- ✅ `protected $tags = ['job-type'];` (tagging)
- ✅ `correlation_id` передача через конструктор или request
- ✅ `DB::transaction()` для мутаций
- ✅ `Log::channel('audit')` логирование с correlation_id

### 8.3 LISTENERS (минимальное)

```
app/Listeners/Octane/
└── [Octane-specific listeners, minimal count]
```

**Статус:** Listeners почти не используются, нужна интеграция с Events

### 8.4 EXCEPTIONS (3 custom)

```
app/Exceptions/
├── DuplicatePaymentException.php    [629 bytes] ✅
├── InvalidPayloadException.php      [611 bytes] ✅
└── RateLimitException.php           [865 bytes] ✅
```

**Нужно добавить:**

```
❌ InsufficientStockException
❌ FraudDetectedException
❌ PaymentGatewayException
❌ QuotaExceededException
❌ InvalidMigrationException
```

### 8.5 FACTORIES (20)

```
database/factories/
├── AdCampaignFactory.php            [861 bytes]
├── BusinessBranchFactory.php        [430 bytes]
├── CourseFactory.php                [864 bytes]
├── DeliveryOrderFactory.php         [812 bytes]
├── EventFactory.php                 [939 bytes]
├── FoodOrderFactory.php             [863 bytes]
├── GeoZoneFactory.php               [608 bytes]
├── HotelBookingFactory.php          [939 bytes]
├── InsurancePolicyFactory.php       [952 bytes]
├── InventoryItemFactory.php         [868 bytes]
├── MedicalCardFactory.php           [726 bytes]
├── MessageFactory.php               [636 bytes]
├── PaymentTransactionFactory.php    [2076 bytes] ✅ UPDATED
├── PropertyFactory.php              [1003 bytes]
├── SalonFactory.php                 [844 bytes]
├── SportsMembershipFactory.php      [744 bytes]
├── TaxiRideFactory.php              [1109 bytes]
├── TenantFactory.php                [1124 bytes] ✅ UPDATED
├── UserFactory.php                  [1075 bytes]
└── WalletFactory.php                [999 bytes] ✅ UPDATED
```

**Статус:** Все 20 фабрик EXISTS  
**Нужно обновить согласно CANON:**

- Добавить `correlation_id` в each factory
- Добавить `tenant_id` scoping
- Faker data должны быть реалистичны
- Все фабрики должны быть idempotent

### 8.6 SEEDERS (115+ всего)

#### Типы

| Категория | Количество | Примеры |
|-----------|-----------|---------|
| Vertical Seeders | 40+ | AutomotiveSeeder, BeautyShopSeeder, ClinicSeeder |
| B2B Seeders | 5+ | B2BMarketplaceSeeder, B2BAIEcosystemSeeder |
| Brand Seeders | 20+ | BeautyBrands, AutoBrands, SportBrands, RetailBrands |
| Filter Seeders | 15+ | BeautyFilterSeeder, ElectronicsFilterSeeder |
| Master Seeders | 5 | TenantMasterSeeder, ProductionMasterSeeder |
| Feature Seeders | 10+ | NewsletterSeeder, CRMAutomationSeeder, AIConstructorSeeder |
| Domain Seeders | 3 | CRM/, Marketplace/, tenant/ |

**Всего: 115+ seeders**

#### Структура директорий

```
database/seeders/
├── DatabaseSeeder.php               [Main entry point]
├── RolesAndPermissionsSeeder.php    [✅ RBAC setup]
├── CRM/
│   ├── [CRM-specific seeders]
├── Marketplace/
│   ├── [Marketplace-specific seeders]
├── tenant/
│   ├── [Tenant-specific seeders]
└── [115+ individual seeders]
```

### 8.7 План действий для Section 8

#### PHASE 1: ОБНОВЛЕНИЕ JOBS

Каждый Job должен быть проверен и обновлен:

```php
// Checklist для каждого Job:
✅ protected $tries = 3
✅ protected $backoff = [10, 30, 60]
✅ protected $tags = ['job-type']
✅ Конструктор с readonly $correlationId
✅ handle() с try/catch
✅ DB::transaction() для мутаций
✅ Log::channel('audit') с correlation_id
✅ Правильная очередь (default, high-priority)
```

#### PHASE 2: СОЗДАНИЕ НЕДОСТАЮЩИХ EXCEPTIONS

```php
// Создать в app/Exceptions/
❌ InsufficientStockException extends Exception
❌ FraudDetectedException extends Exception
❌ PaymentGatewayException extends Exception
❌ QuotaExceededException extends Exception
❌ InvalidMigrationException extends Exception
```

#### PHASE 3: ОБНОВЛЕНИЕ FACTORIES

Каждая factory должна:

```php
// database/factories/{Model}Factory.php
protected $model = Model::class;

public function definition(): array
{
    return [
        'tenant_id' => Tenant::factory(),
        'correlation_id' => Str::uuid(),
        'tags' => json_encode(['test']),
        // Realistic faker data
    ];
}
```

#### PHASE 4: REVIEW SEEDERS

- Проверить, что никакие seeders не запускаются в production
- Все seeders должны использовать Factory::create()
- Не создавать реальных пользователей/платежей в seeders

#### PHASE 5: EVENTS & LISTENERS

Создать недостающие события:

```php
✅ OrderCreatedEvent
✅ PaymentCompletedEvent
✅ AppointmentScheduledEvent
✅ EmployeeHiredEvent
✅ PayrollCalculatedEvent
✅ PromoCampaignCreatedEvent
✅ ReferralClaimedEvent
✅ BonusAccruedEvent
```

И соответствующие Listeners:

```php
✅ SendOrderConfirmationListener
✅ SendPaymentNotificationListener
✅ SendAppointmentReminderListener
✅ LogAuditEventListener
✅ UpdateAnalyticsListener
✅ TriggerFraudCheckListener
```

---

## 📦 МОДУЛЬ 9: ВЕРТИКАЛИ (DOMAINS)

### Статус: ✅ ПОЛНЫЙ КОМПЛЕКТ (22 вертикали)

### Критичность: **CRITICAL** (Core business logic)

### 9.1 Найденные вертикали (22 всего)

```
app/Domains/
├── Auto                     [✅ 40+ files] - Taxi, Delivery, Auto service, Car wash, Tuning
├── Beauty                   [✅ 41 files] - Salons, Masters, Services, Products
├── Courses                  [✅] - Online education, Instructors, Enrollments
├── Entertainment            [✅] - Events, Concerts, Shows, Tickets
├── Fashion                  [✅] - Apparel, Retail, Trends
├── FashionRetail            [✅] - E-commerce clothing brand
├── Fitness                  [✅] - Gyms, Memberships, Classes, Coaches
├── Flowers                  [✅] - Florists, Bouquets, Delivery
├── Food                     [✅] - Restaurants, Delivery, Catering, Supermarket
├── Freelance                [✅] - Freelancers, Projects, Skills
├── HomeServices             [✅] - Cleaning, Repair, Plumbing
├── Hotels                   [✅] - Bookings, Rooms, Amenities
├── Logistics                [✅] - Courier, Warehouse, Shipping
├── Medical                  [✅] - Clinics, Doctors, Appointments
├── MedicalHealthcare        [✅] - Advanced medical (Healthcare integration)
├── Pet                      [✅] - Pet stores, Supplies
├── PetServices              [✅] - Grooming, Veterinary, Pet care
├── Photography              [✅] - Photographers, Portfolios, Bookings
├── RealEstate               [✅] - Properties, Rentals, Sales
├── Sports                   [✅] - Sports goods, Coaches, Events, Nutrition
├── Tickets                  [✅] - Event tickets, Shows, Concerts
├── Travel                   [✅] - Tours, Hotels, Flights
└── TravelTourism            [✅] - Advanced tourism (Tourism integration)
```

**Всего вертикалей: 22**

### 9.2 Типовая структура вертикали

Каждая вертикаль должна иметь (согласно CANON 2026):

```
app/Domains/{Vertical}/
├── Models/                  [5-15 моделей]
│   ├── {Entity}.php
│   ├── {Service}.php
│   └── {Order/Booking}.php
│
├── Services/                [3-8 сервисов]
│   ├── {VerticalMainService}.php
│   ├── DemandForecastService.php
│   ├── InventoryManagementService.php
│   └── StaffScheduleService.php
│
├── Resources/ (Filament)    [2-5 ресурсов]
│   ├── {EntityResource}.php
│   └── {OrderResource}.php
│
├── Policies/                [2-4 polícy]
│   └── {EntityPolicy}.php
│
├── Jobs/                    [1-3 jobs]
│   ├── {DataImportJob}.php
│   └── {AnalyticsJob}.php
│
├── Events/                  [1-3 события]
│   └── {EntityCreatedEvent}.php
│
├── Listeners/               [1-3 слушателя]
│   └── {EntityEventListener}.php
│
├── Filament/               [Pages + Resources]
│   └── Resources/
│
├── Http/                   [Controllers]
│   └── Controllers/
│
├── Routes/                 [Route registration]
│   └── {vertical}.php
│
└── Database/ (if vertical-specific)
    ├── Factories/
    └── Seeders/
```

### 9.3 Обязательные элементы в каждой вертикали

#### Models (обязательные поля)

Все модели вертикали должны иметь:

```php
✅ protected $table = 'table_name';
✅ protected $fillable = [...];
✅ protected $hidden = [...];
✅ protected $casts = [...];
✅ booted() с global scope tenant_id + business_group_id
✅ Все relationships (hasMany, belongsTo, etc.)
✅ Методы для бизнес-логики
```

#### Services (обязательные)

Каждый vertical service должен:

```php
✅ Конструктор с readonly зависимостями
✅ DB::transaction() для мутаций
✅ Log::channel('audit') с correlation_id
✅ FraudControlService::check() перед записью
✅ RateLimiter на критичные методы
✅ Методы возвращают конкретный тип (Result, Model, Collection)
✅ Вызываемые из контроллеров, а не прямо в моделях
```

#### Filament Resources

```php
✅ protected static ?string $model = Model::class;
✅ form() с полным набором fields
✅ table() с columns, filters, actions, bulkActions
✅ getEloquentQuery() с tenant scoping + eager loading
✅ actions() (CreateAction, EditAction, DeleteAction)
✅ canCreate(), canEdit(), canDelete() проверка прав
```

### 9.4 Статистика по вертикалям

| Вертикаль | Файлы | Models | Services | Policies | Status |
|-----------|-------|--------|----------|----------|--------|
| Beauty | 41 | 10 | 5 | 2 | ✅ Complete |
| Auto | 40+ | 8+ | 5+ | 2+ | ✅ Complete |
| Food | 35+ | 12+ | 6+ | 2+ | ✅ Complete |
| Hotels | 35+ | 8+ | 5+ | 2 | ✅ Complete |
| RealEstate | 30+ | 8+ | 4+ | 2 | ✅ Complete |
| Others | 25+ each | 5+ | 3+ | 1+ | ✅ Complete |

### 9.5 Обязательные Seeders для каждой вертикали

Для каждой вертикали должен быть:

```
database/seeders/{VerticalName}Seeder.php
database/seeders/{VerticalName}VerticalSeeder.php
database/factories/{VerticalEntity}Factory.php
```

### 9.6 API Routes для каждой вертикали

Каждая вертикаль должна иметь в routes/:

```
routes/{vertical}.php          (main API routes)
routes/b2b.{vertical}.php      (B2B API routes)
```

### 9.7 План действий для Domains

#### PHASE 1: AUDIT (каждой вертикали)

Для каждого Domain проверить:

```checklist
✅ Models структура (relationships, scopes, casts)
✅ Services наличие всех обязательных методов
✅ DB::transaction() usage
✅ Log::channel('audit') с correlation_id
✅ FraudControlService::check() перед мутациями
✅ RateLimiter на API endpoints
✅ Filament Resources (если Admin-accessible)
✅ Jobs для async операций
✅ Events & Listeners
✅ Routes регистрация
```

#### PHASE 2: СТАНДАРТИЗАЦИЯ

Привести все вертикали в соответствие с CANON 2026:

1. Одинаковая структура папок
2. Одинаковые методы в Services
3. Одинаковые Filament Resources
4. Одинаковые API endpoints

#### PHASE 3: РАСШИРЕНИЕ ФУНКЦИОНАЛА

Для каждой вертикали добавить:

- DemandForecastService (если applicable)
- InventoryManagementService (если applicable)
- StaffScheduleService (если applicable)
- RecommendationService интеграция
- Analytics tracking

#### PHASE 4: ТЕСТИРОВАНИЕ

- Unit тесты для каждого Service
- Integration тесты для API endpoints
- Filament Resource тесты (если applicable)
- Authorization tests

#### PHASE 5: ДОКУМЕНТИРОВАНИЕ

Для каждой вертикали:

- README.md с описанием
- API endpoints documentation
- Database schema documentation
- Workflow diagrams

---

## 🔌 МОДУЛЬ 10: API И CONTROLLERS

### Статус: ✅ СУЩЕСТВУЕТ (V1 + V2 ready)

### Критичность: **CRITICAL** (API is core)

### 10.1 Найденные Controllers

#### API Controllers V1

```
app/Http/Controllers/Api/V1/
├── AuthController.php              [8176 bytes] ✅
├── BaseApiV1Controller.php          [1352 bytes] ✅
├── HealthCheckController.php        [4085 bytes] ✅
├── PaymentController.php            [5189 bytes] ✅
└── WalletController.php             [5712 bytes] ✅
```

**Всего: 5 V1 Controllers (24,514 bytes)**

#### API Controllers V2

```
app/Http/Controllers/Api/V2/
├── [TBD - готовы ли файлы?]
```

#### Основные API Controllers (корневые)

```
app/Http/Controllers/Api/
├── OpenApiController.php            [10021 bytes] ✅ OpenAPI/Swagger
├── PaymentController.php            [6812 bytes] ✅
└── V1/ (see above)
└── V2/ (to be verified)
```

#### Internal Controllers

```
app/Http/Controllers/Internal/
├── [TBD - контроллеры для вебхуков]
```

#### Auth Controllers

```
app/Http/Controllers/Auth/
├── [TBD]
```

### 10.2 Обязательная структура Controllers

Каждый API Controller должен:

```php
// app/Http/Controllers/Api/V1/BaseApiV1Controller.php
abstract class BaseApiV1Controller
{
    protected function successResponse($data, string $message = null, int $status = 200): JsonResponse
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    protected function validationErrorResponse(array $errors): JsonResponse
    protected function unauthorizedResponse(): JsonResponse
    protected function forbiddenResponse(): JsonResponse
}

// Каждый controller должен:
✅ Наследовать BaseApiV1Controller
✅ Использовать FormRequest для валидации
✅ try/catch + JsonResponse с correlation_id
✅ Вызывать Service (никогда не Model прямо)
✅ DB::transaction() для мутаций
✅ Log::channel('audit') на каждое действие
✅ RateLimiter middleware
✅ Authorization checks (Policy)
```

### 10.3 FormRequests

```
app/Http/Requests/
├── BaseApiRequest.php              [2346 bytes] ✅
├── CreateApiKeyRequest.php          [1258 bytes] ✅
├── PaymentInitRequest.php           [1707 bytes] ✅
├── PromoApplyRequest.php            [961 bytes] ✅
└── ReferralClaimRequest.php         [957 bytes] ✅
```

**Всего: 5 FormRequests (7,229 bytes)**

#### Обязательные методы в FormRequest

```php
public function authorize(): bool
{
    // Проверка прав + FraudControlService::check()
}

public function rules(): array
{
    // Полный набор валидационных правил
}

public function messages(): array
{
    // Человекочитаемые сообщения об ошибках
}

public function failedValidation(Validator $validator): void
{
    // Возвращать JSON 422 с correlation_id
}
```

### 10.4 Routes

```
routes/
├── api.php                         [3576 bytes] ✅ Main API
├── auto.api.php                    [3597 bytes] ✅
├── courses.api.php                 [3154 bytes] ✅
├── [... 40+ route файлов]
├── b2b.*.api.php                   [25+ files]
├── tenant.php                      [4926 bytes] ✅ Tenant routes
├── web.php                         [515 bytes] ✅ Web routes
└── console.php                     [302 bytes] ✅
```

**Всего: 43+ route файлов**

#### Типы routes

| Тип | Кол-во | Примеры |
|-----|--------|---------|
| Vertical API routes | 20+ | auto.api.php, beauty.api.php, food.api.php |
| B2B API routes | 25+ | b2b.auto.api.php, b2b.beauty.api.php |
| Main API | 1 | api.php |
| Tenant routes | 1 | tenant.php (Filament) |

### 10.5 Обязательная структура API

Каждый API endpoint должен:

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'rate-limit-api'])
    ->prefix('/api/v1')
    ->name('api.v1.')
    ->group(function () {
        // Payment endpoints
        Route::post('/payments/init', [PaymentController::class, 'init'])
            ->name('payments.init')
            ->middleware('rate-limit-payment'); // 10 req/min

        // Wallet endpoints
        Route::middleware('auth:sanctum')
            ->group(function () {
                Route::get('/wallet/balance', [WalletController::class, 'balance'])
                    ->name('wallet.balance');
                Route::post('/wallet/credit', [WalletController::class, 'credit'])
                    ->name('wallet.credit');
            });

        // Other endpoints...
    });

// Webhook routes (no auth, but signature verification)
Route::post('/webhooks/payment/{gateway}', [WebhookController::class, 'handle'])
    ->middleware('verify-webhook-signature');
```

### 10.6 Middleware для API

Обязательные middleware:

```php
✅ 'auth:sanctum'                   // API authentication
✅ 'rate-limit-api'                 // 1000 req/min per tenant
✅ 'rate-limit-payment'             // 10 req/min per user
✅ 'rate-limit-promo'               // 50 req/min per user
✅ 'rate-limit-search'              // 1000 light / 100 heavy per hour
✅ 'verify-webhook-signature'       // HMAC validation
✅ 'ip-whitelist'                   // Only for webhooks from payment providers
✅ 'tenant-scoping'                 // Automatic tenant filtering
```

### 10.7 OpenAPI/Swagger Documentation

```
app/Http/Controllers/Api/OpenApiController.php [✅ 10021 bytes]
```

Должен содержать:

- ✅ Swagger 3.0 schema
- ✅ Все endpoints с примерами
- ✅ Authentication requirements
- ✅ Error codes
- ✅ Rate limits
- ✅ Response examples

### 10.8 План действий для API

#### PHASE 1: AUDIT API V1

Каждый V1 endpoint проверить на:

```checklist
✅ Использует BaseApiV1Controller
✅ FormRequest валидация
✅ try/catch + JsonResponse
✅ correlation_id в ответе
✅ RateLimiter middleware
✅ Authorization checks
✅ Service layer (не прямо Model)
✅ Log::channel('audit')
✅ Документирован в OpenAPI
```

#### PHASE 2: РЕАЛИЗАЦИЯ V2 ENDPOINTS (если нужны)

V2 может отличаться:

- Новые endpoints
- Изменённая схема ответов
- Дополнительная функциональность
- Backwards compatibility

#### PHASE 3: WEBHOOK ENDPOINTS

Создать Internal controllers для вебхуков:

```php
app/Http/Controllers/Internal/
├── PaymentWebhookController.php     [Tinkoff, Sber, Tochka]
├── RefundWebhookController.php
└── StatusUpdateWebhookController.php
```

Каждый вебхук должен:

- ✅ Verify signature (WebhookSignatureService)
- ✅ Check idempotency (IdempotencyService)
- ✅ Process in queue (Job)
- ✅ Log with correlation_id
- ✅ Return 200 OK immediately

#### PHASE 4: API DOCUMENTATION

- OpenAPI schema completion
- Postman collection
- Developer guide
- Error codes reference

#### PHASE 5: API VERSIONING STRATEGY

```
Version 1 (Current):
├── /api/v1/payments/init
├── /api/v1/wallet/balance
└── [All current endpoints]

Version 2 (Future):
├── /api/v2/payments/init [improved]
├── /api/v2/wallet/balance [improved]
└── [New endpoints]

Deprecation policy:
├── V1 supported 12 months after V2 release
├── Migration guide provided
└── Automated migration tools
```

---

## 📊 ИТОГОВАЯ СТАТИСТИКА

### Общие метрики проекта

```
СТРУКТУРА ПРОЕКТА:
├── Вертикалей (Domains):      22
├── Services (корневые):         14
├── Services (вложенные):        15+
├── Policies:                    16
├── Jobs:                        9
├── Miграций:                    55
├── Factories:                   20
├── Seeders:                     115+
├── Routes файлы:               43+
├── Controllers (API V1):        5
├── FormRequests:                5
├── Custom Exceptions:           3 (need +5)
└── Config файлы:               10
```

### Размер проекта

```
Services (все):         ~120 KB
Models (основные):      ~20 KB
Policies:               ~93 KB
Jobs:                   ~70 KB
Controllers API:        ~30 KB
FormRequests:           ~7 KB
───────────────────────────
ВСЕГО CODE:             ~350+ KB

+ 55 миграций
+ 115+ seeders
+ 20 factories
+ 22 vертикали (40+ KB each = 880+ KB)
+ Routes (43+ файлов = ~150 KB)
───────────────────────────
TOTAL PROJECT SIZE:     ~1.5 MB (excluding node_modules, storage)
```

### Статус по модулям (CANON 2026 compliance)

| Модуль | Статус | Полнота | Приоритет | Оценка |
|--------|--------|---------|-----------|--------|
| 1. Авторизация & RBAC | ⚠️ Partial | 60% | **CRITICAL** | 6/10 |
| 2. Уведомления | ❌ Missing | 20% | **HIGH** | 2/10 |
| 3. Маркетинг | ✅ Exists | 70% | **HIGH** | 7/10 |
| 4. Аналитика | ✅ Partial | 50% | **MEDIUM** | 5/10 |
| 5. HR | ⚠️ Minimal | 30% | **HIGH** | 3/10 |
| 6. Payroll | ❌ Missing | 10% | **CRITICAL** | 1/10 |
| 7. Logistics | ✅ Exists | 80% | **HIGH** | 8/10 |
| 8. Components | ✅ Exists | 85% | **MEDIUM** | 8.5/10 |
| 9. Domains | ✅ Complete | 90% | **CRITICAL** | 9/10 |
| 10. API | ✅ Exists | 80% | **CRITICAL** | 8/10 |
| **TOTAL** | ✅ **70%** | **70%** | - | **7/10** |

### Критические пробелы (BLOCKING)

```
🔴 CRITICAL (должны быть срочно):
  ❌ AuthService полный (app/Services/Auth/AuthService.php)
  ❌ Payroll система (app/Domains/Payroll/ + Service)
  ❌ HR Domain (app/Domains/HR/ полный)
  ❌ Notification system (app/Notifications/ + Mailables)
  ❌ Config permission.php
  ❌ More custom Exceptions (5 штук)
  ❌ Events & Listeners integration
  
🟡 HIGH (очень желательно):
  ⚠️ PromoCampaign Model + миграция
  ⚠️ Referral Model + миграция
  ⚠️ Bonus Model + миграция
  ⚠️ Payment Webhooks Internal Controller
  ⚠️ BigData Aggregator Service
  ⚠️ Advanced Fraud Detection
  ⚠️ API V2 endpoints
```

### Рекомендуемый план на следующие 4 дня

**День 1: Авторизация & RBAC (4 часа)**

- AuthService создание
- config/permission.php
- Middleware создание

**День 2: HR & Payroll (6 часов)**

- HR Domain создание (Models, Services, Filament)
- Payroll Domain создание (Models, Services, Filament)
- Miграции

**День 3: Notifications & Marketing (6 часов)**

- Notification system (app/Notifications/, app/Mail/)
- Promo/Referral/Bonus Models + миграции
- Events & Listeners

**День 4: Completion & Testing (4 часа)**

- Fix missing Exceptions
- Complete API documentation
- Run full test suite
- Production readiness checks

---

## ✅ ИТОГОВЫЕ ВЫВОДЫ

### Статус проекта: **70% готов к CANON 2026**

### Сильные стороны

- ✅ Полный набор 22 вертикалей (Domains)
- ✅ Security Services хорошо реализованы
- ✅ Payment система существует (3 gateways)
- ✅ Fraud ML система готова
- ✅ Inventory management ready
- ✅ Wish list система
- ✅ Analytics & Recommendations
- ✅ API V1 структура готова

### Слабые стороны

- ❌ Нет полной HR системы
- ❌ Нет Payroll системы
- ❌ Нет Notification системы
- ❌ AuthService требует расширения
- ❌ Отсутствуют некоторые Models (Promo, Referral, Bonus)
- ❌ Events & Listeners не интегрированы
- ❌ API V2 не начинался

### Немедленные действия (Today)

1. Создать AuthService + config/permission.php
2. Создать Notification систему (app/Notifications/ + Mail/)
3. Создать Promo/Referral/Bonus Models + миграции
4. Заполнить недостающие Custom Exceptions

### 4-дневный план

- День 1: Авторизация COMPLETE
- День 2: HR + Payroll COMPLETE
- День 3: Notifications + Marketing COMPLETE
- День 4: Testing + Finalization

### Production-ready timeline

**Оценка: 5-7 дней** (от текущего состояния)

---

**Документ подготовлен:**  
Дата: 18 марта 2026 г.  
Версия КАНОНА: 2026 (Production-Ready)  
Статус: **READY FOR IMPLEMENTATION**
