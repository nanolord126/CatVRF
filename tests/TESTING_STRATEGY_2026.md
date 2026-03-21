declare(strict_types=1);

# СТРАТЕГИЯ КОМПЛЕКСНОГО ТЕСТИРОВАНИЯ CATVRF (КАНОН 2026)

## 1. ОБЗОР

Проект CatVRF требует **production-grade** тестирования на 100% критичных путей с покрытием:
- ✅ Unit-тесты (Pest) — все сервисы, модели, вспомогательные классы
- ✅ Feature-тесты (Pest) — все API endpoints, контроллеры, flow'ы
- ✅ Integration-тесты — взаимодействие Wallet ↔ Payment ↔ FraudML, Inventory ↔ Recommendation
- ✅ Security-тесты — Fraud-атаки, Race conditions, Rate limiting bypass
- ✅ Load-тесты — k6: 50k RPS, ramp-up, spike, soak scenarios
- ✅ Chaos-тесты — Redis down, DB slow queries, Service unavailable

## 2. СТРУКТУРА ПРОЕКТА (АРТЕФАКТЫ ДЛЯ ТЕСТИРОВАНИЯ)

### Core Services (КРИТИЧНЫЕ)
```
app/Services/
├── Wallet/
│   ├── WalletService.php ← CRITICAL (DB::transaction, optimistic lock, Redis cache)
│   ├── BalanceTransactionService.php ← CRITICAL (audit log, fraud check)
│   └── WalletHoldService.php ← CRITICAL (race conditions)
├── Payment/
│   ├── PaymentGatewayInterface.php ← CRITICAL
│   ├── PaymentService.php ← CRITICAL (idempotency, webhook verification)
│   └── RefundService.php ← CRITICAL
├── Fraud/
│   ├── FraudControlService.php ← CRITICAL (ML scoring)
│   ├── FraudMLService.php ← CRITICAL (model versions, fallback)
│   └── RateLimiterService.php ← CRITICAL
├── Inventory/
│   ├── InventoryManagementService.php ← CRITICAL
│   └── StockMovementService.php ← CRITICAL (audit log)
├── Recommendation/
│   ├── RecommendationService.php ← HIGH (caching, Redis TTL)
│   └── EmbeddingsService.php ← HIGH
├── Promo/
│   └── PromoCampaignService.php ← HIGH (budget tracking, fraud)
├── Referral/
│   └── ReferralService.php ← HIGH (qualification, bonus award)
└── Search/
    └── SearchService.php ← HIGH (ranking, filtering)
```

### Models (40 вертикалей + Core)
```
app/Domains/{Vertical}/Models/
- Auto: TaxiDriver, TaxiRide, AutoRepairOrder, CarWashBooking
- Beauty: BeautySalon, Master, Appointment, Consumable
- Food: Restaurant, Dish, RestaurantOrder, DeliveryOrder, KDSOrder
- Hotels: Hotel, Booking, HotelRoomInventory
- RealEstate: Property, RentalListing, ViewingAppointment
- ... 35 остальных вертикалей
```

### Controllers
```
app/Http/Controllers/
├── PaymentController.php ← CRITICAL
├── WalletController.php ← CRITICAL
├── Auth/LoginController.php ← CRITICAL
└── {Vertical}Controller.php (для каждой вертикали)
```

### Livewire Components (17+)
```
app/Livewire/
├── Wishlist/WishlistComponent.php ← Fraud: манипуляция рейтингом через wishlist
├── Search/SearchComponent.php ← Fraud: DDoS через search
├── Marketplace/CartComponent.php ← Fraud: множественные попытки checkout
└── ... остальные компоненты
```

### Filament Resources
```
app/Filament/Tenant/Resources/
├── PaymentTransactionResource.php ← CRITICAL
├── WalletResource.php ← CRITICAL
├── {Vertical}Resource.php (для каждой вертикали)
└── FraudAlertResource.php ← CRITICAL
```

### Jobs, Events, Listeners, Policies
```
app/Jobs/ ← Asynctask execution, retry logic, correlation_id
app/Events/ ← Event dispatch, serialization
app/Listeners/ ← Event handling, transaction safety
app/Policies/ ← RBAC, tenant scoping, fraud check
```

---

## 3. ПЛАН ФАЗ РЕАЛИЗАЦИИ

### ✅ ФАЗА 1: Фундамент + Core Services (НЕДЕЛЯ 1)
**Приоритет**: CRITICAL

- [ ] Создать Base Test Classes (BaseTestCase, SecurityTestCase, LoadTestCase, ChaosTestCase)
- [ ] Создать Test Fixtures и Factories
- [ ] Unit-тесты для WalletService, PaymentService, FraudMLService
- [ ] Feature-тесты для Payment API, Wallet API, Auth API
- [ ] Security-тесты: Fraud-атаки, Idempotency bypass, Race conditions
- [ ] Load-test конфиг (k6)
- [ ] Результат: 200+ тестов, 85%+ покрытие Core

### 📋 ФАЗА 2: Вертикали (TOP-5) + Models + Controllers (НЕДЕЛЯ 2)
**Приоритет**: HIGH

- [ ] Unit-тесты для моделей всех 40 вертикалей (relationships, scopes, casts)
- [ ] Feature-тесты для контроллеров вертикалей (CRUD, validation, auth)
- [ ] Integration-тесты: Booking → Wallet → Payment flow (Beauty, Hotels, Food)
- [ ] Security-тесты: Policy authorization, tenant isolation, business_group scoping
- [ ] Результат: 400+ тестов, 75%+ покрытие Domains

### 🔐 ФАЗА 3: Advanced Services + Livewire + Filament (НЕДЕЛЯ 3)
**Приоритет**: HIGH

- [ ] Unit-тесты для Recommendation, Inventory, Promo, Referral, Search
- [ ] Feature-тесты для Livewire компонентов (Wishlist, Search, Cart)
- [ ] Feature-тесты для Filament Resources (CRUD с authorization)
- [ ] Integration-тесты: Inventory ↔ DemandForecast, Promo ↔ Wallet, Referral ↔ Bonus
- [ ] Security-тесты: Advanced fraud patterns (wishlist manipulation, search poisoning)
- [ ] Результат: 300+ тестов, 70%+ покрытие Advanced services

### ⚡ ФАЗА 4: Jobs, Events, Policies, Middleware (НЕДЕЛЯ 4)
**Приоритет**: MEDIUM

- [ ] Unit-тесты для всех Jobs (correlation_id, retry logic, queue handling)
- [ ] Unit-тесты для всех Events/Listeners (event dispatch, transaction safety)
- [ ] Unit-тесты для всех Policies (authorization, fraud checks)
- [ ] Unit-тесты для всех Middleware (rate limiting, auth, tenant scoping)
- [ ] Integration-тесты: Event chains (OrderCreated → InventoryReserve → FraudCheck)
- [ ] Результат: 250+ тестов, 80%+ покрытие Infrastructure

### 🚀 ФАЗА 5: Load + Chaos + ML Testing (НЕДЕЛЯ 5)
**Приоритет**: HIGH (для production)

- [ ] Load-тесты k6: Payment flow, Search, Marketplace browse — 50k RPS
- [ ] Load-тесты k6: Surge pricing, Real-time inventory updates
- [ ] Chaos-тесты: Redis down, DB slow queries, Service delays
- [ ] Chaos-тесты: Partial outage scenarios (circuit breaker, fallback)
- [ ] ML-fraud scoring validation: Model accuracy > 92%, False positive rate < 5%
- [ ] Stress-тесты: 100k concurrent users, connection pooling, GC pressure
- [ ] Результат: 50+ load/chaos scenarios, performance baseline documented

---

## 4. ТРЕБОВАНИЯ К ТЕСТАМ (ОБЯЗАТЕЛЬНЫ)

### Unit Tests (Pest)
```
- Все методы сервисов покрыты
- Все edge cases и boundary values протестированы
- Моки для зависимостей (Database, Redis, External APIs)
- Assertions на return types, exceptions, side effects
- Assert::assertEquals(), Assert::assertThrows(), Assert::assertTrue()
```

### Feature Tests (Pest)
```
- Все HTTP endpoints покрыты (GET, POST, PUT, DELETE)
- Authentication & Authorization (middleware, policies)
- Request validation (422 на invalid input)
- Response formats (JSON, status codes)
- Side effects: Database mutations, Events dispatched, Jobs queued
```

### Security Tests (ОБЯЗАТЕЛЬНЫ)
```
✅ Fraud Attacks:
  - Replay attack: Платёж twice с одинаковым idempotency_key
  - Idempotency bypass: Изменение payload после первого запроса
  - Rate limit bypass: 1000 запросов за 1 сек на /payment
  - Race condition: Двойная трата со счёта через race condition
  - Wishlist manipulation: Создание wishlist для повышения рейтинга
  - Fake reviews: Postitive reviews от одного IP
  - Bonus hunting: Multiply referral claims от одного user
  
✅ Authorization:
  - RBAC: User не может видеть другого tenant'a
  - Policy: Только owner может edit свой shop
  - Tenant scoping: Queries всегда фильтруются по tenant_id
  - Business group isolation: Subsidiaries изолированы друг от друга

✅ Input Validation:
  - SQL injection: ' OR '1'='1
  - XSS: <script>alert(1)</script>
  - XXE: XML external entity
  - Mass assignment: Прямое установление private fields
```

### Load Tests (k6 / Artillery)
```
Сценарии:
1. Ramp-up: 0 → 5k RPS за 5 минут
2. Spike: Резкий скачок до 50k RPS на 30 сек
3. Soak: Стабильная нагрузка 10k RPS на 30 минут
4. Gradual: 1k → 50k → 1k за цикл

Метрики:
- P50, P95, P99 response time
- Error rate < 0.1%
- Throughput стабилен
- Memory leak detection (RSS not growing)
- DB connection pooling не переполняется
- Redis не кешируется до смерти
```

### Chaos Tests
```
Сценарии:
1. Redis down: Должен fallback на DB cache
2. DB slow queries: > 5 сек query — должен timeout & retry
3. Service unavailable: 503 responses — должен circuit breaker
4. Partial network failure: 20% packet loss на Redis
5. Kill worker process: Должен respawn & continue
6. Memory pressure: OOMKiller — graceful shutdown
```

---

## 5. МЕТРИКИ УСПЕХА

| Метрика | Целевое значение | Критерий |
|---------|-----------------|----------|
| Code Coverage (Unit + Feature) | ≥ 80% Core, ≥ 70% Domains | SonarQube / PHPCOV |
| Security Issues | 0 Critical | Pest + Manual code review |
| Fraud Detection Accuracy | > 92% | TP / (TP + FP) |
| False Positive Rate (Rate limit) | < 2% | Genuine requests blocked |
| Load test P95 response | < 200ms | k6 metrics |
| Load test Error Rate | < 0.1% | k6 checks |
| Chaos test recovery | < 30 сек | Time to resume normal operation |
| Flaky tests | 0 | No randomness in tests |

---

## 6. ИНСТРУМЕНТЫ И КОНФИГУРАЦИЯ

### Тестирование
- **Pest** — PHP Testing Framework (Unit + Feature)
- **PHPUnit** — Fallback для специфичных случаев
- **PHPCOV** — Code coverage report

### Load Testing
- **k6** — JavaScript-based load testing
- **Artillery** — Alternative, node-based
- **Apache JMeter** — Optional, UI-based

### Chaos Engineering
- **Chaos Monkey** (PHP имитация)
- **Toxiproxy** — TCP/HTTP proxy для network failures
- **Fault Injection** — Произвольные ошибки в коде

### Monitoring & Metrics
- **Prometheus** — Сбор метрик
- **Grafana** — Визуализация
- **Sentry** — Error tracking
- **DataDog** — APM (optional)

---

## 7. СТРУКТУРА ФАЙЛОВ ТЕСТОВ

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── Wallet/
│   │   │   ├── WalletServiceTest.php
│   │   │   ├── BalanceTransactionServiceTest.php
│   │   │   └── WalletHoldServiceTest.php
│   │   ├── Payment/
│   │   │   ├── PaymentServiceTest.php
│   │   │   ├── RefundServiceTest.php
│   │   │   └── IdempotencyServiceTest.php
│   │   ├── Fraud/
│   │   │   ├── FraudMLServiceTest.php
│   │   │   ├── RateLimiterServiceTest.php
│   │   │   └── FraudControlServiceTest.php
│   │   ├── Inventory/ ← [новые]
│   │   ├── Recommendation/ ← [новые]
│   │   ├── Promo/ ← [новые]
│   │   ├── Referral/ ← [новые]
│   │   └── Search/ ← [новые]
│   ├── Models/
│   │   ├── UserTest.php
│   │   ├── TenantTest.php
│   │   ├── {Vertical}/
│   │   │   ├── AutoTest.php
│   │   │   ├── BeautyTest.php
│   │   │   └── ... (для каждой вертикали)
│   │   └── WalletTest.php
│   ├── Policies/
│   │   ├── PaymentPolicyTest.php
│   │   ├── WalletPolicyTest.php
│   │   └── {Vertical}PolicyTest.php
│   ├── Requests/
│   │   └── PaymentInitRequestTest.php
│   └── Middleware/
│       ├── RateLimitMiddlewareTest.php
│       └── TenantMiddlewareTest.php
├── Feature/
│   ├── Payment/
│   │   ├── PaymentInitTest.php
│   │   ├── PaymentWebhookTest.php
│   │   └── RefundTest.php
│   ├── Wallet/
│   │   ├── BalanceOperationsTest.php
│   │   └── TransactionHistoryTest.php
│   ├── Fraud/
│   │   ├── FraudDetectionTest.php
│   │   ├── RateLimitBypassTest.php
│   │   └── AdvancedFraudPatternsTest.php
│   ├── {Vertical}/
│   │   ├── AutoTest.php
│   │   ├── BeautyTest.php
│   │   └── ... (для каждой вертикали)
│   ├── Livewire/
│   │   ├── WishlistComponentTest.php
│   │   ├── SearchComponentTest.php
│   │   └── CartComponentTest.php
│   ├── Filament/
│   │   ├── PaymentResourceTest.php
│   │   └── {Vertical}ResourceTest.php
│   └── Integration/
│       ├── BookingFlowTest.php
│       ├── PaymentToWalletTest.php
│       └── InventoryToRecommendationTest.php
├── Security/ ← [новая папка]
│   ├── FraudAttacksTest.php
│   ├── AuthorizationTest.php
│   ├── InputValidationTest.php
│   └── TenantIsolationTest.php
├── Load/ ← [новая папка]
│   ├── payment-flow.js
│   ├── search-browse.js
│   ├── marketplace-surge.js
│   └── real-time-inventory.js
├── Chaos/ ← [новая папка]
│   ├── redis-failure.php
│   ├── db-slow-queries.php
│   ├── service-unavailable.php
│   └── network-failure.php
├── BaseTestCase.php ← [отрефакторен]
├── SecurityTestCase.php ← [новый]
├── LoadTestCase.php ← [новый]
├── ChaosTestCase.php ← [новый]
├── Fixtures/ ← [новая папка]
│   ├── user-fixture.php
│   ├── payment-fixture.php
│   └── fraud-scenario-fixture.php
└── TESTING_STRATEGY_2026.md ← Этот файл
```

---

## 8. БЫСТРЫЙ СТАРТ

```bash
# Запустить все тесты
pest --parallel

# Только Unit
pest tests/Unit --parallel

# Только Security
pest tests/Security

# Load test
k6 run k6/payment-flow.js

# Chaos test
php tests/Chaos/redis-failure.php

# Покрытие (HTML report)
pest --coverage --coverage-html=storage/coverage

# Watch mode для разработки
pest --watch tests/Unit/Services/Wallet
```

---

## 9. TIMELINE

| Фаза | Сроки | Тесты | Покрытие |
|------|-------|-------|----------|
| 1: Core | 3–5 дней | 200+ | 85% Core |
| 2: Domains | 5–7 дней | 400+ | 75% Domains |
| 3: Advanced | 5–7 дней | 300+ | 70% Services |
| 4: Infrastructure | 3–5 дней | 250+ | 80% Infrastructure |
| 5: Load + Chaos | 7–10 дней | 50+ scenarios | ✅ Baseline |
| **TOTAL** | **~4 недели** | **1200+** | **75%+ всего** |

---

## 10. КРИТИЧНЫЕ ЗАМЕЧАНИЯ

⚠️ **Обязательно соблюдать**:
- Все тесты должны использовать `declare(strict_types=1);`
- Все файлы — UTF-8 с CRLF
- Корреляция ID обязательна во всех тестах (assert $response->headers())
- Tenant scoping проверяется в КАЖДОМ feature-тесте
- Fraud score assertions на все операции > 100 000 ₽
- Rate limiting headers проверяются (429 + Retry-After)
- NO STUBS, NO MOCKS (только Factories + real DB для feature тестов)
- Flaky tests не допускаются (детерминированность)

---

**Версия**: 2026-03-19 v1.0  
**Статус**: Готово к реализации (ФАЗА 1 начинается сейчас)
