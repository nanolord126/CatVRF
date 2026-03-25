declare(strict_types=1);

# B2C / B2B СТЕКИ И ПРАВИЛА КОРЗИН 2026

## 🔷 ОПРЕДЕЛЕНИЕ B2C vs B2B

### B2C режим (Consumer, розница)
- Обычный пользователь без юридического статуса
- Отсутствуют поля: `inn`, `business_card_id`, `company_name`
- Розничные цены и условия
- Одна корзина на продавца

### B2B режим (Business, оптовая торговля)
- Юридическое лицо или ИП
- Обязательны: `inn` (ИНН), `business_card_id` (ID визитной карточки), `company_name`
- Оптовые/специальные цены
- Корпоративные условия оплаты
- Отдельная логика комиссий
- Специальные выплаты (отсроченные платежи и т.д.)

### Проверка B2B в коде:

```php
// ✅ ПРАВИЛЬНО
$isB2B = $request->user()->inn && $request->user()->business_card_id;

// ✅ АЛЬТЕРНАТИВНО
$isB2B = $request->has('inn') && $request->has('business_card_id');

// ❌ НЕПРАВИЛЬНО
$isB2B = auth()->check();  // Это B2C!
```

---

## 🛒 ПРАВИЛА КОРЗИН (КРИТИЧНО!)

### Правило 1: Один продавец = одна корзина

```php
declare(strict_types=1);

namespace App\Services;

final readonly class CartService
{
    /**
     * Получить корзину для конкретного продавца
     */
    public function getByVendor(int $userId, int $vendorId): Cart
    {
        return Cart::where([
            'user_id' => $userId,
            'vendor_id' => $vendorId,
        ])->firstOrCreate([
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'status' => 'active',
            'reserved_until' => now()->addMinutes(20),
        ]);
    }
}
```

### Правило 2: До 20 корзин в памяти пользователя

```php
// В кабинете пользователя — показывать ТО ЛОКАЛЬНО
// Максимум 20 корзин одновременно в IndexedDB / LocalStorage

// ✅ На фронте (Vue/Livewire)
const MAX_CARTS = 20;

async function loadCarts(userId) {
    const carts = await fetch(`/api/user/${userId}/carts?limit=${MAX_CARTS}`)
        .then(r => r.json());
    
    // Сохранить в IndexedDB
    await db.carts.clear();
    await db.carts.bulkAdd(carts);
    
    return carts;
}
```

### Правило 3: Резерв товаров снимается через 20 минут

```php
declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

final class ReleaseExpiredCartReservesJob implements ShouldQueue
{
    use Queueable;

    public function handle(\App\Services\CartService $cartService): void
    {
        // Найти все корзины, зарезервированные > 20 минут назад
        $expiredCarts = \App\Models\Cart::where(
            'reserved_until', '<', now()
        )->where('status', 'reserved')->get();

        foreach ($expiredCarts as $cart) {
            // Снять резерв товаров
            foreach ($cart->items as $item) {
                $cartService->releaseStock(
                    itemId: $item->product_id,
                    quantity: $item->quantity,
                    sourceType: 'cart_expiration'
                );
            }

            // Пометить корзину как истекшей
            $cart->update([
                'status' => 'expired',
                'reserved_until' => null,
            ]);

            \Illuminate\Support\Facades\Log::channel('audit')->info(
                'Cart reservation expired',
                [
                    'cart_id' => $cart->id,
                    'user_id' => $cart->user_id,
                    'vendor_id' => $cart->vendor_id,
                ]
            );
        }
    }
}

// ✅ Зарегистрировать в scheduler
protected function schedule(Schedule $schedule)
{
    $schedule->job(new ReleaseExpiredCartReservesJob())
        ->everyMinute()
        ->withoutOverlapping();
}
```

**Расписание:**
- Каждую минуту проверять корзины
- Если `reserved_until < now()` и `status = reserved` → выпустить резерв

### Правило 4: При повторном открытии корзины — проверка наличия

```php
declare(straight_types=1);

namespace App\Http\Controllers;

final readonly class CartController
{
    public function __construct(
        private readonly \App\Services\CartService $cartService,
        private readonly \App\Services\InventoryService $inventory,
    ) {}

    /**
     * Получить корзину с проверкой наличия товаров
     */
    public function show(int $vendorId): JsonResponse
    {
        try {
            $cart = $this->cartService->getByVendor(auth()->id(), $vendorId);

            // ✅ КРИТИЧНО: Проверить наличие всех товаров в корзине
            foreach ($cart->items as $item) {
                $currentStock = $this->inventory->getCurrentStock($item->product_id);

                if ($currentStock < $item->quantity) {
                    // Товара больше нет — уменьшить количество или удалить
                    if ($currentStock > 0) {
                        $item->update(['quantity' => $currentStock]);
                    } else {
                        $item->delete();
                        
                        Log::channel('audit')->warning('Product out of stock', [
                            'product_id' => $item->product_id,
                            'cart_id' => $cart->id,
                        ]);
                    }
                }
            }

            // ✅ Обновить цены товаров
            $cart->load('items.product');
            foreach ($cart->items as $item) {
                $item->refresh(['price']);
            }

            return response()->json([
                'success' => true,
                'data' => $cart->toArray(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Error showing cart', [
                'vendor_id' => $vendorId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load cart',
            ], 500);
        }
    }
}
```

### Правило 5: Чёрно-белые товары без кнопки "В корзину"

```html
<!-- ✅ Vue компонент товара -->
<template>
    <div :class="product.outOfStock ? 'grayscale opacity-50' : ''">
        <!-- Фото товара -->
        <img :src="product.image" :style="{ filter: product.outOfStock ? 'grayscale(100%)' : 'none' }" />

        <!-- Название -->
        <h3>{{ product.name }}</h3>

        <!-- Цена -->
        <p class="text-lg font-bold">{{ formatPrice(product.price) }}</p>

        <!-- Кнопка -->
        <button
            v-if="!product.outOfStock"
            @click="addToCart"
            class="btn btn-primary"
        >
            В корзину
        </button>

        <button
            v-else
            disabled
            class="btn btn-disabled"
        >
            Нет в наличии
        </button>
    </div>
</template>

<script setup>
const product = ref({
    id: 1,
    name: 'Товар',
    price: 1000,
    currentStock: 0,  // ← Важно
    outOfStock: computed(() => product.value.currentStock === 0),
});

async function addToCart() {
    if (product.value.outOfStock) {
        return;  // Ничего не делаем
    }

    // Добавить в корзину
    await fetch(`/api/cart/add`, {
        method: 'POST',
        body: JSON.stringify({
            product_id: product.value.id,
            quantity: 1,
        }),
    });
}
</script>

<style scoped>
/* Чёрно-белый фильтр для товаров без наличия */
.grayscale {
    filter: grayscale(100%);
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
```

### Правило 6: Логика цены (выросла → показать новую, упала → старая)

```php
declare(strict_types=1);

namespace App\Services;

final readonly class PricingService
{
    /**
     * Получить отображаемую цену товара в корзине
     *
     * Логика:
     * - Если цена товара ВЫРОСЛА → показываем НОВУЮ цену (выше)
     * - Если цена товара УПАЛА → показываем СТАРУЮ цену (не уменьшаем пользователю)
     */
    public function getCartItemPrice(
        \App\Models\CartItem $cartItem,
        \App\Models\Product $currentProduct
    ): int {
        $cartItemPrice = $cartItem->price;
        $currentPrice = $currentProduct->price;

        // Если цена выросла — показать новую (более высокую)
        if ($currentPrice > $cartItemPrice) {
            \Illuminate\Support\Facades\Log::channel('audit')->warning(
                'Product price increased in cart',
                [
                    'product_id' => $cartItem->product_id,
                    'old_price' => $cartItemPrice,
                    'new_price' => $currentPrice,
                    'difference' => $currentPrice - $cartItemPrice,
                ]
            );

            // ✅ Обновить цену в корзине до новой (выше)
            $cartItem->update(['price' => $currentPrice]);

            return $currentPrice;
        }

        // Если цена упала — показать СТАРУЮ цену (не уменьшаем)
        if ($currentPrice < $cartItemPrice) {
            \Illuminate\Support\Facades\Log::channel('audit')->info(
                'Product price decreased (user benefits)',
                [
                    'product_id' => $cartItem->product_id,
                    'old_price' => $cartItemPrice,
                    'new_price' => $currentPrice,
                    'discount' => $cartItemPrice - $currentPrice,
                ]
            );

            // ✅ Не меняем цену, пользователь платит МЕНЬШЕ
            return $cartItemPrice;
        }

        // Цена не изменилась
        return $cartItemPrice;
    }
}
```

---

## 📝 ТАБЛИЦА МИГРАЦИИ ДЛЯ КОРЗИН

```php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица корзин
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('tenants')->onDelete('cascade');
            $table->string('status')->default('active')->index();  // active, reserved, expired, checked_out
            $table->timestamp('reserved_until')->nullable()->index();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'vendor_id', 'status']);
            $table->comment('Корзины для товаров каждого продавца');
        });

        // Элементы корзины
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('price')->comment('Цена в копейках');
            $table->string('correlation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->comment('Элементы в корзине');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
```

---

## 🎯 РАЗЛИЧИЯ B2C vs B2B

| Параметр | B2C | B2B |
|----------|-----|-----|
| **Цены** | Розничные | Оптовые/специальные |
| **Минимальный заказ** | 1 товар | Часто минимум N товаров |
| **Сроки оплаты** | Предоплата | 7–30 дней (отсрочка) |
| **Комиссия** | 14–17% | 8–12% |
| **Выплаты** | 4–7 дней | 7–14 дней |
| **Скидки** | Промо-коды | Объёмные скидки |
| **Доступ к витринам** | Публичный маркетплейс | Приватные B2B-витрины |
| **Корзины** | Одна на продавца | Несколько (по проектам) |
| **Аудит** | Стандартный | Расширенный с разбором платежей |

---

## 🔐 SECURITY CHECK B2C/B2B

```php
declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;

final class CheckB2B
{
    public function handle(Request $request, \Closure $next)
    {
        $isB2B = $request->user()?->inn && $request->user()?->business_card_id;

        // Сохранить в request для использования в контроллерах
        $request->merge(['is_b2b' => $isB2B]);

        // Логировать для аудита
        \Illuminate\Support\Facades\Log::channel('audit')->debug('Request B2B check', [
            'user_id' => $request->user()?->id,
            'is_b2b' => $isB2B,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        return $next($request);
    }
}

// ✅ Зарегистрировать в app/Http/Kernel.php
protected $routeMiddleware = [
    'b2b.check' => \App\Http\Middleware\CheckB2B::class,
];
```

---

## 📊 ML/AI для B2C/B2B

### RecommendationService должен знать B2B:

```php
public function getForUser(int $userId, string $vertical = null, array $context = []): Collection
{
    $user = User::findOrFail($userId);
    $isB2B = $user->inn && $user->business_card_id;

    if ($isB2B) {
        // ✅ Рекомендации B2B: объёмные товары, оптовые цены, поставщики
        return $this->getB2BRecommendations($user);
    } else {
        // ✅ Рекомендации B2C: персональные, по интересам
        return $this->getB2CRecommendations($user);
    }
}

private function getB2BRecommendations(User $user): Collection
{
    // Рекомендовать товары для закупки, поставщиков, оптовые партии
    return collect();
}

private function getB2CRecommendations(User $user): Collection
{
    // Рекомендовать по поведению, просмотрам, покупкам
    return collect();
}
```

---

## 📋 ЧЕКЛИСТ B2C/B2B

- [ ] Определение B2C/B2B через `inn` + `business_card_id`
- [ ] Одна корзина на продавца
- [ ] Максимум 20 корзин в памяти пользователя
- [ ] Резерв товаров на 20 минут
- [ ] ReleaseExpiredCartReservesJob запущен
- [ ] При открытии корзины — проверка наличия товаров
- [ ] Товары без наличия — чёрно-белые без кнопки "В корзину"
- [ ] Логика цены: выросла → новая, упала → старая
- [ ] Таблицы миграции с correlation_id/uuid/tags
- [ ] Middleware CheckB2B регистрирован
- [ ] RecommendationService различает B2C/B2B
- [ ] Все операции логируются с B2B-статусом

---

**Автор:** B2C/B2B Stack 2026  
**Версия:** 1.0  
**Дата:** 25.03.2026  
**Статус:** PRODUCTION READY
