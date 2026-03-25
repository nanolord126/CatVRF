# CART RULES 2026 — ПРАВИЛА УПРАВЛЕНИЯ КОРЗИНАМИ И ТОВАРАМИ

**Версия:** 1.0  
**Статус:** PRODUCTION MANDATORY  
**Дата:** 25.03.2026

---

## 📦 ПРАВИЛО КОРЗИН (ЖЕСТКОЕ)

### 1. ОСНОВНЫЕ ПРАВИЛА

**Один продавец = одна корзина**
```
Пользователь может иметь несколько корзин, но:
- Каждая корзина привязана к одному продавцу/бизнесу
- Максимум 20 активных корзин одновременно в памяти
- Корзины хранятся в Redis (ключ: cart:{userId}:{sellerId}:{B2C|B2B})
```

**Резерв товаров на 20 минут**
```php
// При добавлении в корзину:
CartService::reserve($itemId, $quantity, $cartId, $expiresAt = now()->addMinutes(20));

// CartCleanupJob ежеминутно проверяет и снимает резервы:
CartCleanupJob::dispatch();
```

**Проверка наличия при открытии корзины**
```php
// Каждый раз при открытии корзины:
public function show(Cart $cart): JsonResponse
{
    // 1. Проверить актуальное наличие всех товаров
    $cart->items()->each(function ($item) {
        $currentStock = InventoryManagementService::getCurrentStock($item->product_id);
        
        if ($currentStock === 0) {
            $item->update(['is_available' => false, 'is_visible' => false]);
        } else if ($currentStock < $item->quantity) {
            // Товара меньше, чем в корзине - уменьшить
            $item->update(['quantity' => $currentStock]);
        }
    });
    
    return response()->json($cart->fresh());
}
```

### 2. ЦЕНООБРАЗОВАНИЕ В КОРЗИНЕ

**Логика цены товара в корзине**
```
1. Цена товара выросла → показать новую (более высокую) цену
   - Пользователь ДОЛЖЕН видеть актуальную цену
   - Предупредить: "Цена товара выросла на 300 ₽"

2. Цена товара упала → оставить СТАРУЮ цену в корзине
   - Пользователь получает выгоду
   - Но показать "Экономия: 500 ₽" за счёт падения цены
   - На финальной странице оплаты показать итоговую цену
```

**Реализация**
```php
public function calculateCartPrice(Cart $cart): CartPriceResult
{
    $totalPrice = 0;
    $priceDifferences = [];

    foreach ($cart->items() as $item) {
        $product = Product::find($item->product_id);
        $originalPrice = $item->price_when_added;  // Цена при добавлении
        $currentPrice = $product->price;           // Текущая цена
        
        if ($currentPrice > $originalPrice) {
            // ЦЕНА ВЫРОСЛА - использовать новую цену
            $itemPrice = $currentPrice * $item->quantity;
            $priceDifferences[$item->id] = [
                'type' => 'price_increase',
                'old_price' => $originalPrice,
                'new_price' => $currentPrice,
                'difference' => ($currentPrice - $originalPrice) * $item->quantity,
            ];
        } else {
            // ЦЕНА УПАЛА или осталась - использовать СТАРУЮ цену из корзины
            $itemPrice = $originalPrice * $item->quantity;
            if ($currentPrice < $originalPrice) {
                $priceDifferences[$item->id] = [
                    'type' => 'price_decrease',
                    'old_price' => $originalPrice,
                    'new_price' => $currentPrice,
                    'savings' => ($originalPrice - $currentPrice) * $item->quantity,
                ];
            }
        }
        
        $totalPrice += $itemPrice;
    }

    return new CartPriceResult(
        totalPrice: $totalPrice,
        priceDifferences: $priceDifferences,
        message: "Итоговая цена включает последние изменения цен товаров",
    );
}
```

### 3. ОТОБРАЖЕНИЕ НЕДОСТУПНЫХ ТОВАРОВ

**Товары, которых нет в наличии**
```
Отображать ЧЁРНО-БЕЛЫМИ (grayscale: true) в корзине:
- Изображение в оттенках серого (filter: grayscale(100%))
- Название пусто
- Цена скрыта
- Кнопка "В корзину" отсутствует
- Статус: "Нет в наличии"
- Кнопка "Уведомить": пользователь может подписаться на возврат товара
```

**HTML/Blade**
```html
<div class="cart-item" :class="{ 'grayscale opacity-50': !item.is_available }">
    <img 
        :src="item.image_url" 
        :style="{ filter: item.is_available ? 'none' : 'grayscale(100%)' }"
    />
    
    @if (!item.is_available)
        <span class="badge badge-danger">Нет в наличии</span>
        <button @click="notifyWhenAvailable(item.id)">
            Уведомить о наличии
        </button>
    @else
        <p class="price">{{ item.price }} ₽</p>
        <button @click="addToCart(item.id)">В корзину</button>
    @endif
</div>
```

### 4. АВТОМАТИЧЕСКОЕ СНЯТИЕ РЕЗЕРВА

**CartCleanupJob - ежеминутная задача**
```php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CartCleanupJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        try {
            // 1. Найти корзины с истёкшим резервом (> 20 минут)
            $expiredCarts = Cart::where('reserved_until', '<', now())
                ->where('status', 'active')
                ->get();

            DB::transaction(function () use ($expiredCarts) {
                foreach ($expiredCarts as $cart) {
                    // 2. Снять резерв со всех товаров
                    foreach ($cart->items() as $item) {
                        InventoryManagementService::releaseStock(
                            itemId: $item->product_id,
                            quantity: $item->quantity,
                            sourceType: 'cart_cleanup',
                            sourceId: $cart->id,
                        );
                    }

                    // 3. Очистить корзину или пометить как инактивную
                    $cart->update([
                        'status' => 'abandoned',
                        'abandoned_at' => now(),
                    ]);

                    // 4. Отправить уведомление пользователю
                    // "Ваша корзина была очищена из-за истечения времени резерва"
                    NotificationService::send(
                        userId: $cart->user_id,
                        title: 'Корзина очищена',
                        message: 'Резерв товаров истёк. Добавьте их снова, если нужно.',
                        correlationId: $cart->correlation_id,
                    );
                }
            });

            Log::channel('audit')->info('Cart cleanup completed', [
                'cleaned_carts' => count($expiredCarts),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Cart cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

// === SCHEDULE: app/Console/Kernel.php ===
protected function schedule(Schedule $schedule): void
{
    $schedule->job(new CartCleanupJob())
        ->everyMinute()
        ->withoutOverlapping();
}
```

### 5. МОДЕЛЬ Cart

```php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Cart extends Model
{
    protected $table = 'carts';

    protected $fillable = [
        'user_id',
        'seller_id',  // tenant_id продавца/бизнеса
        'mode',  // 'B2C' или 'B2B'
        'status',  // 'active', 'abandoned', 'converted'
        'reserved_until',
        'abandoned_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'reserved_until' => 'datetime',
        'abandoned_at' => 'datetime',
        'tags' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isExpired(): bool
    {
        return $this->reserved_until < now();
    }
}
```

**Migration**
```php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('seller_id')->constrained('tenants');
    $table->enum('mode', ['B2C', 'B2B'])->default('B2C');
    $table->enum('status', ['active', 'abandoned', 'converted'])->default('active');
    $table->timestamp('reserved_until')->nullable();
    $table->timestamp('abandoned_at')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->json('tags')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'seller_id', 'mode']);
    $table->index(['status', 'reserved_until']);
    
    $table->comment('Корзины товаров: max 20 на пользователя, резерв 20 мин');
});

Schema::create('cart_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('products');
    $table->integer('quantity')->default(1);
    $table->integer('price_when_added')->comment('Цена товара при добавлении в корзину');
    $table->boolean('is_available')->default(true);
    $table->boolean('is_visible')->default(true);
    $table->timestamps();

    $table->index(['cart_id', 'product_id']);
});
```

### 6. ИНТЕГРАЦИЯ С INVENTORY

```php
declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Str;

final readonly class CartService
{
    public function __construct(
        private readonly InventoryManagementService $inventory,
    ) {}

    /**
     * Добавить товар в корзину + сделать резерв
     */
    public function addItem(
        int $userId,
        int $sellerId,
        int $productId,
        int $quantity,
        string $mode = 'B2C',
        string $correlationId = '',
    ): CartItem {
        $correlationId = $correlationId ?: Str::uuid();

        $cart = Cart::firstOrCreate(
            [
                'user_id' => $userId,
                'seller_id' => $sellerId,
                'mode' => $mode,
            ],
            [
                'status' => 'active',
                'reserved_until' => now()->addMinutes(20),
                'correlation_id' => $correlationId,
            ]
        );

        // 1. Проверить наличие и сделать резерв
        $this->inventory->reserveStock(
            itemId: $productId,
            quantity: $quantity,
            sourceType: 'cart',
            sourceId: $cart->id,
        );

        // 2. Добавить в корзину
        $product = \App\Models\Product::findOrFail($productId);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price_when_added' => $product->price,
            'is_available' => true,
        ]);

        // 3. Обновить резерв корзины
        $cart->update(['reserved_until' => now()->addMinutes(20)]);

        return $item;
    }

    /**
     * Удалить товар из корзины + снять резерв
     */
    public function removeItem(CartItem $item): void
    {
        $this->inventory->releaseStock(
            itemId: $item->product_id,
            quantity: $item->quantity,
            sourceType: 'cart',
            sourceId: $item->cart_id,
        );

        $item->delete();
    }

    /**
     * Максимум 20 корзин на пользователя
     */
    public function enforceLimits(int $userId): void
    {
        $cartCount = Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        if ($cartCount > 20) {
            // Удалить самые старые корзины
            Cart::where('user_id', $userId)
                ->where('status', 'active')
                ->orderBy('updated_at')
                ->limit($cartCount - 20)
                ->update(['status' => 'abandoned']);
        }
    }
}
```

---

## ✅ ЧЕКЛИСТ РЕАЛИЗАЦИИ ПРАВИЛА КОРЗИН

- [ ] Миграции для carts и cart_items
- [ ] CartService с методами добавить/удалить/очистить
- [ ] InventoryManagementService с методами reserve/release
- [ ] CartCleanupJob (ежеминутная очистка резервов)
- [ ] Логика ценообразования (цена выросла → новая, упала → старая)
- [ ] Отображение недоступных товаров чёрно-белыми
- [ ] Максимум 20 корзин на пользователя
- [ ] Максимум 20 минут резерва
- [ ] Проверка наличия при открытии корзины
- [ ] Уведомления об истечении резерва и падении цены

---

**Это правило ОБЯЗАТЕЛЬНО для всех вертикалей.**  
**Нарушение хотя бы одного пункта = критическая ошибка в функциональности магазина.**
