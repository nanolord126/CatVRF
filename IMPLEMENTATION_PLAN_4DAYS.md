<!--
=============================================================================
БЫСТРЫЙ ПЛАН ДЕЙСТВИЙ - 4 ДНЯ ДО PRODUCTION (CANON 2026)
=============================================================================
-->

# ⚡ ПЛАН РЕАЛИЗАЦИИ НА 4 ДНЯ

**Дата начала:** 18 марта 2026 г.  
**Целевая дата:** 22 марта 2026 г. (Production ready)  
**Версия КАНОНА:** 2026 Production-Ready

---

## 📋 ДЕНЬ 1: АВТОРИЗАЦИЯ И RBAC (4-5 часов)

### ✅ ЦЕЛЬ ДНЯ

Завершить RBAC систему согласно CANON 2026

### ЗАДАЧИ

#### Task 1.1: Создать AuthService (30 мин)

```
Файл: app/Services/Auth/AuthService.php

Методы:
✅ authenticate(string $email, string $password): AuthResult
✅ generateToken(User $user, int $lifetime = 365): string
✅ validatePermission(User $user, string $ability, Model $resource = null): bool
✅ checkFraudBefore(string $email, string $ip, string $deviceId): bool
✅ revokeToken(string $token): void
✅ refreshToken(string $token): string

Требования CANON:
✅ correlation_id в конструкторе
✅ FraudControlService::check() в authenticate()
✅ RateLimiter tenant-aware в generateToken()
✅ DB::transaction() для токенов
✅ Log::channel('audit') на каждый вызов
✅ Redis кэширование permissions (TTL 300)
```

#### Task 1.2: Создать config/permission.php (20 мин)

```php
// config/permission.php
return [
    'abilities' => [
        // Dashboard
        'view_dashboard',
        'view_analytics',
        'export_data',
        
        // Employee management
        'manage_employees',
        'manage_departments',
        'manage_positions',
        'manage_leaves',
        'manage_schedules',
        
        // Financial
        'manage_payroll',
        'manage_payments',
        'manage_wallet',
        'manage_commissions',
        'manage_payouts',
        
        // Content
        'manage_products',
        'manage_services',
        'manage_orders',
        'manage_appointments',
        
        // Marketing
        'manage_promo',
        'manage_referral',
        'manage_campaigns',
        
        // Settings
        'manage_settings',
        'manage_api_keys',
        'manage_webhooks',
    ],
    
    'role_abilities' => [
        'admin' => ['*'],  // All abilities
        
        'business_owner' => [
            'view_dashboard', 'view_analytics',
            'manage_employees', 'manage_payroll',
            'manage_products', 'manage_services',
            'manage_orders', 'manage_appointments',
            'manage_payments', 'manage_wallet',
            'manage_promo', 'manage_referral',
            'manage_settings', 'manage_api_keys',
        ],
        
        'manager' => [
            'view_dashboard', 'manage_employees',
            'manage_orders', 'manage_appointments',
            'view_analytics', 'export_data',
        ],
        
        'accountant' => [
            'view_dashboard', 'manage_payroll',
            'manage_payments', 'manage_wallet',
            'view_analytics', 'export_data',
        ],
        
        'employee' => [
            'view_dashboard',
            'manage_appointments',  // Own only
        ],
    ],
];
```

#### Task 1.3: Создать Middleware (30 мин)

```
Файл: app/Http/Middleware/CheckAbilityMiddleware.php

Функция:
✅ Получить 'ability' из route параметра
✅ Получить 'resource' из request
✅ Вызвать AuthService::validatePermission()
✅ FraudControlService::check() перед check
✅ Возвращать 403 JsonResponse если denied
✅ Log::channel('audit') на denied
```

#### Task 1.4: Обновить Route middleware (20 мин)

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'check-ability:manage_payments'])
    ->post('/api/v1/payments/init', [PaymentController::class, 'init']);

// routes/tenant.php (Filament)
Route::middleware(['auth', 'check-ability:manage_employees'])
    ->resource('employees', EmployeeResource::class);
```

#### Task 1.5: UNIT TESTS (30 мин)

```
tests/Unit/Services/Auth/
├── AuthServiceTest.php
│   ├── testAuthenticate()
│   ├── testGenerateToken()
│   ├── testValidatePermission()
│   ├── testFraudCheck()
│   └── testRevokeToken()
└── AuthorizationMiddlewareTest.php
    ├── testAllowsValidAbility()
    ├── testDeniesInvalidAbility()
    └── testLogsAuditEvent()
```

#### Task 1.6: Обновить API Documentation (20 мин)

```
docs/api/authentication.md:
✅ Authentication flow diagram
✅ Token generation example
✅ Permission checking example
✅ Error codes (401, 403, 429)
```

---

## 📋 ДЕНЬ 2: HR И PAYROLL (6-7 часов)

### ✅ ЦЕЛЬ ДНЕЙ

Создать полные системы HR и Payroll

### ЗАДАЧИ

#### Task 2.1: HR Domain создание (2 часа)

##### 2.1.1 Models создание

```bash
php artisan make:model "Domains/HR/Models/Employee" -m
php artisan make:model "Domains/HR/Models/Department" -m
php artisan make:model "Domains/HR/Models/Position" -m
php artisan make:model "Domains/HR/Models/EmployeeSchedule" -m
php artisan make:model "Domains/HR/Models/EmployeeLeave" -m
php artisan make:model "Domains/HR/Models/EmployeeReview" -m
```

##### 2.1.2 Models реализация

Каждый Model должен иметь:

```php
✅ protected $table = 'table_name';
✅ protected $fillable = [all fields];
✅ protected $hidden = ['password', 'token'];
✅ protected $casts = [dates, booleans, json];
✅ booted() с tenant_id + business_group_id global scope
✅ Все relationships (belongsTo, hasMany)
✅ Accessor/Mutator для бизнес-логики
✅ Scopes: active(), inactive(), byDepartment()
✅ SoftDeletes если нужен soft delete
```

##### 2.1.3 Миграции

```sql
-- 2026_03_18_create_employees_table.php
CREATE TABLE employees (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT UNIQUE,
    department_id BIGINT,
    position_id BIGINT,
    full_name VARCHAR(255),
    email VARCHAR(255),
    hire_date DATE,
    end_date DATE NULL,
    salary DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'RUB',
    contract_type ENUM('permanent', 'temporary', 'contract'),
    status ENUM('active', 'on_leave', 'terminated') DEFAULT 'active',
    correlation_id UUID,
    tags JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (position_id) REFERENCES positions(id),
    INDEX (tenant_id, status),
    INDEX (department_id),
    UNIQUE INDEX (tenant_id, email)
);

-- 2026_03_18_create_departments_table.php
-- 2026_03_18_create_positions_table.php
-- 2026_03_18_create_employee_schedules_table.php
-- 2026_03_18_create_employee_leaves_table.php
-- 2026_03_18_create_employee_reviews_table.php
```

#### Task 2.2: HR Services создание (1.5 часа)

```
app/Domains/HR/Services/
├── EmployeeManagementService.php     [500+ lines]
├── DepartmentService.php             [200 lines]
├── ScheduleService.php               [300 lines]
└── LeaveManagementService.php        [250 lines]
```

Каждый Service должен иметь:

```php
✅ Readonly constructor dependencies
✅ DB::transaction() для всех мутаций
✅ FraudControlService::check() перед записью
✅ Log::channel('audit') с correlation_id
✅ RateLimiter на критичные методы
✅ Правильные return types
✅ Exception throwing вместо null
```

#### Task 2.3: HR Filament Resources (1 час)

```
app/Domains/HR/Filament/Resources/
├── EmployeeResource.php
├── DepartmentResource.php
└── PositionResource.php
```

Каждый Resource должен иметь:

```php
✅ protected static ?string $model = Model::class;
✅ form() с полным набором fields
✅ table() с columns, filters, actions
✅ getEloquentQuery() с tenant scoping
✅ Proper authorization checks
```

#### Task 2.4: Payroll Domain создание (2 часа)

##### 2.4.1 Models

```bash
php artisan make:model "Domains/Payroll/Models/Payroll" -m
php artisan make:model "Domains/Payroll/Models/SalaryComponent" -m
php artisan make:model "Domains/Payroll/Models/Deduction" -m
php artisan make:model "Domains/Payroll/Models/PayrollApproval" -m
```

##### 2.4.2 Миграции

```sql
-- 2026_03_18_create_payrolls_table.php
CREATE TABLE payrolls (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    employee_id BIGINT NOT NULL,
    period_start DATE,
    period_end DATE,
    gross_salary DECIMAL(10,2),
    tax DECIMAL(10,2),
    deductions DECIMAL(10,2),
    net_salary DECIMAL(10,2),
    status ENUM('draft', 'submitted', 'approved', 'paid'),
    payment_date DATE NULL,
    correlation_id UUID,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX (tenant_id, period_start, period_end),
    UNIQUE INDEX (tenant_id, employee_id, period_start)
);
```

#### Task 2.5: Payroll Service (1 час)

```
app/Domains/Payroll/Services/PayrollCalculationService.php

Методы:
✅ calculatePayroll(int $employeeId, Carbon $periodStart, Carbon $periodEnd): Payroll
✅ calculateTax(float $grossSalary, string $region = 'RU'): float
✅ calculateDeductions(Employee $employee): float
✅ processPayroll(int $payrollId): bool
✅ approvePayroll(int $payrollId, int $approverId): void
✅ generatePayslip(int $payrollId): PDF
✅ exportPayroll(Collection $payrolls, string $format): void
```

#### Task 2.6: Payroll Filament + Jobs (1 час)

```
✅ PayrollResource (Filament)
✅ MonthlyPayrollJob (run 1st of month)
✅ PayrollApprovalNotificationJob
✅ PayoutProcessingJob
```

---

## 📋 ДЕНЬ 3: NOTIFICATIONS & MARKETING (6-7 часов)

### ✅ ЦЕЛЬ ДНЕЙ

Создать Notification систему + доделать Marketing models

### ЗАДАЧИ

#### Task 3.1: Notification System (2.5 часа)

##### 3.1.1 Создать структуру

```bash
mkdir -p app/Notifications app/Mail
mkdir -p database/migrations
mkdir -p resources/views/notifications
mkdir -p resources/views/emails
```

##### 3.1.2 Notification классы

```
app/Notifications/
├── OrderConfirmationNotification.php
├── PaymentSuccessNotification.php
├── AppointmentReminderNotification.php
├── LowStockAlertNotification.php
├── PayoutCompletedNotification.php
├── PayrollReportNotification.php
├── PromoExpiredNotification.php
└── ReferralClaimedNotification.php
```

Каждый Notification:

```php
✅ Implements ShouldQueue
✅ public function via($notifiable): array
✅ public function toMail($notifiable): MailMessage
✅ public function toArray($notifiable): array
✅ Включить correlation_id в тему/body
✅ User preferences check перед send
```

##### 3.1.3 Mailables

```
app/Mail/
├── OrderConfirmationMail.php
├── InvoiceMail.php
├── PayrollReportMail.php
├── PromoCampaignMail.php
└── WeeklyAnalyticsReportMail.php
```

Каждый Mailable:

```php
✅ Implements ShouldQueue
✅ public function build(): self
✅ Markdown templates в resources/views/emails/
✅ Attachment поддержка (для invoice, payslip)
✅ Dynamic data binding
```

##### 3.1.4 Миграции

```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    notifiable_type VARCHAR(255),
    notifiable_id BIGINT,
    type VARCHAR(255),
    data JSON,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    INDEX (notifiable_id, read_at)
);

CREATE TABLE notification_preferences (
    id BIGINT PRIMARY KEY,
    user_id BIGINT UNIQUE,
    email_enabled BOOLEAN DEFAULT true,
    sms_enabled BOOLEAN DEFAULT false,
    push_enabled BOOLEAN DEFAULT false,
    quiet_hours_start TIME,
    quiet_hours_end TIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

##### 3.1.5 Config

```php
// config/notification.php
return [
    'channels' => ['mail', 'database', 'sms', 'push'],
    'queue' => 'notifications',
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS'),
        'name' => env('MAIL_FROM_NAME'),
    ],
    'templates' => [
        'order_confirmation' => ['mail', 'database'],
        'payment_success' => ['mail', 'database', 'push'],
        'appointment_reminder' => ['sms', 'push'],
        'low_stock_alert' => ['mail'],
    ],
    'retry' => 3,
    'timeout' => 300,
];
```

##### 3.1.6 Service обновление

```php
// app/Services/NotificationService.php
public function send(User|Tenant $recipient, string $type, array $data): bool
public function sendBulk(Collection $recipients, string $type, array $data): int
public function sendDeferred($recipient, string $type, array $data, Carbon $sendAt): void
public function getUnread(User $user): Collection
public function markAsRead(Notification $notification): void
public function getUserPreferences(User $user): array
```

#### Task 3.2: Marketing Models & Migrations (2.5 часа)

##### 3.2.1 Создать Models

```bash
php artisan make:model PromoCampaign -m
php artisan make:model PromoUse -m
php artisan make:model Referral -m
php artisan make:model Bonus -m
php artisan make:model BonusRule -m
```

##### 3.2.2 Миграции

```sql
-- promo_campaigns
CREATE TABLE promo_campaigns (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT,
    code VARCHAR(50) UNIQUE,
    type ENUM('discount_percent', 'fixed_amount', 'bundle', 'buy_x_get_y'),
    start_at TIMESTAMP,
    end_at TIMESTAMP,
    budget INT (в копейках),
    spent_budget INT DEFAULT 0,
    max_uses_total INT,
    status ENUM('active', 'paused', 'expired'),
    correlation_id UUID,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    INDEX (code, status)
);

-- referrals
CREATE TABLE referrals (
    id BIGINT PRIMARY KEY,
    referrer_id BIGINT,
    referee_id BIGINT,
    referral_code VARCHAR(50) UNIQUE,
    status ENUM('pending', 'qualified', 'rewarded'),
    turnover_threshold INT,
    bonus_amount INT,
    correlation_id UUID,
    FOREIGN KEY (referrer_id) REFERENCES users(id),
    FOREIGN KEY (referee_id) REFERENCES users(id)
);

-- bonuses
CREATE TABLE bonuses (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT,
    type ENUM('referral', 'turnover', 'promo', 'loyalty'),
    amount INT,
    status ENUM('pending', 'credited', 'withdrawn'),
    credited_at TIMESTAMP NULL,
    correlation_id UUID,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id)
);
```

##### 3.2.3 Model relationships

```php
✅ PromoCampaign: hasMany(PromoUse), belongsTo(Tenant)
✅ PromoUse: belongsTo(PromoCampaign, User, Order)
✅ Referral: belongsTo(User as referrer, referee), hasMany(BonusTransaction)
✅ Bonus: belongsTo(Wallet), hasOne(BalanceTransaction)
```

#### Task 3.3: Marketing Services обновление (1.5 часа)

```php
// PromoCampaignService
✅ createCampaign(array $data): PromoCampaign
✅ applyPromo(string $code, Cart|Order $cart): DiscountResult
✅ validatePromo(string $code): ValidationResult
✅ cancelPromoUse(int $useId): bool
✅ getActiveCampaigns(): Collection

// ReferralService
✅ generateReferralLink(int $referrerId): string
✅ registerReferral(string $code, int $newUserId): bool
✅ checkQualification(int $referralId): QualificationResult
✅ awardBonus(int $referralId): void
```

#### Task 3.4: Events & Listeners (1.5 часа)

```
app/Events/
├── OrderCreatedEvent.php
├── PaymentCompletedEvent.php
├── AppointmentScheduledEvent.php
├── EmployeeHiredEvent.php
├── PayrollCalculatedEvent.php
└── ReferralClaimedEvent.php

app/Listeners/
├── SendOrderConfirmationListener.php
├── SendPaymentNotificationListener.php
├── SendAppointmentReminderListener.php
├── LogAuditEventListener.php
├── UpdateAnalyticsListener.php
└── TriggerFraudCheckListener.php
```

Каждое Event:

```php
✅ Implements ShouldDispatchAfterCommit
✅ public $correlationId = 'value'
✅ Serializable для queue

Каждый Listener:
✅ public function handle(Event $event): void
✅ DB::transaction() для мутаций
✅ Log::channel('audit') с correlation_id
```

---

## 📋 ДЕНЬ 4: COMPLETION & TESTING (4-5 часов)

### ✅ ЦЕЛЬ ДНЕЙ

Финализация, тестирование, production-ready

### ЗАДАЧИ

#### Task 4.1: Создать недостающие Exceptions (30 мин)

```php
// app/Exceptions/
InsufficientStockException extends Exception
FraudDetectedException extends Exception
PaymentGatewayException extends Exception
QuotaExceededException extends Exception
InvalidMigrationException extends Exception
```

#### Task 4.2: Update Factories (30 мин)

Каждая factory должна:

```php
✅ Добавить correlation_id
✅ Добавить tenant_id scoping
✅ Использовать realistic faker
✅ Быть idempotent
```

#### Task 4.3: Документирование (1 час)

```
docs/
├── AUTHORIZATION.md
├── HR_SYSTEM.md
├── PAYROLL_SYSTEM.md
├── NOTIFICATION_SYSTEM.md
├── MARKETING_SYSTEM.md
└── API_GUIDE.md
```

#### Task 4.4: Full Test Suite (1.5 часа)

```bash
# Unit tests
✅ AuthService tests
✅ HR Services tests
✅ Payroll Services tests
✅ Notification tests
✅ Marketing Services tests

# Integration tests
✅ API endpoints (V1)
✅ Authorization flow
✅ Notification sending
✅ Marketing campaigns

# Coverage target: > 80%
php artisan test --coverage-min=80
```

#### Task 4.5: Security Audit (1 час)

```checklist
✅ correlation_id везде
✅ tenant_id scoping везде
✅ FraudControlService используется
✅ DB::transaction() для мутаций
✅ RateLimiter на endpoints
✅ Log::channel('audit') везде
✅ Custom Exceptions для errors
✅ FormRequest validation
✅ Policy authorization
✅ Webhook signature verification
```

#### Task 4.6: Production Checklist (1 час)

```checklist
✅ Все миграции runnable
✅ Env variables задокументированы
✅ Config files правильные
✅ Seeders не запускаются в production
✅ Logging configured (audit channel)
✅ Queue configured
✅ Cache configured
✅ Database indexes created
✅ API documentation complete
✅ Error handling complete
✅ Rate limiting configured
✅ Webhook endpoints ready
✅ Payment gateway configured
✅ Email service configured
✅ Notification channels ready
```

#### Task 4.7: Deploy & Verification (30 мин)

```bash
# Preparation
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Testing
php artisan test
npm run build

# Verification
curl -X GET https://api.example.com/api/v1/health
curl -X POST https://api.example.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

---

## 📊 ИТОГОВЫЙ СПИСОК ФАЙЛОВ НА СОЗДАНИЕ

### День 1: Авторизация (4 файла)

```
✅ app/Services/Auth/AuthService.php
✅ config/permission.php
✅ app/Http/Middleware/CheckAbilityMiddleware.php
✅ tests/Unit/Services/Auth/AuthServiceTest.php
```

### День 2: HR & Payroll (25+ файлов)

```
HR:
✅ app/Domains/HR/Models/Employee.php
✅ app/Domains/HR/Models/Department.php
✅ app/Domains/HR/Models/Position.php
✅ app/Domains/HR/Models/EmployeeSchedule.php
✅ app/Domains/HR/Models/EmployeeLeave.php
✅ app/Domains/HR/Services/EmployeeManagementService.php
✅ app/Domains/HR/Services/DepartmentService.php
✅ app/Domains/HR/Services/ScheduleService.php
✅ app/Domains/HR/Services/LeaveManagementService.php
✅ app/Domains/HR/Filament/Resources/EmployeeResource.php
✅ app/Domains/HR/Filament/Resources/DepartmentResource.php
✅ database/migrations/2026_03_18_create_employees_table.php
✅ database/migrations/2026_03_18_create_departments_table.php
✅ database/factories/EmployeeFactory.php
✅ database/seeders/EmployeeSeeder.php

Payroll:
✅ app/Domains/Payroll/Models/Payroll.php
✅ app/Domains/Payroll/Models/SalaryComponent.php
✅ app/Domains/Payroll/Models/Deduction.php
✅ app/Domains/Payroll/Services/PayrollCalculationService.php
✅ app/Domains/Payroll/Filament/Resources/PayrollResource.php
✅ app/Domains/Payroll/Jobs/MonthlyPayrollJob.php
✅ database/migrations/2026_03_18_create_payrolls_table.php
✅ database/factories/PayrollFactory.php
```

### День 3: Notifications & Marketing (30+ файлов)

```
Notifications:
✅ app/Notifications/OrderConfirmationNotification.php
✅ app/Notifications/PaymentSuccessNotification.php
✅ app/Mail/OrderConfirmationMail.php
✅ app/Mail/InvoiceMail.php
✅ config/notification.php
✅ database/migrations/2026_03_18_create_notifications_table.php

Marketing:
✅ app/Models/PromoCampaign.php
✅ app/Models/PromoUse.php
✅ app/Models/Referral.php
✅ app/Models/Bonus.php
✅ database/migrations/2026_03_18_create_promo_campaigns_table.php
✅ database/migrations/2026_03_18_create_referrals_table.php
✅ database/migrations/2026_03_18_create_bonuses_table.php
✅ database/factories/PromoCampaignFactory.php
✅ database/factories/ReferralFactory.php

Events & Listeners:
✅ app/Events/OrderCreatedEvent.php
✅ app/Events/PaymentCompletedEvent.php
✅ app/Listeners/SendOrderConfirmationListener.php
✅ app/Listeners/SendPaymentNotificationListener.php

Plus migrations, seeders, tests...
```

### День 4: Finalization (10+ файлов)

```
✅ app/Exceptions/InsufficientStockException.php
✅ app/Exceptions/FraudDetectedException.php
✅ app/Exceptions/PaymentGatewayException.php
✅ app/Exceptions/QuotaExceededException.php
✅ app/Exceptions/InvalidMigrationException.php
✅ docs/AUTHORIZATION.md
✅ docs/HR_SYSTEM.md
✅ docs/PAYROLL_SYSTEM.md
✅ docs/NOTIFICATION_SYSTEM.md
✅ tests/ (+ 15 test files)
```

---

## ⏱️ ВРЕМЕННАЯ ШКАЛА

| День | Модуль | Часы | Начало | Конец |
|------|--------|------|--------|-------|
| 1 | RBAC | 4-5 | 09:00 | 14:00 |
| 2 | HR + Payroll | 6-7 | 09:00 | 16:00 |
| 3 | Notifications + Marketing | 6-7 | 09:00 | 16:00 |
| 4 | Completion + Testing | 4-5 | 09:00 | 14:00 |
| **TOTAL** | **ALL** | **20-26** | | **Production Ready** |

---

## 🎯 УСПЕШНОЕ ЗАВЕРШЕНИЕ = CHECKLIST

- ✅ Все 4 дня завершены
- ✅ Все файлы созданы/обновлены
- ✅ Все миграции работают
- ✅ Test suite > 80% coverage
- ✅ Security audit PASSED
- ✅ API documentation COMPLETE
- ✅ Production checklist PASSED
- ✅ Deploy readiness VERIFIED

**Status: 🚀 READY FOR PRODUCTION**
