# 📋 ПЛАН ДЕЙСТВИЙ НА 4 ДНЯ: КРИТИЧЕСКИЕ БЛОКЕРЫ

**Дата начала:** 17 марта 2026  
**Реальная готовность ДО исправлений:** 72%  
**Целевая готовность ПОСЛЕ:** 95%+  

---

## 📅 ДЕНЬ 1 (17 марта): ПЛАТЁЖНАЯ СИСТЕМА — КРИТИЧНО

### 🎯 Задачи
1. **Создать модели** (2 часа)
   - `app/Models/Wallet.php` (или переместить из Domains)
   - `app/Models/BalanceTransaction.php`
   - `app/Models/PaymentTransaction.php`
   - `app/Models/PaymentIdempotencyRecord.php`

2. **Создать миграции** (1 час)
   - `database/migrations/xxxx_create_wallets_table.php`
   - `database/migrations/xxxx_create_balance_transactions_table.php`
   - `database/migrations/xxxx_create_payment_transactions_table.php`
   - `database/migrations/xxxx_create_payment_idempotency_records_table.php`

3. **Исправить WalletService** (1 час)
   - Убедиться что все методы (credit, debit, hold, release) работают
   - Добавить корректные use statements
   - Протестировать на 1 вертикали (Beauty)

4. **Написать IdempotencyService** (1.5 часа)
   - Метод `check(string $operation, int $merchantId, string $idempotencyKey): ?array`
   - Логика: проверить payment_idempotency_records
   - Если уже обработано — вернуть cached response
   - Если нет — записать и обработать

5. **Написать FiscalService** (1.5 часа)
   - Метод `fiscalize(PaymentTransaction $payment): void`
   - Интеграция с ОФД API (заглушка на баз уровне)
   - Логирование в audit
   - Обработка ошибок

### ✅ Успех дня
- [ ] WalletService работает без ошибок
- [ ] При вызове `$wallet->credit(...)` не выбрасывает `Class not found`
- [ ] Все 4 модели существуют и имеют правильные fields
- [ ] IdempotencyService проверяет duplicates
- [ ] FiscalService вызывается при capture платежа
- [ ] Миграции проходят `php artisan migrate:fresh`

---

## 📅 ДЕНЬ 2 (18 марта): PAYMENT WEBHOOK + RBAC FOUNDATION

### 🎯 Задачи (до обеда)

#### Платежи: WEBHOOK (2 часа)
1. **Создать Internal/PaymentWebhookController.php**
   - Метод `webhook(Request $request): JsonResponse` для каждого шлюза
   - Routes: `/internal/webhook/tinkoff`, `/internal/webhook/sber`, `/internal/webhook/tochka`

2. **Реализовать signature verification**
   - Tinkoff: проверить SIGN (MD5)
   - Sber: проверить signature header
   - Tochka: проверить signature header
   - При неудаче — 403 Forbidden

3. **Логика обработки**
   - Получить orderId из webhook
   - Проверить idempotency_key через IdempotencyService
   - Обновить payment_transaction status (authorized → captured)
   - Вызвать WalletService::credit()
   - Вызвать FiscalService::fiscalize()
   - Вернуть 200 OK

4. **Написать ReleaseHoldJob** (1 час)
   - Запускается 24 часа после hold
   - Проверяет payment_transactions where status = 'authorized'
   - Если hold_time > 24h — release hold и обновить status
   - Логировать в audit

#### RBAC: Foundation (2 часа)
1. **Создать Role enum**
   ```php
   enum Role: string {
       case ADMIN = 'admin';
       case BUSINESS_OWNER = 'owner';
       case MANAGER = 'manager';
       case EMPLOYEE = 'employee';
       case CUSTOMER = 'customer';
   }
   ```

2. **Добавить role в User/TenantUser**
   - Migration: `ALTER TABLE users ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'customer'`
   - Migration: `ALTER TABLE tenant_users ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'employee'`
   - Model: `protected $casts = ['role' => Role::class]`

3. **Создать Policies**
   - `app/Policies/BusinessOwnerPolicy.php`
   - `app/Policies/ManagerPolicy.php`
   - Методы: `viewCRM()`, `managePayout()`, `viewAnalytics()`, etc.

### ✅ Успех дня
- [ ] Webhook эндпоинты созданы
- [ ] Signature verification работает (тестировать curl)
- [ ] ReleaseHoldJob вызывается по расписанию
- [ ] Role enum существует
- [ ] User/TenantUser имеют role поле
- [ ] Policies созданы и зарегистрированы

---

## 📅 ДЕНЬ 3 (19 марта): RBAC MIDDLEWARE + WISHLIST

### 🎯 Задачи (до обеда)

#### RBAC: MIDDLEWARE (1.5 часа)
1. **TenantCRMOnly middleware**
   ```php
   public function handle(Request $request, Closure $next): Response {
       if (!auth()->user() || auth()->user()->role !== Role::BUSINESS_OWNER) {
           abort(403);
       }
       return $next($request);
   }
   ```

2. **RoleBasedAccess middleware** (параметризованный)
   ```php
   Route::middleware(['auth', 'role:owner,manager'])
       ->prefix('tenant')
       ->group(function () { ... });
   ```

3. **Разделить Filament на 3 панели**
   - `/admin` — Admin panel (только для ADMIN role)
   - `/tenant` — Tenant CRM (только для OWNER, MANAGER roles)
   - `/` — Public marketplace
   - У каждой свой `AuthProvider`

#### WISHLIST (2.5 часа)
1. **Создать WishlistService**
   - `add(int $userId, int $itemId, string $vertical): void`
   - `remove(int $userId, int $itemId): void`
   - `getForUser(int $userId, int $limit = 20): Collection`
   - `calculateRanking(int $itemId): float` (0-1)

2. **Создать WishlistPaymentService**
   - `trackPayment(int $wishlistId, PaymentTransaction $payment): void`
   - Записать в wishlist_payments таблицу

3. **Создать миграцию**
   - `wishlist_payments` (wishlist_id, payment_id, payment_amount, paid_at)

4. **Интегрировать в SearchService**
   - Добавить wishlist_weight = 0.15
   - При ранжировании учитывать wishlist_count

### ✅ Успех дня
- [ ] `/admin` доступен только для ADMIN
- [ ] `/tenant` доступен только для OWNER/MANAGER
- [ ] Обычный user видит ошибку 403 при попытке access /tenant
- [ ] WishlistService работает
- [ ] wishlist_payments таблица создана
- [ ] SearchService возвращает wishlist_weight в результатах

---

## 📅 ДЕНЬ 4 (20 марта): FRAUDML + CLEANUP + TESTING

### 🎯 Задачи (до обеда)

#### FraudML (базовый уровень, 2 часа)
1. **Создать FraudMLService** (без настоящего XGBoost)
   - `scoreOperation(OperationDto $dto): float` (возвращает 0-1)
   - `shouldBlock(float $score, string $operationType): bool`
   - `extractFeatures(OperationDto $dto): array`

2. **Создать fraud_attempts таблицу**
   - Миграция: id, tenant_id, user_id, operation_type, ml_score, decision, created_at

3. **Интегрировать в PaymentService**
   ```php
   $score = $this->fraudMLService->scoreOperation($operationDto);
   if ($score > 0.8) {
       throw new FraudDetectedException('Suspicious activity detected');
   }
   ```

#### CLEANUP (1 час)
1. **Удалить дубликат:**
   - `rm -rf database/migrations/real_estate/` (оставить только realestate)
   - Найти duplicate Policies и удалить

2. **Validate Models structure**
   - Если app/Models должна быть пустая — удалить
   - Убедиться что core models в правильном месте

#### TESTING (1.5 часа)
1. **E2E тест платежей**
   ```php
   $payment = $paymentGateway->initPayment($amount);
   $this->webhook->webhook($webhookData);
   $this->assertEquals('captured', $payment->refresh()->status);
   ```

2. **E2E тест RBAC**
   ```php
   $response = $this->actingAs($customer)->get('/tenant/dashboard');
   $this->assertEquals(403, $response->status());
   ```

3. **E2E тест Wishlist**
   ```php
   $wishlist->add($userId, $itemId);
   $this->assertContains($itemId, $wishlist->getForUser($userId));
   ```

### ✅ Успех дня
- [ ] FraudMLService работает и логирует fraud_attempts
- [ ] PaymentService проверяет fraud score перед capture
- [ ] real_estate дубликат удалён
- [ ] E2E тесты проходят
- [ ] Все 15 todo в статусе completed

---

## 📊 МЕТРИКИ УСПЕХА

После 4 дней готовность должна быть:

| Компонент | ДО | ПОСЛЕ | Изменение |
|-----------|----|----|-----------|
| **Платёжная система** | 0% | 95% | ✅ |
| **RBAC** | 0% | 90% | ✅ |
| **Wishlist** | 30% | 85% | ✅ |
| **FraudML** | 20% | 60% | ✅ |
| **Infra/Bootstrap** | 40% | 60% | ⚠️ |
| **ИТОГО** | 72% | 95% | ✅✅✅ |

---

## 🚀 КОМАНДЫ ДЛЯ ЗАПУСКА

### День 1: Миграции
```bash
php artisan make:model Wallet --migration
php artisan make:model BalanceTransaction --migration
php artisan make:model PaymentTransaction --migration
php artisan make:model PaymentIdempotencyRecord --migration
php artisan migrate:fresh
```

### День 2: Controllers
```bash
php artisan make:controller Internal/PaymentWebhookController
php artisan make:job ReleaseHoldJob
```

### День 3: Services & Policies
```bash
php artisan make:class Services/WishlistService
php artisan make:policy BusinessOwnerPolicy
php artisan make:middleware TenantCRMOnly
```

### День 4: Testing
```bash
php artisan make:test PaymentFlowTest
php artisan make:test RBACTest
php artisan make:test WishlistTest
php artisan test
```

---

## 💡 КРИТИЧЕСКИЕ НАПОМИНАНИЯ

1. **Не забыть correlation_id** везде (платежи, fraud, wishlist)
2. **Все мутации в DB::transaction()** (платежи, wishlist)
3. **Логирование в audit** (все операции)
4. **Rate limiting** (платежи, wishlist add)
5. **Tenant scoping** (везде!)

---

**Ответственный:** GitHub Copilot  
**Контакт:** @user для уточнений  
**Статус:** 🔴 КРИТИЧНО, начать немедленно

