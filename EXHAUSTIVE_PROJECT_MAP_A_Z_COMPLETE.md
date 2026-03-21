# EXHAUSTIVE PROJECT MAP — CatVRF 2026
> **Актуальность:** 20 марта 2026 г. | Честная карта, без стабов и фантазий

---

## 📊 РЕАЛЬНАЯ СТАТИСТИКА ПРОЕКТА

| Компонент | Реальное кол-во |
|---|---|
| Вертикали (app/Domains) | **41** |
| PHP-файлов в доменах | **932** |
| Core Services (app/Services) | **130** |
| Filament-файлов | **143** |
| Миграций | **73** |
| Фабрик | **65** |
| Сидеров | **150** |
| Тестов | **121** |
| Config-файлов | **14** |
| Routes-файлов | **48** |
| Core Models (app/Models) | **11** |
| RBAC Policies | **16** |

**ИТОГО PHP-файлов в проекте:** ~1 500+ (реальные, без завышения)

---

## 🟢 СТАТУС ВЕРТИКАЛЕЙ

### Легенда
- ✅ **Полная** — ≥30 PHP-файлов, есть Models + Services + Events + Jobs + Filament
- ⚠️ **Частичная** — 3–29 файлов, базовая структура без полного набора
- ❌ **Стаб** — 1–2 файла, требует полной реализации

---

### ✅ ПОЛНЫЕ ВЕРТИКАЛИ (20 из 41)

| Вертикаль | Путь | Файлов | Models | Services | Статус |
|---|---|---|---|---|---|
| Auto | app/Domains/Auto | 47 | 11 | 6 | ✅ Полная |
| Beauty | app/Domains/Beauty | 47 | 10 | 7 | ⚠️ 52% — нет UI тестов |
| Courses | app/Domains/Courses | 39 | 11 | 3 | ✅ Полная |
| Entertainment | app/Domains/Entertainment | 55 | 11 | 4 | ✅ Полная |
| Fashion | app/Domains/Fashion | 40 | 10 | 4 | ✅ Полная |
| FashionRetail | app/Domains/FashionRetail | 25 | 10 | 4 | ⚠️ Нет B2B API |
| Fitness | app/Domains/Fitness | 56 | 11 | 5 | ✅ Полная |
| Flowers | app/Domains/Flowers | 36 | 9 | 4 | ✅ Полная |
| Food | app/Domains/Food | 46 | 11 | 6 | ✅ Полная |
| Freelance | app/Domains/Freelance | 43 | 11 | 6 | ✅ Полная |
| HomeServices | app/Domains/HomeServices | 57 | 12 | 5 | ✅ Полная |
| Hotels | app/Domains/Hotels | 46 | 12 | 6 | ✅ Полная |
| Logistics | app/Domains/Logistics | 43 | 13 | 5 | ✅ Полная |
| Medical | app/Domains/Medical | 40 | 10 | 5 | ✅ Полная |
| Pet | app/Domains/Pet | 40 | 10 | 5 | ✅ Полная |
| Photography | app/Domains/Photography | 36 | 9 | 5 | ✅ Полная |
| RealEstate | app/Domains/RealEstate | 39 | 10 | 4 | ✅ Полная |
| Sports | app/Domains/Sports | 40 | — | — | ✅ Полная |
| Tickets | app/Domains/Tickets | 40 | — | — | ✅ Полная |
| Travel | app/Domains/Travel | 42 | — | — | ✅ Полная |

**Beauty помечена отдельно:** 52% — есть Models/Services/Events, но отсутствуют:
- UI-тесты записи
- Livewire-компоненты Календаря
- Полный ConsumableDeductionService (есть событие `ConsumableDeducted`, но нет сквозного теста)

---

### ⚠️ ЧАСТИЧНЫЕ ВЕРТИКАЛИ (10 из 41)

| Вертикаль | Файлов | Чего не хватает |
|---|---|---|
| Books | 5 | Нет Filament Resource, нет миграций, нет фабрик |
| ConstructionMaterials | 5 | Нет Models полного набора, нет Events |
| Cosmetics | 4 | Нет Filament, нет Jobs, нет тестов |
| Gifts | 3 | Нет Filament, нет событий, нет фабрики |
| Jewelry | 6 | Есть 3D-сервис и Filament3DResource, нет полных CRUD и тестов |
| MedicalHealthcare | 7 | Нет Events, нет Jobs, нет тестов |
| MedicalSupplies | 3 | Нет Filament, нет фабрики |
| PetServices | 9 | Нет Events, нет Jobs, нет тестов |
| SportingGoods | 3 | Нет Filament, нет событий |
| TravelTourism | 8 | Нет Events, нет Jobs, нет тестов |

---

### ❌ СТАБ-ВЕРТИКАЛИ (11 из 41) — требуют полной реализации

| Вертикаль | Файлов | Что есть (буквально) |
|---|---|---|
| AutoParts | 2 | AutoPartItem.php + VINCompatibilityService.php |
| Confectionery | 2 | 1 Model + 1 Service |
| Electronics | 2 | ElectronicProduct.php + WarrantyService.php |
| FarmDirect | 2 | ProduceBox(?) + FarmDirectService |
| FreshProduce | 2 | ProduceBox.php + FreshProduceService.php |
| Furniture | 2 | FurnitureItem.php + DeliveryAssemblyService.php |
| HealthyFood | 2 | 1 Model + 1 Service |
| MeatShops | 2 | 1 Model + 1 Service |
| OfficeCatering | 2 | 1 Model + 1 Service |
| Pharmacy | 2 | Medicine.php + PharmacyService.php |
| ToysKids | 2 | ToyProduct.php + ToyOrderService.php |

> ⚠️ Все 11 стаб-вертикалей имеют дублирующие 3D-сервисы в `app/Services`, но сами домены пустые.

---

## 💰 ПЛАТЁЖНАЯ СИСТЕМА

### Модели (app/Models)
```
app/Models/
├── Wallet.php                    ✅ uuid, tenant_id, current_balance, hold_amount
├── BalanceTransaction.php        ✅ type: deposit/withdrawal/commission/bonus/refund/payout
├── PaymentTransaction.php        ✅ idempotency_key, provider_code, status, hold
├── PaymentIdempotencyRecord.php  ✅
├── InventoryItem.php             ✅
└── StockMovement.php             ✅
```

### Сервисы (app/Services)
```
app/Services/
├── WalletService.php             ✅ credit/debit/hold/release, DB::transaction, lockForUpdate
├── PaymentGatewayInterface.php   ✅ initPayment/capture/refund/payout/handleWebhook/fiscalize
├── PaymentGatewayService.php     ✅
├── TinkoffGateway.php            ✅ основной шлюз
├── TochkaGateway.php             ✅
├── SberGateway.php               ✅
├── IdempotencyService.php        ⚠️ ДУБЛИКАТ в двух подпапках!
├── PaymentIdempotencyService.php ✅
├── FiscalService.php             ✅ ОФД / 54-ФЗ
└── WebhookSignatureService.php   ✅ HMAC-SHA256
```

### Миграции платёжной системы
```
database/migrations/
├── 2018_11_15_..._create_wallets_table.php                 ✅
├── 2021_11_02_..._update_wallets_uuid_table.php            ✅
├── 2026_03_06_..._create_agency_referrals_table.php        ✅
├── 2026_03_06_..._create_fraud_payout_tables.php           ✅
├── 2026_03_06_..._create_fraud_infrastructure_tables.php   ✅
├── 2026_03_19_..._rebuild_wallets_canonical.php            ✅ REBUILD
├── 2026_03_19_..._create_balance_transactions_table.php    ✅
├── 2026_03_20_..._patch_payment_transactions_missing_cols.php  ⚠️ PATCH
├── 2026_03_20_..._rebuild_payment_transactions_nullable.php    ⚠️ PATCH
└── 2026_03_20_..._create_fraud_inventory_enrollment_tables.php ✅
```

> ⚠️ **КРИТИЧНО:** payment_transactions дважды патчилась 20 марта — оригинальная миграция содержала ошибки. Требует ревью и объединения.

### Банковские шлюзы
| Шлюз | Файл | Статус |
|---|---|---|
| Tinkoff | TinkoffGateway.php | ✅ |
| Точка Банк | TochkaGateway.php | ✅ |
| Сбербанк | SberGateway.php | ✅ |
| СБП | — | ❌ Отсутствует |
| Recurrent / подписки | — | ❌ Отсутствует |

---

## 🔐 RBAC И РАЗДЕЛЕНИЕ ДОСТУПА

### Политики (app/Policies) — 16 файлов
```
app/Policies/
├── AppointmentPolicy.php      ✅
├── BeautyPolicy.php           ✅
├── BonusPolicy.php            ✅
├── CommissionPolicy.php       ✅
├── EmployeePolicy.php         ✅
├── HotelPolicy.php            ✅
├── InventoryPolicy.php        ✅
├── OrderPolicy.php            ✅
├── PaymentPolicy.php          ✅
├── PayoutPolicy.php           ✅
├── PayrollPolicy.php          ✅
├── ProductPolicy.php          ✅
├── ReferralPolicy.php         ✅
├── TenantPolicy.php           ✅
├── WalletManagementPolicy.php ✅
└── WalletPolicy.php           ✅
```

### Core Models User/Tenant
```
app/Models/
├── User.php          ✅ стандартная модель пользователя
├── TenantUser.php    ✅ связь пользователя с тенантом
├── Tenant.php        ✅ мультитенантность (stancl/tenancy)
└── BusinessGroup.php ✅ филиалы (ИНН-изоляция)
```

### Разделение панелей Filament (app/Filament)
```
app/Filament/
├── Admin/            ✅ суперадмин-панель
├── Tenant/           ✅ ЛК бизнеса (tenant-scoped)
│   └── Resources/    ✅ ресурсы вертикалей
└── B2B/              ⚠️ частично (есть для 15+ вертикалей)
```

> ⚠️ **Дыра:** Нет явного `User CRM` (список клиентов тенанта с историей заказов) как отдельного Filament Resource. Роли `accountant` и `manager` описаны в config/rbac.php, но гейты не зарегистрированы глобально.

---

## ❤️ WISHLIST + РАНЖИРОВАНИЕ + АНТИФРОД

### Текущее состояние
```
app/Services/
├── WishlistService.php          ⚠️ ДУБЛИКАТ (два файла в разных подпапках)
├── WishlistAntiFraudService.php ✅
├── SearchRankingService.php     ⚠️ ДУБЛИКАТ (два файла)
├── SearchService.php            ✅
├── LiveSearchService.php        ✅
└── RecommendationService.php    ⚠️ ДУБЛИКАТ (два файла)
```

### Статус компонентов
| Компонент | Статус | Детали |
|---|---|---|
| WishlistService | ⚠️ | Есть, но дубликат — нужна консолидация |
| WishlistAntiFraudService | ✅ | Защита от накрутки вишлиста |
| SearchRankingService | ⚠️ | Дубликат — нужна консолидация |
| LiveSearchService | ✅ | |
| RecommendationService | ⚠️ | Дубликат + нет тестов |
| RecommendationMLService | ✅ | |
| RecommendationEngine | ✅ | |
| Отключение персонализации (70%) | ❌ | Feature flag не реализован |
| A/B тест рекомендаций | ❌ | Не реализован |
| Интеграция с Typesense | ❌ | Векторный поиск не подключён |

---

## 🤖 ML / АНТИФРОД

### Fraud-сервисы (app/Services)
```
app/Services/
├── FraudControlService.php       ⚠️ ДУБЛИКАТ (два файла!)
├── FraudDetectionMLService.php   ✅
├── FraudDetectionService.php     ✅
├── FraudMLService.php            ✅ основной
└── WishlistAntiFraudService.php  ✅
```

> ⚠️ `FraudControlService.php` присутствует дважды — критический баг. Может приводить к неправильному resolve через IoC-контейнер.

### ML-сервисы
```
app/Services/
├── DemandForecastService.php           ✅
├── DemandForecastMLService.php         ✅
├── PriceSuggestionService.php          ✅
├── PriceSuggestionMLService.php        ✅
├── RecommendationMLService.php         ✅
├── CustomerLifetimeValueMLService.php  ✅
└── SegmentationService.php             ✅
```

---

## 🗄️ РЕАЛЬНОЕ СОСТОЯНИЕ МИГРАЦИЙ

**Всего:** 73 файла

> ⚠️ **ИСТОРИЯ:** Copilot удалял и пересоздавал миграции. Текущие патчи (2026_03_20) свидетельствуют о проблемах в оригинальных миграциях payment_transactions.

### Критичные таблицы — статус
| Таблица | Миграция | Статус |
|---|---|---|
| wallets | 2018 + rebuild 2026_03_19 | ✅ |
| balance_transactions | 2026_03_19 | ✅ |
| payment_transactions | Патч 03_20 x2 | ⚠️ |
| payment_idempotency_records | — | ❌ Нет отдельной миграции |
| fraud_attempts | 2026_03_06 | ✅ |
| fraud_model_versions | 2026_03_06 | ✅ |
| inventory_items | 2026_03_20 | ✅ |
| stock_movements | 2026_03_20 | ✅ |
| bonus_transactions | — | ❌ Требует проверки |
| promo_campaigns | — | ❌ Требует проверки |
| referrals | 2026_03_06 | ✅ |

---

## 🏗️ CORE СЕРВИСНАЯ ШИНА (app/Services — 130 файлов)

### Финансы
- WalletService, PaymentGatewayInterface, PaymentGatewayService, TinkoffGateway, TochkaGateway, SberGateway, FiscalService

### Безопасность
- FraudMLService, FraudControlService ⚠️(дубл), FraudDetectionMLService, FraudDetectionService, IdempotencyService ⚠️(дубл), WebhookSignatureService, WebhookSignatureValidator, RateLimiterService ⚠️(дубл)

### Поиск и рекомендации
- SearchService, SearchRankingService ⚠️(дубл), LiveSearchService, RecommendationService ⚠️(дубл), RecommendationMLService, RecommendationEngine

### ML/AI
- DemandForecastService, DemandForecastMLService, PriceSuggestionService, PriceSuggestionMLService, CustomerLifetimeValueMLService, SegmentationService, AdvancedAnalyticsService

### Инфраструктура
- GeoService, NotificationService, EmailService, ImportService, ExportService ⚠️(дубл), HRService, PromoCampaignService, ReferralService, InventoryManagementService, DopplerService, ApiKeyManagementService, LogManager, ReportingService, SecurityAuditService, MonitoringAlertingService

### 3D/AR (42 сервиса)
- `{Vertical}3DService.php` для всех 41 доменов
- У 11 стаб-вертикалей 3D-сервис — единственный файл домена

---

## ⚠️ ПОТЕРЯННЫЕ ВЕРТИКАЛИ ИЗ КАНОНА 2026

**Стабы (1–2 файла) — требуют полной реализации:**

| Вертикаль | Приоритет |
|---|---|
| ToysKids | 🔴 Высокий |
| Electronics | 🔴 Высокий |
| Furniture | 🔴 Высокий |
| Pharmacy | 🔴 Высокий |
| FreshProduce | 🟡 Средний |
| HealthyFood | 🟡 Средний |
| MeatShops | 🟡 Средний |
| Confectionery | 🟡 Средний |
| OfficeCatering | 🟡 Средний |
| FarmDirect | 🟠 Низкий |
| AutoParts | 🟠 Низкий |

**Не созданы вообще (нет даже папки в app/Domains):**
BathsSaunas, Billiards, BoardGames, Bars, HookahLounges, Karaoke,
QuestRooms, DanceStudios, DrivingSchools, KidsCenters, KidsPlayCenters,
YogaPilates, EscapeRooms, EventVenues, PartyVenues, ShortTermRentals

---

## 🔗 ИНТЕГРАЦИИ ВЕРТИКАЛЕЙ С CORE-МОДУЛЯМИ

```
Каждая ПОЛНАЯ вертикаль должна подключать:
  ├── WalletService         → при завершении заказа/услуги
  ├── PaymentGatewayService → при инициации оплаты
  ├── FraudMLService        → перед каждой мутацией
  ├── InventoryManagementService → списание расходников
  ├── RecommendationService → виджет «рекомендуем вам»
  ├── DemandForecastService → прогноз спроса
  ├── PromoCampaignService  → применение акций
  └── ReferralService       → реферальные бонусы

Подтверждённые интеграции (есть Events/Listeners):
  ✅ Beauty       → WalletService + Events(AppointmentCompleted, ConsumableDeducted)
  ✅ Auto         → WalletService + Events(RideCompleted, SurgeUpdated)
  ✅ Food         → InventoryManagementService (DeductOrderConsumables)
  ✅ Hotels       → WalletService (выплата через 4 дня)

Требуют проверки интеграции:
  ⚠️ FashionRetail → нет B2B API
  ⚠️ Sports        → нет связи с Inventory
  ⚠️ TravelTourism → нет связи с WalletService
```

---

## 🚨 КРИТИЧЕСКИЕ ДЫРЫ ПЕРЕД РЕЛИЗОМ

### P0 — Блокирующие

| # | Проблема | Решение |
|---|---|---|
| 1 | **Дублирующие сервисы** (FraudControl×2, Wishlist×2, RateLimiter×2, Export×2, Recommendation×2) | Удалить дубликаты, оставить по одному |
| 2 | **payment_transactions** патчилась дважды | Аудит миграций, создать чистую единую |
| 3 | **payment_idempotency_records** — нет миграции (модель есть) | Создать миграцию |
| 4 | **SBP шлюз** отсутствует | Создать SBPGateway.php |
| 5 | **Beauty 52%** — нет Livewire-компонентов и UI-тестов | Дописать |

### P1 — Критично

| # | Проблема | Решение |
|---|---|---|
| 6 | 11 стаб-вертикалей по 2 файла | Полная реализация по канону |
| 7 | Нет User CRM в Filament/Tenant | Создать UserCrmResource |
| 8 | Feature flag отключения рекомендаций (70%) | config/features.php |
| 9 | Роли manager/accountant не зарегистрированы в Gate | AuthServiceProvider |
| 10 | bonus_transactions и promo_campaigns — статус миграций неясен | Проверить и создать |
| 11 | Wishlist дубликат + нет Feature-тестов | Консолидация |

### P2 — Важно

| # | Проблема |
|---|---|
| 12 | Нет 16 вертикалей из КАНОНА (Bars, Baths и т.д.) |
| 13 | Нет recurrent/подписочных платежей |
| 14 | Нет интеграции Typesense для векторного поиска |
| 15 | A/B тесты рекомендаций не реализованы |

---

## 📋 ИТОГОВЫЙ ОТЧЁТ

```
=== ИТОГОВЫЙ ОТЧЁТ ПО КАРТЕ ===

Общее количество вертикалей: 41
  ✅ Полных:      18 (без Beauty и FashionRetail которые частичные)
  ⚠️ Частичных:   12 (включая Beauty 52%, FashionRetail, + 10 из списка)
  ❌ Стабов:      11 (по 2 файла каждая)
  🚫 Не созданы:  16 (из КАНОНА 2026)

PHP-файлов реально: ~1 500 (не 3 000+ как было в старой карте)

Критические дыры (топ-5):
  1. Дубликаты сервисов — риск неправильного IoC resolve
  2. payment_transactions — двойной патч = плохая оригинальная миграция
  3. payment_idempotency_records — нет миграции
  4. SBP шлюз — отсутствует
  5. Beauty — 52%, заявлена как полная

Что удалено как фантазия из старой карты:
  - Завышенные цифры (>2 000 файлов)
  - Несуществующие B2BModels.php
  - "Полный" статус для стаб-вертикалей
  - Несуществующие Wishlist* в доменных папках

Что добавлено как реальные данные:
  - Раздел платёжной системы с реальными файлами
  - 16 реальных Policies (RBAC)
  - Core Models: реальный список 11 файлов
  - Дубликаты сервисов как баги (не фичи)
  - Патчи миграций как предупреждение
  - 16 несозданных вертикалей из КАНОНА
```

---

*Карта обновлена: 20 марта 2026 г. | На основе реального сканирования файловой системы*