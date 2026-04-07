<?php declare(strict_types=1);

namespace App\Services;


use Illuminate\Http\Request;
use App\Exceptions\CartLimitExceededException;
use App\Exceptions\FraudBlockedException;
use App\Domains\Inventory\Exceptions\InsufficientStockException;
use App\Domains\Inventory\Services\InventoryService;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Collection;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * CartService — сервис управления корзинами.
 * PRODUCTION MANDATORY — Канон CatVRF 2026.
 *
 * Правила:
 *   - 1 продавец = 1 активная корзина на пользователя
 *   - Максимум 20 корзин на пользователя
 *   - Резерв 20 минут (CartCleanupJob — каждую минуту)
 *   - Цена выросла → новая; цена упала → старая (никогда не платит меньше чем добавил)
 *   - Товар без наличия → не может быть добавлен
 *   - correlation_id + FraudControlService::check() + $this->db->transaction() обязательны
 */
final readonly class CartService
{
    private const MAX_CARTS_PER_USER  = 20;
    private const RESERVE_MINUTES     = 20;

    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private AuditService        $audit,
        private InventoryService    $inventory,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // Добавить товар в корзину
    // ─────────────────────────────────────────────────────────────

    /**
     * Добавить товар в корзину продавца.
     * Если корзины нет — создаём. Если есть — обновляем quantity.
     *
     * @throws CartLimitExceededException
     * @throws FraudBlockedException
     * @throws InsufficientStockException
     */
    public function addItem(
        int    $userId,
        int    $sellerId,
        int    $productId,
        int    $quantity,
        int    $currentPrice,
        string $correlationId = '',
    ): CartItem {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check($userId, 'cart_add_item', 0, (string) $this->request->ip());

        return $this->db->transaction(function () use ($userId, $sellerId, $productId, $quantity, $currentPrice, $correlationId): CartItem {

            // 1. Проверить наличие
            $available = $this->inventory->getAvailableStock($productId);
            if ($available < $quantity) {
                throw new InsufficientStockException(
                    productId: $productId,
                    warehouseId: 1,
                    requested: $quantity,
                    available: $available,
                    correlationId: $correlationId
                );
            }

            // 2. Получить или создать корзину для этого продавца
            $cart = $this->resolveCart($userId, $sellerId, $correlationId);

            // 3. Добавить / обновить позицию
            $item = CartItem::firstOrNew([
                'cart_id'    => $cart->id,
                'product_id' => $productId,
            ]);

            if ($item->exists) {
                // Обновляем количество, пересчитываем current_price по правилу
                $item->quantity      += $quantity;
                $item->current_price  = $currentPrice;
                // price_at_add не меняется!
                $item->save();
            } else {
                $item->fill([
                    'uuid'          => Str::uuid()->toString(),
                    'quantity'      => $quantity,
                    'price_at_add'  => $currentPrice,
                    'current_price' => $currentPrice,
                    'correlation_id' => $correlationId,
                ])->save();
            }

            // 4. Обновить резерв
            $cart->update(['reserved_until' => now()->addMinutes(self::RESERVE_MINUTES)]);

            // 5. Резерв в инвентаре
            $this->inventory->reserve(new \App\Domains\Inventory\DTOs\CreateReservationDto(
                tenantId: $sellerId,
                productId: $productId,
                warehouseId: 1, // fallback to a default warehouse or logic to find nearest warehouse
                quantity: $quantity,
                sourceType: 'cart',
                sourceId: $cart->id,
                correlationId: $correlationId,
                businessGroupId: $this->request->get('business_group_id')
            ));

            $this->audit->record('cart_item_added', Cart::class, $cart->id, [], [
                'product_id' => $productId,
                'quantity'   => $quantity,
            ], $correlationId);

            $this->logger->channel('audit')->info('CartService: item added', [
                'cart_id'        => $cart->id,
                'product_id'     => $productId,
                'quantity'       => $quantity,
                'correlation_id' => $correlationId,
            ]);

            return $item;
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Получить корзину (или создать)
    // ─────────────────────────────────────────────────────────────

    /**
     * Resolve (get or create) active cart for user+seller pair.
     *
     * @throws CartLimitExceededException
     */
    public function resolveCart(int $userId, int $sellerId, string $correlationId = ''): Cart
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $existing = Cart::where('user_id', $userId)
            ->where('seller_id', $sellerId)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Лимит 20 корзин на пользователя
        $activeCount = Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        if ($activeCount >= self::MAX_CARTS_PER_USER) {
            throw new CartLimitExceededException(
                "User #{$userId} has reached the maximum of " . self::MAX_CARTS_PER_USER . " active carts."
            );
        }

        return Cart::create([
            'tenant_id'      => function_exists('tenant') && tenant() ? tenant()->id : 1,
            'user_id'        => $userId,
            'seller_id'      => $sellerId,
            'status'         => 'active',
            'reserved_until' => now()->addMinutes(self::RESERVE_MINUTES),
            'correlation_id' => $correlationId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Обновить цены в корзине (актуализация)
    // ─────────────────────────────────────────────────────────────

    /**
     * Обновить current_price для всех позиций корзины по правилу канона:
     *   price_at_add < new_price  → current_price = new_price  (выросла)
     *   price_at_add > new_price  → current_price = price_at_add (упала, оставляем старую)
     */
    public function refreshPrices(int $cartId, array $newPrices, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($cartId, $newPrices, $correlationId): void {
            $items = CartItem::where('cart_id', $cartId)->get();

            foreach ($items as $item) {
                $newPrice = $newPrices[$item->product_id] ?? $item->current_price;

                $item->current_price = max($item->price_at_add, $newPrice);
                $item->save();
            }

            $this->logger->channel('audit')->info('CartService: prices refreshed', [
                'cart_id'        => $cartId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Удалить позицию
    // ─────────────────────────────────────────────────────────────

    public function removeItem(int $cartId, int $productId, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($cartId, $productId, $correlationId): void {
            $item = CartItem::where('cart_id', $cartId)
                ->where('product_id', $productId)
                ->firstOrFail();

            $reservation = \App\Domains\Inventory\Models\Reservation::where('cart_id', $cartId)
                ->whereHas('inventoryItem', function ($query) use ($productId) {
                    $query->where('product_id', $productId);
                })->first();

            if ($reservation) {
                $this->inventory->releaseReservation($reservation->id, $correlationId);
            }

            $item->delete();

            $this->logger->channel('audit')->info('CartService: item removed', [
                'cart_id'        => $cartId,
                'product_id'     => $productId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Очистить / завершить корзину
    // ─────────────────────────────────────────────────────────────

    public function clear(int $cartId, string $reason = 'ordered', string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($cartId, $reason, $correlationId): void {
            $cart = Cart::findOrFail($cartId);

            foreach ($cart->items as $item) {
                $reservation = \App\Domains\Inventory\Models\Reservation::where('cart_id', $cartId)
                    ->whereHas('inventoryItem', function ($query) use ($item) {
                        $query->where('product_id', $item->product_id);
                    })->first();

                if ($reservation) {
                    $this->inventory->releaseReservation($reservation->id, $correlationId);
                }
            }

            $cart->items()->delete();
            $cart->update(['status' => $reason, 'reserved_until' => null]);

            $this->logger->channel('audit')->info('CartService: cart cleared', [
                'cart_id'        => $cartId,
                'reason'         => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Получить корзины пользователя
    // ─────────────────────────────────────────────────────────────

    public function getUserCarts(int $userId): Collection
    {
        return Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->with('items.product')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────
    // Итого по корзине (с учётом правила цен)
    // ─────────────────────────────────────────────────────────────

    public function getTotal(int $cartId): int
    {
        return CartItem::where('cart_id', $cartId)
            ->get()
            ->sum(fn (CartItem $item): int => $item->getEffectivePrice() * $item->quantity);
    }
}
