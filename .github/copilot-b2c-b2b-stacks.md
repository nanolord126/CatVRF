# B2C / B2B TECHNICAL STACKS 2026

**Версия:** 1.0  
**Статус:** PRODUCTION MANDATORY  
**Дата:** 25.03.2026

---

## 🏪 B2C РЕЖИМ (Consumer-to-Business)

**Для обычных пользователей (физических лиц)**

### Определение B2C:
```php
// Условие для определения B2C-режима:
$isB2C = !$request->has('inn') || !$request->has('business_card_id');

// Если нет ИНН или бизнес-карты → режим B2C
```

### Характеристики B2C:

#### 1. Розничные цены
```php
// B2C-цены ВСЕГДА выше или равны B2B-ценам
$pricing = PricingService::getPricesB2C($productId, $currentUser);
```

#### 2. Одна корзина на продавца (максимум 20 корзин всего)
```php
// У пользователя может быть:
// - Корзина #1 у продавца "Магазин А"
// - Корзина #2 у продавца "Магазин Б"
// - ... максимум 20 корзин всего

$carts = Cart::where('user_id', auth()->id())
    ->where('mode', 'B2C')
    ->limit(20)
    ->get();
```

#### 3. Резерв 20 минут
```php
// Товары резервируются на 20 минут
// Если не оплачено - резерв автоматически снимается
$cart->reserved_until = now()->addMinutes(20);
```

#### 4. Обязательная проверка наличия при открытии корзины
```php
public function showCart(Cart $cart)
{
    // 1. Проверить наличие каждого товара
    foreach ($cart->items as $item) {
        $stock = InventoryManagementService::getCurrentStock($item->product_id);
        
        if ($stock === 0) {
            // Товар недоступен - сделать чёрно-белым
            $item->is_available = false;
        } else if ($stock < $item->quantity) {
            // Товара меньше - уменьшить количество
            $item->quantity = min($item->quantity, $stock);
        }
    }
    
    return response()->json($cart);
}
```

#### 5. Чёрно-белое отображение недоступных товаров
```html
<!-- Товар в наличии -->
<div class="product-card">
    <img src="{{ $product->image }}" />
    <p class="price">{{ $product->price }} ₽</p>
    <button class="btn-primary">В корзину</button>
</div>

<!-- Товар НЕДОСТУПЕН -->
<div class="product-card grayscale opacity-50">
    <img src="{{ $product->image }}" style="filter: grayscale(100%)" />
    <p class="status text-danger">Нет в наличии</p>
    <!-- Цена и кнопка "В корзину" скрыты -->
    <button class="btn-secondary">Уведомить при наличии</button>
</div>
```

#### 6. Логика цены в корзине (критично!)
```
ПРАВИЛО:
1. Цена ВЫРОСЛА → показать НОВУЮ (более высокую) цену
2. Цена УПАЛА → показать СТАРУЮ (более высокую) цену из корзины

Результат: пользователь НИКОГДА не платит дешевле добавленной цены
```

**Реализация**
```php
public function calculateItemPrice(CartItem $item): int
{
    $product = Product::find($item->product_id);
    $priceAtAdding = $item->price_when_added;  // Цена при добавлении
    $currentPrice = $product->price;           // Текущая цена

    if ($currentPrice > $priceAtAdding) {
        // ЦЕНА ВЫРОСЛА - использовать новую
        return $currentPrice;
    } else {
        // ЦЕНА УПАЛА или осталась - использовать СТАРУЮ
        return $priceAtAdding;
    }
}

// Результат: final_price = max(price_at_adding, current_price)
```

---

## 🏢 B2B РЕЖИМ (Business-to-Business)

**Для юридических лиц и ИП (с ИНН и бизнес-картой)**

### Определение B2B:
```php
// Условие для определения B2B-режима:
$isB2B = $request->has('inn') && $request->has('business_card_id');

// Нужны ОБЪА: ИНН И бизнес-карта
```

### Характеристики B2B:

#### 1. Оптовые цены (ниже розничных)
```php
// B2B-цены < B2C-цены
$pricing = PricingService::getPricesB2B($productId, $businessGroup);

// Пример:
// - Товар стоит 1000 ₽ в B2C
// - В B2B - 850 ₽ (15% скидка)
// - При обороте > 100k ₽ в месяц - дополнительно 5% (720 ₽)
```

#### 2. Специальные условия
```php
// 1. Расширенный кредит (вместо предоплаты)
$creditLimit = B2BService::calculateCreditLimit($businessGroup);
// Может быть 50k-500k ₽ в зависимости от истории

// 2. Отсрочка платежа (7-30 дней)
$paymentTerm = $businessGroup->b2b_payment_term ?? 14;  // 14 дней по умолчанию

// 3. Корпоративная ЛК с отчётами
// 4. API-доступ к товарам и ценам
// 5. Интеграция с 1С/бухгалтерией
```

#### 3. Отдельная B2B-витрина
```php
// B2B-товары:
// - Могут быть недоступны для B2C
// - Отображаются в отдельном каталоге
// - Имеют минимальный заказ (MOQ)
// - Групповые скидки по объёму

Product::where('b2b_only', true)->get();  // B2B товары
Product::where('b2b_moq', '>', 0)->get();  // С минимальным заказом
```

#### 4. Другая логика комиссий и выплат
```php
// B2C комиссия: 14% всегда
$b2cCommission = 0.14;

// B2B комиссия (переговорная):
$b2bCommission = match ($businessGroup->b2b_tier) {
    'standard' => 0.12,   // 12% - стандарт для новых
    'silver' => 0.10,     // 10% - при обороте > 500k
    'gold' => 0.08,       // 8% - при обороте > 2M
    'platinum' => 0.05,   // 5% - при обороте > 10M
};
```

#### 5. Налоговые реквизиты и счёта
```php
// B2B требует:
// - УНП / ИНН продавца
// - КПП (для РФ)
// - Юридический адрес
// - Банковские реквизиты
// - Счёта-фактуры (по требованию)
// - НДС (если применимо)

$b2bData = [
    'inn' => $request->get('inn'),
    'kpp' => $request->get('kpp'),
    'legal_address' => $request->get('legal_address'),
    'bank_account' => $request->get('bank_account'),
    'is_nds_applicable' => true,
];
```

---

## 🔄 ЛОГИКА ПЕРЕКЛЮЧЕНИЯ РЕЖИМОВ

### Регистрация / Вход

```php
// 1. Пользователь заходит без ИНН → B2C режим
if (!auth()->check()) {
    session(['mode' => 'B2C']);
}

// 2. Пользователь заходит с ИНН → предложить B2B
if ($user->hasB2BCredentials()) {
    // Показать выбор: "Режим B2C" / "Режим B2B"
    // Сохранить выбор в session:
    session(['mode' => $selectedMode]);  // 'B2C' или 'B2B'
}
```

### Переключение режима в ЛК

```php
// middleware: CheckMode
public function handle(Request $request, Closure $next)
{
    $mode = session('mode', 'B2C');
    
    // Установить контекст для сервисов
    app('mode.context')->set($mode);
    
    return $next($request);
}
```

### Отображение интерфейса

```php
// resources/views/marketplace/products.blade.php
@if (session('mode') === 'B2C')
    <!-- B2C интерфейс: Розничные цены, одна корзина, 20 мин резерв -->
    <div class="b2c-catalog">
        @foreach ($products as $product)
            <div class="product-card">
                <p class="price">{{ $product->price }} ₽</p>
                <button @click="addToCartB2C({{ $product->id }})">В корзину</button>
            </div>
        @endforeach
    </div>
@elseif (session('mode') === 'B2B')
    <!-- B2B интерфейс: Оптовые цены, кредит, отсрочка, API -->
    <div class="b2b-catalog">
        @foreach ($products as $product)
            <div class="product-card">
                <p class="price b2b-price">{{ $product->price_b2b }} ₽</p>
                <p class="text-muted">MOQ: {{ $product->b2b_moq }} шт</p>
                <button @click="addToCartB2B({{ $product->id }})">Заказать оптом</button>
            </div>
        @endforeach
    </div>
    
    <!-- B2B специфичный блок -->
    <div class="b2b-credit-info">
        <p>Кредитный лимит: {{ $businessGroup->credit_limit }} ₽</p>
        <p>Используется: {{ $businessGroup->credit_used }} ₽</p>
        <p>Срок платежа: {{ $businessGroup->payment_term }} дней</p>
    </div>
@endif
```

---

## 💾 МОДЕЛИ И ТАБЛИЦЫ

### B2B BusinessGroup (юридическое лицо)

```php
Schema::create('business_groups', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants');
    $table->string('legal_name');
    $table->string('inn')->unique();
    $table->string('kpp')->nullable();
    $table->string('legal_address');
    $table->string('bank_account');
    $table->enum('b2b_tier', ['standard', 'silver', 'gold', 'platinum'])->default('standard');
    $table->integer('turnover_month')->default(0);  // Оборот за месяц
    $table->integer('credit_limit')->default(0);    // Кредитный лимит
    $table->integer('credit_used')->default(0);     // Использовано
    $table->integer('payment_term')->default(14);   // Дней на оплату
    $table->json('b2b_data')->nullable();            // Налоговые реквизиты
    $table->timestamps();
    
    $table->comment('B2B группы: юридические лица и ИП');
});
```

### CartMode (определение режима)

```php
// Структура сессии:
session([
    'mode' => 'B2C',  // или 'B2B'
    'business_group_id' => 123,  // Если B2B
]);

// В Middleware:
class EnsureB2BMode {
    public function handle($request, $next) {
        if (session('mode') === 'B2B') {
            if (!$request->user()->business_group_id) {
                session(['mode' => 'B2C']);
            }
        }
        return $next($request);
    }
}
```

---

## 📊 СРАВНЕНИЕ B2C vs B2B

| Характеристика | B2C | B2B |
|----------------|-----|-----|
| **Пользователь** | Физлицо | Юридлицо / ИП |
| **Требование** | - | ИНН + бизнес-карта |
| **Цены** | Розничные | Оптовые (-10–30%) |
| **Минимальный заказ** | 1 шт | Зависит от товара |
| **Резерв товаров** | 20 мин | Нет (оплата авансом или по факту) |
| **Способ оплаты** | Полная предоплата | Авансом + кредит / отсрочка |
| **Срок оплаты** | Сразу | 7–30 дней (договорной) |
| **Кредит** | Нет | Да, с лимитом |
| **Документы** | Нет | Счёта, счёта-фактуры, акты |
| **API** | Нет | Да, с ключом доступа |
| **Отчёты** | Нет | Да, по обороту, платежам, кредиту |
| **Скидка по обороту** | Нет | Да, в зависимости от tier |
| **Витрина** | Общая | Отдельная B2B |
| **Комиссия платформы** | 14% | 8–12% (договорная) |

---

## ✅ ЧЕКЛИСТ РЕАЛИЗАЦИИ B2C/B2B

- [ ] Миграция business_groups
- [ ] Логика определения режима ($isB2C, $isB2B)
- [ ] Отдельные PricingService::getPricesB2C() и getPricesB2B()
- [ ] B2B-витрина (отдельные товары, MOQ, отсрочка)
- [ ] Сессионное переключение режима
- [ ] Отдельные корзины для B2C и B2B
- [ ] Разные комиссии (14% B2C vs 8–12% B2B)
- [ ] Логика кредита и отсрочки платежа
- [ ] Документы (счёта, счёта-фактуры)
- [ ] API для B2B (авторизация по ключу)
- [ ] Отчёты по обороту и платежам
- [ ] UI, различающий B2C и B2B

---

**B2C/B2B режимы ОБЯЗАТЕЛЬНЫ для всех вертикалей.**  
**Нарушение логики режимов = критическая ошибка.**
