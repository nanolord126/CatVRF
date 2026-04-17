# ПЛАН РАБОТ Q1 — CatVRF 2026
**Версия:** 1.1  
**Дата:** 15.04.2026  
**Статус:** ACTIVE — Sprint 1 ✅ ЗАВЕРШЁН  
**Ответственный:** Сенсей (ex-Amazon, Alibaba, Ozon)

---

## ОБОЗНАЧЕНИЯ СТАТУСОВ

| Символ | Смысл |
|--------|-------|
| ✅ | Готово (production-ready) |
| ⚠️ | Есть код, но нужна доработка / проверка |
| ❌ | Отсутствует, нужно создать |
| 🔄 | В работе |

---

## ОБЩИЙ СТАТУС Q1 (34 ДОМЕНА)

### ГРУППА A — Исходные домены (18 штук, код есть в app/Domains/)

| # | Домен | app/Domains/ | Тесты | Миграции | Статус |
|---|-------|-------------|-------|----------|--------|
| 1 | Wallet | ✅ 31 файл | ✅ Unit+Feature+Integration+Performance | ✅ wallets, balance_transactions | ✅ **ГОТОВ** |
| 2 | Payment | ✅ 36 файлов | ✅ Unit+Feature+E2E+Performance | ✅ payment_transactions, payment_idempotency_records | ✅ **ГОТОВ** |
| 3 | Finances | ✅ 56 файлов | ✅ Unit (finances, bonus, payout) | ✅ finances_records | ⚠️ **Проверить Payout** |
| 4 | Inventory | ✅ 46 файлов | ✅ Unit (многоуровневые) | ✅ inventory_audits, inventories | ✅ **ГОТОВ** |
| 5 | Analytics | ✅ 42 файла | ✅ Unit+Feature | ✅ analytics_events | ✅ **ГОТОВ** |
| 6 | AI | ✅ 24 файла | ✅ Unit | ✅ user_ai_designs (проверить!) | ⚠️ **Проверить user_ai_designs** |
| 7 | FraudML | ✅ 22 файла | ✅ Unit+E2E+Performance | ✅ fraud_attempts, fraudml_models, fraud_notifications | ✅ **ГОТОВ** |
| 8 | Geo | ✅ 24 файла | ✅ Unit+Feature | ✅ geo_shipments | ✅ **ГОТОВ** |
| 9 | GeoLogistics | ✅ 46 файлов | ✅ Unit+Feature | ✅ geo_logistics_shipments | ✅ **ГОТОВ** |
| 10 | Recommendation | ✅ 23 файла | ✅ Unit+Feature | ✅ recommendations | ✅ **ГОТОВ** |
| 11 | DemandForecast | ✅ 24 файла | ✅ Unit+Feature | ✅ demand_forecasts | ✅ **ГОТОВ** |
| 12 | Referral | ✅ 31 файл | ✅ Unit+Feature | ✅ referrals | ✅ **ГОТОВ** |
| 13 | Staff | ✅ 68 файлов | ✅ Unit+Feature | ✅ staff, employees, payrolls | ✅ **ГОТОВ** |
| 14 | Marketplace | ✅ 26 файлов | ✅ Unit+Feature | ✅ marketplace_listings | ✅ **ГОТОВ** |
| 15 | Advertising | ✅ 51 файл | ✅ Unit+Feature | ✅ promo_campaigns | ✅ **ГОТОВ** |
| 16 | Common | ✅ 29 файлов | ✅ Unit | ✅ common_entities | ✅ **ГОТОВ** |
| 17 | Communication | ✅ 24 файла | ✅ Unit | ✅ chat_rooms, chat_messages (15.04.2026) | ⚠️ **Проверить ChatService** |
| 18 | CRM | ✅ 66 файлов | ✅ Unit+Feature | ✅ crm_full_tables, crm_tables | ✅ **ГОТОВ** |

### ГРУППА B — Новые технические домены (16 штук, код в app/Services/)

| # | Домен | app/Services/ | Тесты | Миграции | Статус |
|---|-------|--------------|-------|----------|--------|
| 19 | ML | ✅ 8 файлов (app/Services/ML/) | ✅ Feature/ML, Unit/ML, Unit/Services | ✅ user_taste_profiles, anonymized_behavior | ⚠️ **Нет app/Domains/ML/** |
| 20 | BigData | ✅ BigDataAggregatorService.php | ✅ Unit/Services/BigDataAggregatorServiceTest | ✅ anonymized_behavior (ClickHouse) | ⚠️ **Только 1 сервис** |
| 21 | HR | ✅ app/Services/HR/ (2 файла) + HRService.php | ✅ Unit/Services/EmployeeServiceTest, PayrollServiceTest | ✅ employees, payrolls | ⚠️ **Тонко, нужно расширить** |
| 22 | Search | ✅ SearchService.php + LiveSearch + SearchRanking | ✅ Feature/Controllers/Api/V1/Search | ✅ search_queries (15.04.2026) | ⚠️ **Нет доменного теста** |
| 23 | Bonuses | ✅ app/Services/Bonus/BonusService.php, modules/Bonuses | ✅ Unit/Services/BonusServiceTest + Unit/Domains/Finances/BonusServiceTest | ✅ bonuses, bonus_transactions | ⚠️ **Нет app/Domains/Bonuses/** |
| 24 | Commissions | ✅ app/Services/CommissionService.php, modules/Commissions | ❌ нет доменного теста | ✅ commission_rules (EXISTS в auto_inventory) | ⚠️ **Нет доменного теста** |
| 25 | Payout | ✅ app/Services/Payout/ (2 файла) | ✅ Unit/Domains/Finances/Domain/Entities/PayoutTest | ✅ payout_requests | ⚠️ **Только 2 сервиса** |
| 26 | Audit | ✅ app/Services/AuditService.php | ✅ Unit/Services/AuditServiceTest | ✅ audit_logs (EXISTS в fraud_audit_kds) | ⚠️ **Проверить AuditLogJob** |
| 27 | Security | ✅ app/Services/Security/ (9 файлов) | ✅ Unit/Services/SecurityMonitoringServiceTest | ✅ security_events, fraud_notifications | ⚠️ **9 файлов, нет домена** |
| 28 | Notifications | ✅ NotificationService + Channel + Preferences (3 файла) | ✅ Integration/Notifications (5 тестов) + Unit/Notifications | ✅ notification_preferences (15.04.2026) | ⚠️ **Привязать сервис к таблице** |
| 29 | Cart | ✅ app/Services/CartService.php | ✅ Unit/Services/CartServiceTest | ✅ carts (2026_04_02) | ⚠️ **1 сервис, нет домена** |
| 30 | B2B | ✅ app/Services/B2B/ (2 файла) | ✅ Feature/Filament/B2BPanelTest | ✅ business_groups, b2b_api_keys | ⚠️ **2 сервиса, нет домена** |
| 31 | Webhooks | ✅ API/WebhookManagementService + Webhook/WebhookSignatureValidator | ❌ нет теста | ✅ webhook_endpoints, webhook_deliveries (15.04.2026) | ⚠️ **Нет тестов** |
| 32 | Realtime | ✅ RealtimeService + RealtimeChatService + RealtimeAnalyticsService + WebSocketConnectionService | ❌ нет теста | ✅ chat_rooms, chat_messages (15.04.2026) | ⚠️ **Нет тестов** |
| 33 | UserProfile | ✅ UserAddressService + UserActivityService | ✅ Feature/Controllers/Api/V1/UserProfile | ✅ user_addresses (15.04.2026) | ⚠️ **Привязать UserAddressService** |
| 34 | Compliance | ✅ Compliance/ (2 файла) + Security/ComplianceManagementService | ❌ нет теста | ✅ compliance_records (15.04.2026) | ⚠️ **Нет тестов** |

---

## СВОДКА ПО ПРОБЕЛАМ

### ✅ Миграции — Sprint 1 ЗАВЕРШЁН (15.04.2026) — 185 файлов

Все 8 отсутствующих миграций созданы:
1. ✅ `audit_logs` — существовала в `2026_03_25_000004_create_fraud_audit_kds_tables.php`
2. ✅ `commission_rules` — существовала в `2026_03_25_000003_create_auto_inventory_commission_tables.php`
3. ✅ `ai_constructions` — существовала в `2026_03_25_000002_create_ai_constructions_table.php`
4. ✅ `2026_04_15_000001_create_webhook_endpoints_table.php`
5. ✅ `2026_04_15_000002_create_webhook_deliveries_table.php`
6. ✅ `2026_04_15_000003_create_notification_preferences_table.php`
7. ✅ `2026_04_15_000004_create_user_addresses_table.php`
8. ✅ `2026_04_15_000005_create_search_queries_table.php`
9. ✅ `2026_04_15_000006_create_compliance_records_table.php`
10. ✅ `2026_04_15_000007_create_chat_rooms_table.php`
11. ✅ `2026_04_15_000008_create_chat_messages_table.php`

### ❌ Отсутствующие доменные тесты (7 доменов)
1. `Unit/Domains/Webhooks/` — нет
2. `Unit/Domains/Realtime/` — нет
3. `Unit/Domains/Compliance/` — нет
4. `Unit/Domains/Commissions/` — нет (есть только Service-level)
5. `Integration/Webhooks/` — нет
6. `Integration/Realtime/` — нет
7. `Feature/Domains/B2B/` — нет (есть Filament тест)

### ⚠️ Доработки кода (5 доменов)
1. **AI** — проверить/создать миграцию `user_ai_designs`
2. **Communication** — добавить миграцию chat_rooms/messages
3. **HR** — расширить (ShiftScheduling, TimeTracking, LeaveManagement сервисы)
4. **BigData** — добавить ClickHouse партиционирование и индексы
5. **Realtime** — добавить WebSocket каналы и broadcast events

---

## СПРИНТЫ

### ✅ SPRINT 1 — Устранение критических пробелов (миграции) — ЗАВЕРШЁН 15.04.2026
**Цель:** Закрыть все ❌ по миграциям  
**Приоритет:** БЛОКИРУЮЩИЙ — выполнен  
**Итог:** Все 8 реально отсутствующих миграций созданы. Итого: 185 файлов миграций.

**Результат:**
- [x] `audit_logs` — EXISTS в `2026_03_25_000004` (верифицировано)
- [x] `commission_rules` — EXISTS в `2026_03_25_000003` (верифицировано)
- [x] `ai_constructions` — EXISTS в `2026_03_25_000002` (верифицировано)
- [x] `2026_04_15_000001_create_webhook_endpoints_table.php` ✅
- [x] `2026_04_15_000002_create_webhook_deliveries_table.php` ✅
- [x] `2026_04_15_000003_create_notification_preferences_table.php` ✅
- [x] `2026_04_15_000004_create_user_addresses_table.php` ✅
- [x] `2026_04_15_000005_create_search_queries_table.php` ✅
- [x] `2026_04_15_000006_create_compliance_records_table.php` ✅
- [x] `2026_04_15_000007_create_chat_rooms_table.php` ✅
- [x] `2026_04_15_000008_create_chat_messages_table.php` ✅

**Обязательные поля во всех миграциях:** `uuid` (unique), `tenant_id` FK→tenants, `business_group_id` (nullable, no FK), `correlation_id` (string, indexed), `tags` (json, nullable), timestamps

---

### 📋 SPRINT 2 — Финансовое ядро (доработка)
**Цель:** Убедиться что Wallet, Payment, Finances, Payout, Bonuses, Commissions — production-ready  
**Зависимости:** Sprint 1 (commission_rules)  
**Оценка:** 3-4 дня

**Задачи:**
- [ ] **Finances/Payout** — проверить PayoutService соответствие канону (FraudControlService::check, DB::transaction, WalletService::debit/credit)
- [ ] **Bonuses** — перенести/синхронизировать app/Services/Bonus/BonusService.php с app/Domains/Finances/ или создать app/Domains/Bonuses/
- [ ] **Commissions** — создать полный CommissionService с commission_rules (B2C 14%, B2B 8-12%)
- [ ] **Wallet** — проверить hold/releaseHold механику в WalletService (2 реализации: app/Services/Wallet/ и app/Domains/Wallet/)
- [ ] **Payment** — проверить idempotency key через PaymentIdempotencyService

**Чек-лист каждого сервиса:**
```
✓ FraudControlService::check($dto)
✓ DB::transaction(function () use (...) { ... })
✓ Log::channel('audit')->info(..., ['correlation_id' => ...])
✓ event(new ...Event(..., $correlationId))
✓ WalletService вызывается через DI, не напрямую
```

---

### 📋 SPRINT 3 — Безопасность и аудит
**Цель:** AuditService, SecurityMonitoringService, FraudNotificationService — единые точки входа  
**Зависимости:** Sprint 1 (audit_logs table)  
**Оценка:** 2-3 дня

**Задачи:**
- [ ] Проверить существование базовой таблицы `audit_logs` (не только ALTER)
- [ ] **AuditService** — убедиться что логирует асинхронно через `AuditLogJob::dispatch()`
- [ ] **SecurityMonitoringService** — проверить что все события пишутся в ClickHouse через BigDataAggregatorService
- [ ] **FraudControlService** — убедиться что `FraudNotificationService::notify()` вызывается при score > 0.65
- [ ] **FraudMLService** — проверить daily MLRecalculateJob регистрацию в Scheduler
- [ ] **Compliance** — создать базовый сервис ComplianceService + доменный тест
- [ ] Написать тесты: `Unit/Domains/Security/SecurityServiceTest`, `Unit/Domains/Audit/AuditServiceTest`

---

### 📋 SPRINT 4 — Коммуникации и уведомления
**Цель:** Notifications, Webhooks, Realtime — полная реализация  
**Зависимости:** Sprint 1 (notification_preferences, webhook_endpoints)  
**Оценка:** 4-5 дней

**Задачи:**
- [ ] **NotificationPreferencesService** — сохранение настроек в notification_preferences (БД)
- [ ] **NotificationChannelService** — проверить все каналы (in_app, email, push, telegram, sms, slack)
- [ ] **WebhookManagementService** — полная реализация: регистрация эндпоинтов, подпись, retry
- [ ] **WebhookSignatureValidator** — HMAC SHA-256 валидация  
- [ ] **RealtimeService** — Echo channels конфигурация (delivery.{id}, courier.{id}.location, tenant.{id}.couriers)
- [ ] **WebSocketConnectionService** — Laravel Echo Server setup
- [ ] Написать тесты: `Unit/Domains/Webhooks/WebhookServiceTest`, `Unit/Domains/Realtime/RealtimeServiceTest`
- [ ] Написать `Integration/Webhooks/WebhookDeliveryFlowTest`

---

### 📋 SPRINT 5 — Корзина, B2B и маркетплейс
**Цель:** CartService, B2BService — подтвердить работоспособность по канону  
**Зависимости:** Sprint 2 (CommissionService), Sprint 1 (carts migration OK)  
**Оценка:** 3 дня

**Задачи:**
- [ ] **CartService** — проверить: 1 продавец = 1 корзина, max 20 корзин, 20 мин резерв, ценообразование (выросла→новая, упала→старая)
- [ ] **CartCleanupJob** — проверить регистрацию в Scheduler (every minute)
- [ ] **InventoryService::reserve()** — проверить lockForUpdate() + корзинный резерв
- [ ] **B2BApiKeyService** — полная реализация X-B2B-API-Key авторизации
- [ ] **B2BOrderService** — проверить MOQ, кредитный лимит, отсрочку платежа
- [ ] **Marketplace** — проверить multi-tenant product listings + B2C/B2B цены
- [ ] Написать `Feature/Domains/B2B/B2BOrderFlowTest`

---

### 📋 SPRINT 6 — ML/BigData и персонализация
**Цель:** ML-пайплайн production-ready, UserTasteProfile обновляется правильно  
**Зависимости:** Sprint 1 (user_taste_profiles OK)  
**Оценка:** 3-4 дня

**Задачи:**
- [ ] **UserBehaviorAnalyzerService** — проверить anonymizeEvent() + ClickHouse insert
- [ ] **AnonymizationService** — sha256(user_id + salt), k-anonymity >= 5
- [ ] **NewUserColdStartService** — classifyUser() ≤7 дней → cold-start поток
- [ ] **ReturningUserDeepProfileService** — > 7 дней → deep-profile поток
- [ ] **MLRecalculateJob** — ежедневно 03:00 в Scheduler
- [ ] **AnnualAnonymizationJob** — создать, регистрация в Scheduler (ежегодно)
- [ ] **BigDataAggregatorService** — проверить ClickHouse партиционирование
- [ ] **Search** — добавить миграцию search_queries + SearchRankingService тест
- [ ] Написать `Feature/Domains/ML/MLPipelineTest`, `Feature/Domains/BigData/ClickHouseTest`

---

### 📋 SPRINT 7 — Персонал и HR
**Цель:** HR/Staff полностью рабочие  
**Зависимости:** Sprint 2 (Wallet для выплат)  
**Оценка:** 2-3 дня

**Задачи:**
- [ ] **HR/EmployeeService** — проверить hire(), calculateSalary(), создание Wallet для сотрудника
- [ ] **HR/PayrollService** — проверить pay() → WalletService::debit(tenant) + credit(employee)
- [ ] **ShiftSchedulingService** — создать если нет
- [ ] **TimeTrackingService** — создать если нет
- [ ] **LeaveManagementService** — создать если нет
- [ ] **InventoryAuditService** — startAudit() → InventoryAuditJob::dispatch()
- [ ] Связать Staff (app/Domains/Staff/) с HR (app/Services/HR/) — не дублировать логику
- [ ] Добавить `Unit/Domains/HR/` тесты

---

### 📋 SPRINT 8 — UserProfile, Search, CRM
**Цель:** Полный профиль пользователя и поиск  
**Зависимости:** Sprint 1 (user_addresses), Sprint 6 (UserTasteProfile)  
**Оценка:** 3-4 дня

**Задачи:**
- [ ] **UserAddressService** — хранение до 5 адресов в user_addresses (после создания миграции)
- [ ] **UserActivityService** — логирование действий + передача в ML-пайплайн
- [ ] **UserProfile API** — маршрут GET /api/v1/user/profile с Wallet + Addresses + Taste + Designs
- [ ] **SearchService** — полнотекстовый поиск + фильтры + save to search_queries
- [ ] **LiveSearchService** — Redis кэш + Scout / Algolia / Meilisearch
- [ ] **SearchRankingService** — ML-ранжирование по UserTasteProfile
- [ ] **CRM** — проверить vертикальные CRM-профили (BeautyCrmService, AutoCrmServiceTest)
- [ ] Добавить тесты `Feature/Domains/Search/SearchFlowTest`

---

### 📋 SPRINT 9 — AI-конструкторы (проверка и миграция)
**Цель:** Все AI-конструкторы используют user_ai_designs таблицу + UserTasteProfile  
**Зависимости:** Sprint 1 (user_ai_designs migration)  
**Оценка:** 3 дня

**Задачи:**
- [ ] Проверить/создать `user_ai_designs` миграцию (user_id, vertical, design_data json, correlation_id)
- [ ] **AIConstructorService** (app/Services/AI/) — проверить orchestration flow: Fraud → Vision → TasteProfile → Recommendation → Inventory → Save → Audit
- [ ] Проверить каждый конструктор на канонические требования:
  - BeautyImageConstructorService
  - InteriorDesignConstructorService
  - MenuConstructorService
  - FashionStyleConstructorService
- [ ] **Redis кэш** — user_ai_designs:{userId} TTL 3600
- [ ] **B2C/B2B** — разные цены и доступность в конструкторах
- [ ] Добавить `Integration/AI/AIConstructorFlowTest`

---

### 📋 SPRINT 10 — Финальный аудит и закрытие Q1
**Цель:** Полное соответствие канону, все тесты проходят, нет ❌  
**Зависимости:** Sprint 1-9  
**Оценка:** 2-3 дня

**Задачи:**
- [ ] `php artisan migrate --pretend` — проверить все миграции без ошибок
- [ ] `./vendor/bin/pest --parallel` — все тесты зелёные
- [ ] `php artisan route:list --path=api` — нет broken routes
- [ ] `php artisan queue:work --queue=q1-critical,default --stop-when-empty` — Queue работает
- [ ] Code review случайной выборки (10 файлов) на canon compliance
- [ ] Обновить `config/domain_queues.php` — пометить все Q1 домены как `complete: true`
- [ ] Финальный отчёт: WORK_PLAN_Q1_REPORT.md

---

## ПРИОРИТЕТЫ И ЗАВИСИМОСТИ

```
Sprint 1 (Миграции)
    ↓
Sprint 2 (Финансы)    Sprint 3 (Безопасность)
    ↓                      ↓
Sprint 5 (Корзина)    Sprint 4 (Коммуникации)
    ↓                      ↓
Sprint 6 (ML/BigData) ←────┘
    ↓
Sprint 7 (HR)         Sprint 8 (UserProfile)
    ↓                      ↓
Sprint 9 (AI-конструкторы)
    ↓
Sprint 10 (Финальный аудит)
```

---

## МЕТРИКИ ЗАВЕРШЁННОСТИ Q1

| Метрика | Сейчас | Цель |
|---------|--------|------|
| Доменов с кодом | 34/34 (100%) | 34/34 |
| Доменов с тестами | 27/34 (79%) | 34/34 (100%) |
| Доменов с миграциями | 26/34 (76%) | 34/34 (100%) |
| Миграций созданы | 177 | 177 + 11 новых |
| Тестовых файлов | ~400+ | ~430+ |
| Canon compliance | ~85% | 100% |
| Критических пробелов | 8 ❌ | 0 ❌ |

---

## ДЕТАЛЬНЫЙ ЧЕКЛИСТ ПО КАЖДОМУ ДОМЕНУ

### ✅ WALLET (app/Domains/Wallet/, 31 файл)
- [x] WalletService: credit(), debit(), hold(), releaseHold()
- [x] lockForUpdate() в транзакциях
- [x] Redis кэш wallet:{walletId} TTL 300
- [x] FraudControlService::check() перед debit/payout
- [x] correlation_id во всех логах
- [x] Тесты: Unit (полные), Integration (WalletOperationFlowTest), Performance

### ✅ PAYMENT (app/Domains/Payment/, 36 файлов)
- [x] PaymentGatewayInterface + 4 драйвера (Tinkoff, Tochka, Sber, SBP)
- [x] idempotency_key unique constraint
- [x] Webhook handler → WalletService::credit()
- [x] FraudControlService::check() перед initPayment
- [x] Тесты: Unit (полные), E2E, Performance

### ⚠️ FINANCES (app/Domains/Finances/, 56 файлов)
- [x] FinancesService
- [x] BonusService (в домене)
- [ ] Проверить PayoutService.php на canon compliance
- [ ] CommissionService — синхронизировать с app/Services/CommissionService.php
- [x] Тесты: Unit (entities, enums, DTOs, events)

### ✅ INVENTORY (app/Domains/Inventory/, 46 файлов)
- [x] InventoryService: reserve(), releaseReservation(), confirmShipment()
- [x] InventoryAuditService
- [x] ReservationCleanupJob (каждую минуту)
- [x] Echo broadcast StockReserved/StockReleased
- [x] Тесты: Unit (полные, многоуровневые)

### ✅ ANALYTICS (app/Domains/Analytics/, 42 файла)
- [x] AnalyticsService: getDashboardMetrics()
- [x] StatisticsService
- [x] Тесты: Unit

### ⚠️ AI (app/Domains/AI/, 24 файла)
- [x] AIConstructorService orchestration
- [ ] Создать user_ai_designs миграцию
- [x] Тесты: Unit

### ✅ FRAUDML (app/Domains/FraudML/, 22 файла)
- [x] FraudMLService: scoreOperation(), extractFeatures()
- [x] fraud_model_versions версионирование
- [x] AuditService вызов
- [x] Тесты: Unit, E2E, Performance

### ✅ GEO + GEOLOGISTICS (app/Domains/Geo/, app/Domains/GeoLogistics/)
- [x] GeotrackingService: updateCourierLocation() + broadcast
- [x] RouteOptimizationService (OR-Tools)
- [x] MapService (Yandex Maps API)
- [x] Тесты: Unit

### ✅ RECOMMENDATION + DEMAND_FORECAST
- [x] RecommendationService
- [x] DemandForecastService
- [x] Тесты: Unit

### ✅ REFERRAL (app/Domains/Referral/, 31 файл)
- [x] ReferralService
- [x] Тесты: Unit

### ✅ STAFF (app/Domains/Staff/, 68 файлов)
- [x] StaffService
- [x] InventoryAuditService (в Inventory)
- [x] Тесты: Unit+Feature

### ✅ MARKETPLACE (app/Domains/Marketplace/, 26 файлов)
- [x] MarketplaceService
- [x] Тесты: Unit

### ✅ ADVERTISING (app/Domains/Advertising/, 51 файл)
- [x] AdCampaignService
- [x] AdTargetingService
- [x] ShortVideoAdService
- [x] Тесты: Unit

### ✅ COMMON (app/Domains/Common/, 29 файлов)
- [x] UserTasteProfile сервисы
- [x] Тесты: Unit

### ⚠️ COMMUNICATION (app/Domains/Communication/, 24 файла)
- [x] ChatService
- [ ] Создать chat_rooms + chat_messages миграции
- [x] Тесты: Unit

### ✅ CRM (app/Domains/CRM/, 66 файлов)
- [x] CrmService + вертикальные CRM-профили
- [x] Тесты: Unit+Feature

---

### ⚠️ ML (app/Services/ML/, 8 файлов)
- [x] UserBehaviorAnalyzerService
- [x] AnonymizationService
- [x] NewUserColdStartService / ReturningUserDeepProfileService
- [x] TasteMLService
- [ ] Рассмотреть создание app/Domains/ML/ (если нужна полная 9-слойная)
- [x] Тесты: Feature/ML, Unit/ML, Unit/Services

### ⚠️ BIGDATA (app/Services/ML/BigDataAggregatorService.php)
- [x] ClickHouse insert methods
- [ ] Добавить партиционирование по tenant + дате
- [x] Тесты: Unit/Services

### ⚠️ HR (app/Services/HR/, app/Services/HRService.php)
- [x] EmployeeService, PayrollService
- [ ] ShiftSchedulingService (создать)
- [ ] TimeTrackingService (создать)
- [ ] LeaveManagementService (создать)
- [x] Тесты: Unit/Services

### ⚠️ SEARCH (app/Services/Search*.php)
- [x] SearchService, LiveSearchService, SearchRankingService
- [ ] Создать search_queries миграцию
- [x] Тесты: Feature/Controllers/Api

### ⚠️ BONUSES (app/Services/Bonus/, modules/Bonuses/)
- [x] BonusService: award(), spend()
- [x] BonusRule модель
- [x] Тесты: Unit/Services + Unit/Domains/Finances

### ❌ COMMISSIONS (app/Services/CommissionService.php, modules/Commissions/)
- [x] CommissionService (базовый)
- [ ] Создать commission_rules миграцию
- [ ] Добавить B2C(14%)/B2B(8-12%) tier логику
- [ ] Написать Unit/Domains/Commissions/ тесты

### ⚠️ PAYOUT (app/Services/Payout/)
- [x] PayoutService, MassPayoutService
- [x] payout_requests миграция
- [ ] Проверить BatchPayoutJob регистрацию

### ❌ AUDIT (app/Services/AuditService.php)
- [x] AuditService: log(), logModelEvent()
- [x] AuditLogJob (async)
- [ ] Проверить/создать базовую audit_logs миграцию
- [x] Тесты: Unit/Services/AuditServiceTest

### ⚠️ SECURITY (app/Services/Security/, 9 файлов)
- [x] SecurityMonitoringService, RateLimiterService, IdempotencyService
- [x] ApiKeyManagementService, WebhookSignatureService
- [x] security_events миграция
- [ ] Добавить Unit/Domains/Security/ тесты

### ⚠️ NOTIFICATIONS (app/Services/Notification*.php)
- [x] NotificationService, NotificationChannelService
- [x] NotificationPreferencesService
- [ ] Создать notification_preferences миграцию
- [x] Тесты: Integration/Notifications (5 тестов)

### ⚠️ CART (app/Services/CartService.php)
- [x] CartService
- [x] carts миграция
- [ ] Проверить 20-мин резерв + CartCleanupJob
- [x] Тесты: Unit/Services/CartServiceTest

### ⚠️ B2B (app/Services/B2B/)
- [x] B2BApiKeyService, B2BOrderService
- [x] business_groups, b2b_api_keys миграции
- [ ] Создать Feature/Domains/B2B/B2BOrderFlowTest
- [x] Тесты: Feature/Filament/B2BPanelTest

### ❌ WEBHOOKS (app/Services/API/Webhook*, app/Services/Webhook/)
- [x] WebhookManagementService, WebhookSignatureValidator
- [ ] Создать webhook_endpoints + webhook_deliveries миграции
- [ ] Написать Unit/Domains/Webhooks/ тесты
- [ ] Написать Integration/Webhooks/ тест

### ❌ REALTIME (app/Services/Realtime*.php, WebSocketConnectionService)
- [x] RealtimeService, RealtimeChatService, RealtimeAnalyticsService
- [ ] Создать realtime таблицы (если нужны, или только Redis)
- [ ] Написать Unit/Domains/Realtime/ тесты
- [ ] Настроить Echo channels конфигурацию

### ❌ USERPROFILE (app/Services/UserAddressService.php + UserActivityService.php)
- [x] UserAddressService (до 5 адресов логика)
- [x] UserActivityService
- [ ] Создать user_addresses миграцию
- [x] Тесты: Feature/Controllers/Api/V1/UserProfile

### ❌ COMPLIANCE (app/Services/Compliance/, app/Services/Security/ComplianceManagement*.php)
- [x] ComplianceRequirementService, MdlpService, MercuryService
- [ ] Создать compliance_records миграцию
- [ ] Написать Unit/Domains/Compliance/ тесты

---

## КОМАНДЫ ДЛЯ ПРОВЕРКИ ПРОГРЕССА

```bash
# Проверить что все миграции валидны
php artisan migrate:status

# Запустить Q1 тесты
./vendor/bin/pest tests/Unit/Domains/ tests/Unit/Services/ tests/Integration/ --parallel

# Проверить синтаксис всех PHP
php -r "iterator_apply(
  new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app')),
  fn($f) => !$f->isFile() || shell_exec('php -l '.$f->getPathname()),
  []
);"

# Проверить queue задачи
php artisan schedule:list
php artisan queue:monitor

# Проверить canon violations (ищем запрещённые паттерны)
grep -r "Auth::" app/ --include="*.php" | grep -v "vendor"
grep -r "return null;" app/Services/ --include="*.php"
grep -r "new Exception(" app/Services/ --include="*.php" | grep -v "tests"
```

---

## ДАТА СТАРТА РАБОТ

- **Sprint 1** (миграции): **СТАРТ СЕЙЧАС**
- **Sprint 2** (финансы): после Sprint 1
- **Sprint 3** (безопасность): параллельно с Sprint 2
- **Sprint 4** (коммуникации): после Sprint 1
- **Sprint 5-10**: последовательно по приоритету

**Ожидаемое завершение Q1:** ~2-3 недели при 8 часов в день

---

*Последнее обновление: 14.04.2026*  
*Следующий файл: WORK_PLAN_Q2.md (после завершения Q1)*
