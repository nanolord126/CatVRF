# 📍 БЫСТРАЯ НАВИГАЦИЯ ПО СТРУКТУРЕ ПРОЕКТА

**Файл создан:** 18 марта 2026 г.

## 🎯 ОСНОВНЫЕ ПУТИ В ПРОЕКТЕ

### Где находятся ключевые компоненты

```
CatVRF/
│
├── 🔐 АВТОРИЗАЦИЯ & RBAC
│   ├── app/Enums/Role.php                    ← Roles enum
│   ├── app/Policies/                         ← 16 Policy files
│   ├── app/Services/Auth/                    ← ❌ НЕ СОЗДАН (НУЖНО)
│   ├── config/auth.php                       ← Auth config
│   └── config/permission.php                 ← ❌ НЕ СОЗДАН (НУЖНО)
│
├── 📧 УВЕДОМЛЕНИЯ
│   ├── app/Services/NotificationService.php  ← Существует (нужно расширить)
│   ├── app/Services/EmailService.php         ← Email service
│   ├── app/Notifications/                    ← ❌ НЕ СОЗДАНА (НУЖНО)
│   ├── app/Mail/                             ← ❌ НЕ СОЗДАНА (НУЖНО)
│   └── config/notification.php               ← ❌ НЕ СОЗДАН (НУЖНО)
│
├── 🎯 МАРКЕТИНГ
│   ├── app/Services/Marketing/
│   │   ├── PromoCampaignService.php          ← Service exists
│   │   └── ReferralService.php               ← Service exists
│   ├── app/Models/PromoCampaign.php          ← ❌ НЕ СОЗДАН (НУЖНО)
│   ├── app/Models/Referral.php               ← ❌ НЕ СОЗДАН (НУЖНО)
│   ├── app/Models/Bonus.php                  ← ❌ НЕ СОЗДАН (НУЖНО)
│   └── database/migrations/promo*            ← ❌ НЕ СОЗДАНЫ (НУЖНО)
│
├── 📊 АНАЛИТИКА
│   ├── app/Services/AnalyticsService.php
│   ├── app/Services/AI/
│   │   ├── DemandForecastService.php
│   │   ├── PriceSuggestionService.php
│   │   └── RecommendationService.php
│   └── app/Jobs/
│       ├── DemandForecastJob.php
│       └── RecommendationQualityJob.php
│
├── 👥 HR & ПЕРСОНАЛ
│   ├── app/Domains/HR/                       ← ❌ НЕ СОЗДАНА (НУЖНО)
│   │   ├── Models/Employee.php
│   │   ├── Models/Department.php
│   │   ├── Services/EmployeeManagementService.php
│   │   └── Filament/Resources/
│   ├── app/Services/HRService.php            ← Существует (нужно расширить)
│   └── database/seeders/HRSeeder.php
│
├── 💰 PAYROLL & ЗАРПЛАТЫ
│   ├── app/Domains/Payroll/                  ← ❌ НЕ СОЗДАНА (CRITICAL!)
│   │   ├── Models/Payroll.php
│   │   ├── Models/SalaryComponent.php
│   │   ├── Services/PayrollCalculationService.php
│   │   └── Filament/Resources/PayrollResource.php
│   ├── app/Services/PayrollService.php       ← ❌ НЕ СОЗДАН (CRITICAL!)
│   ├── database/seeders/PayrollSeeder.php    ← Существует
│   └── app/Policies/PayrollPolicy.php        ← Существует (minimal)
│
├── 🚚 ЛОГИСТИКА & КУРЬЕРЫ
│   ├── app/Services/CourierService.php
│   ├── app/Services/GeoService.php
│   ├── app/Domains/Auto/
│   │   ├── Models/TaxiDriver.php
│   │   ├── Models/TaxiVehicle.php
│   │   ├── Models/TaxiRide.php
│   │   └── Services/
│   ├── app/Domains/Logistics/
│   ├── database/seeders/TaxiDriverSeeder.php
│   └── app/Jobs/ReleaseHoldJob.php
│
├── ⚙️ ОСНОВНЫЕ СЕРВИСЫ (14+)
│   ├── app/Services/
│   │   ├── AnalyticsService.php
│   │   ├── CourierService.php
│   │   ├── EmailService.php
│   │   ├── ExportService.php
│   │   ├── FraudControlService.php
│   │   ├── GeoService.php
│   │   ├── HRService.php
│   │   ├── ImportService.php
│   │   ├── NotificationService.php
│   │   ├── RateLimiterService.php
│   │   ├── RecommendationService.php
│   │   ├── SearchRankingService.php
│   │   ├── SearchService.php
│   │   └── WishlistService.php
│   │
│   ├── app/Services/AI/          (3 services)
│   ├── app/Services/Fraud/       (1 service - FraudMLService)
│   ├── app/Services/Infrastructure/ (Doppler)
│   ├── app/Services/Inventory/   (InventoryManagementService)
│   ├── app/Services/Marketing/   (Promo + Referral)
│   ├── app/Services/Payment/     (4 services + Gateways)
│   ├── app/Services/Security/    (7 services) ⭐ BEST
│   ├── app/Services/Wallet/      (WalletService)
│   ├── app/Services/Webhook/     (WebhookSignatureValidator)
│   └── app/Services/Wishlist/    (WishlistService)
│
├── 🔌 API & CONTROLLERS
│   ├── app/Http/Controllers/Api/
│   │   ├── V1/
│   │   │   ├── AuthController.php
│   │   │   ├── BaseApiV1Controller.php
│   │   │   ├── HealthCheckController.php
│   │   │   ├── PaymentController.php
│   │   │   └── WalletController.php
│   │   ├── V2/                   ← ❌ To be created (TBD)
│   │   ├── OpenApiController.php
│   │   └── PaymentController.php
│   │
│   ├── app/Http/Requests/
│   │   ├── BaseApiRequest.php
│   │   ├── CreateApiKeyRequest.php
│   │   ├── PaymentInitRequest.php
│   │   ├── PromoApplyRequest.php
│   │   └── ReferralClaimRequest.php
│   │
│   ├── routes/
│   │   ├── api.php               ← Main API routes
│   │   ├── {vertical}.php        ← 20+ vertical routes
│   │   ├── b2b.{vertical}.php    ← 25+ B2B routes
│   │   ├── tenant.php            ← Filament routes
│   │   └── web.php               ← Web routes
│   │
│   └── app/Http/Middleware/
│       ├── CheckAbilityMiddleware ← ❌ НЕ СОЗДАН (НУЖНО)
│       └── [Others existing]
│
├── 💾 ПОЛИТИКИ ДОСТУПА
│   └── app/Policies/             (16 Policies)
│       ├── AppointmentPolicy.php
│       ├── BeautyPolicy.php
│       ├── BonusPolicy.php
│       ├── CommissionPolicy.php
│       ├── EmployeePolicy.php
│       ├── HotelPolicy.php
│       ├── InventoryPolicy.php
│       ├── OrderPolicy.php
│       ├── PaymentPolicy.php
│       ├── PayoutPolicy.php
│       ├── PayrollPolicy.php
│       ├── ProductPolicy.php
│       ├── ReferralPolicy.php
│       ├── TenantPolicy.php
│       ├── WalletManagementPolicy.php (NEW)
│       └── WalletPolicy.php
│
├── ⚡ JOBS (9 всего)
│   └── app/Jobs/
│       ├── BonusAccrualJob.php
│       ├── CleanupExpiredBonusesJob.php
│       ├── CleanupExpiredIdempotencyRecordsJob.php
│       ├── DemandForecastJob.php
│       ├── FraudMLRecalculationJob.php
│       ├── LowStockNotificationJob.php
│       ├── PayoutProcessingJob.php
│       ├── RecommendationQualityJob.php
│       └── ReleaseHoldJob.php
│
├── 🎬 СОБЫТИЯ & СЛУШАТЕЛИ
│   ├── app/Events/                ← ❌ Minimal (НУЖНО дополнить)
│   ├── app/Listeners/             ← ❌ Minimal (НУЖНО дополнить)
│   └── app/Listeners/Octane/      ← Octane-specific only
│
├── 🗂️ ОСНОВНЫЕ МОДЕЛИ (9)
│   └── app/Models/
│       ├── BalanceTransaction.php
│       ├── BusinessGroup.php
│       ├── PaymentIdempotencyRecord.php
│       ├── PaymentTransaction.php
│       ├── PersonalAccessToken.php
│       ├── Tenant.php
│       ├── TenantUser.php
│       ├── User.php
│       └── Wallet.php
│
├── 🏢 ВЕРТИКАЛИ (DOMAINS) - 22 ШТУК
│   └── app/Domains/
│       ├── Auto/                 (40+ files)
│       ├── Beauty/               (41 files)
│       ├── Courses/
│       ├── Entertainment/
│       ├── Fashion/
│       ├── FashionRetail/
│       ├── Fitness/
│       ├── Flowers/
│       ├── Food/
│       ├── Freelance/
│       ├── HomeServices/
│       ├── Hotels/
│       ├── Logistics/
│       ├── Medical/
│       ├── MedicalHealthcare/
│       ├── Pet/
│       ├── PetServices/
│       ├── Photography/
│       ├── RealEstate/
│       ├── Sports/
│       ├── Tickets/
│       ├── Travel/
│       └── TravelTourism/
│
├── 🎨 FILAMENT (Admin Interface)
│   └── app/Filament/
│       ├── Admin/
│       ├── Tenant/
│       │   └── Resources/
│       └── Public/
│
├── ⚠️ ИСКЛЮЧЕНИЯ (3 существует, нужны +5)
│   └── app/Exceptions/
│       ├── DuplicatePaymentException.php
│       ├── InvalidPayloadException.php
│       ├── RateLimitException.php
│       ├── InsufficientStockException.php      ← ❌ (НУЖНО)
│       ├── FraudDetectedException.php          ← ❌ (НУЖНО)
│       ├── PaymentGatewayException.php         ← ❌ (НУЖНО)
│       ├── QuotaExceededException.php          ← ❌ (НУЖНО)
│       └── InvalidMigrationException.php       ← ❌ (НУЖНО)
│
├── 🏭 ФАБРИКИ (20 всего)
│   └── database/factories/
│       ├── AdCampaignFactory.php
│       ├── BusinessBranchFactory.php
│       ├── CourseFactory.php
│       ├── DeliveryOrderFactory.php
│       ├── EventFactory.php
│       ├── FoodOrderFactory.php
│       ├── GeoZoneFactory.php
│       ├── HotelBookingFactory.php
│       ├── InsurancePolicyFactory.php
│       ├── InventoryItemFactory.php
│       ├── MedicalCardFactory.php
│       ├── MessageFactory.php
│       ├── PaymentTransactionFactory.php
│       ├── PropertyFactory.php
│       ├── SalonFactory.php
│       ├── SportsMembershipFactory.php
│       ├── TaxiRideFactory.php
│       ├── TenantFactory.php
│       ├── UserFactory.php
│       └── WalletFactory.php
│
├── 🌱 СИДЕРЫ (115+ всего)
│   └── database/seeders/
│       ├── DatabaseSeeder.php
│       ├── RolesAndPermissionsSeeder.php
│       ├── TenantMasterSeeder.php
│       ├── ProductionMasterSeeder.php
│       ├── CRM/ (подпапка)
│       ├── Marketplace/ (подпапка)
│       ├── tenant/ (подпапка)
│       ├── [40+ vertical seeders]
│       ├── [20+ brand seeders]
│       └── [15+ filter seeders]
│
├── 📚 МИГРАЦИИ (55 всего)
│   └── database/migrations/
│       ├── 0001_01_01_000000_create_users_table.php
│       ├── 0001_01_01_000001_create_cache_table.php
│       ├── 0001_01_01_000002_create_jobs_table.php
│       ├── [52 more migrations]
│       └── [Create specific tables: auth, payment, wallet, etc.]
│
├── ⚙️ КОНФИГИ (10)
│   └── config/
│       ├── app.php
│       ├── auth.php
│       ├── cache.php
│       ├── database.php
│       ├── filesystems.php
│       ├── logging.php
│       ├── mail.php
│       ├── queue.php
│       ├── services.php
│       ├── session.php
│       ├── permission.php                 ← ❌ (НУЖНО)
│       └── notification.php               ← ❌ (НУЖНО)
│
└── 🛠️ ИНСТРУМЕНТЫ
    ├── artisan                  ← Laravel CLI
    ├── composer.json            ← PHP dependencies
    ├── package.json             ← Node dependencies
    ├── .env.example             ← Environment template
    ├── phpstan.neon             ← Static analysis config
    └── [Other config files]

```

---

## 📋 БЫСТРЫЙ ПОИСК ПО ЗАДАЧАМ

### "Нужно добавить авторизацию"
→ Посмотри:
- `/app/Enums/Role.php` - Roles enum
- `/app/Policies/` - Authorization policies
- `/config/auth.php` - Auth config
- **НУЖНО СОЗДАТЬ:** `/app/Services/Auth/AuthService.php`, `/config/permission.php`

### "Как работает платежи?"
→ Посмотри:
- `/app/Services/Payment/PaymentGatewayService.php` - Main service
- `/app/Services/Payment/Gateways/` - Gateways (Sber, Tinkoff, Tochka)
- `/app/Http/Controllers/Api/V1/PaymentController.php` - API
- `/app/Models/PaymentTransaction.php` - Model
- `/database/migrations/` - Related migrations

### "Как работает борьба с фродом?"
→ Посмотри:
- `/app/Services/Fraud/FraudMLService.php` - ML-based fraud detection
- `/app/Services/Security/FraudControlService.php` - Control service
- `/app/Services/Security/RateLimiterService.php` - Rate limiting
- `/app/Services/Security/WebhookSignatureService.php` - Signature verification
- `/app/Jobs/FraudMLRecalculationJob.php` - ML training job

### "Как работает рекомендация?"
→ Посмотри:
- `/app/Services/RecommendationService.php` - Main service
- `/app/Services/AI/RecommendationService.php` - AI version (если дублируется)
- `/app/Jobs/RecommendationQualityJob.php` - Quality metrics
- `/database/migrations/` - Related tables

### "Как создать новую вертикаль?"
→ Посмотри:
- `/app/Domains/Beauty/` - Example domain structure
- `/app/Domains/Food/` - Another example
- `/routes/beauty.php` - Vertical routes example
- `/database/seeders/BeautyShopSeeder.php` - Seeder example
- **FOLLOW PATTERN:** Models → Services → Resources → Jobs → Events → Routes

### "Как добавить новый API endpoint?"
→ Посмотри:
- `/app/Http/Controllers/Api/V1/PaymentController.php` - Example controller
- `/routes/api.php` - Main API routes
- `/app/Http/Requests/PaymentInitRequest.php` - Example FormRequest
- `/app/Http/Requests/BaseApiRequest.php` - Base request class
- `/app/Http/Middleware/` - Middleware examples

### "Как работает кошелёк?"
→ Посмотри:
- `/app/Services/Wallet/WalletService.php` - Wallet service
- `/app/Models/Wallet.php` - Wallet model
- `/app/Models/BalanceTransaction.php` - Transactions
- `/app/Http/Controllers/Api/V1/WalletController.php` - API
- `/database/migrations/` - Wallet schema

### "Как работает маркетинг?"
→ Посмотри:
- `/app/Services/Marketing/PromoCampaignService.php` - Promo campaigns
- `/app/Services/Marketing/ReferralService.php` - Referral program
- `/database/seeders/NewsletterSeeder.php` - Marketing seeders
- **НУЖНО СОЗДАТЬ:** Models (PromoCampaign, Referral, Bonus)

### "Как работает inventory?"
→ Посмотри:
- `/app/Services/Inventory/InventoryManagementService.php` - Main service
- `/app/Jobs/LowStockNotificationJob.php` - Notifications
- `/app/Domains/Beauty/Services/InventoryManagementService.php` - Vertical example

### "Как работает Filament (Admin Panel)?"
→ Посмотри:
- `/app/Filament/` - Filament structure
- `/app/Filament/Tenant/Resources/` - Resources for tenant panel
- `/config/filament.php` - Filament config (если есть)
- `/database/seeders/RolesAndPermissionsSeeder.php` - Roles setup

---

## 🚀 КОМАНДЫ ДЛЯ БЫСТРОГО СТАРТА

```bash
# Проверить текущий статус
php artisan route:list | head -20

# Запустить все миграции
php artisan migrate

# Запустить все seeders
php artisan db:seed

# Запустить тесты
php artisan test

# Проверить синтаксис (PHPStan)
./vendor/bin/phpstan analyse

# Запустить сервер
php artisan serve

# Запустить queue worker
php artisan queue:work

# Генерировать API документацию
php artisan l5-swagger:generate

# Кэшировать конфиги
php artisan config:cache

# Кэшировать routes
php artisan route:cache
```

---

## 📖 ДОКУМЕНТАЦИЯ

Основные документы в проекте:
- `PROJECT_STRUCTURE_ANALYSIS_CANON_2026.md` - Полный анализ (этот файл)
- `IMPLEMENTATION_PLAN_4DAYS.md` - План на 4 дня
- `MODULE_SUMMARY_AND_STATUS.md` - Сводка по модулям
- `CANON_2026_PRODUCTION_UPGRADE_REPORT_03_18.md` - Production report
- `README.md` (если есть) - General project info

---

**Создано:** 18 марта 2026 г.  
**Версия:** CANON 2026 Production-Ready  
**Статус:** Navigation guide ready ✅

