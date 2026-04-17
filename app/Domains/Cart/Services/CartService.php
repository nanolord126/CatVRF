<?php declare(strict_types=1);

namespace App\Domains\Cart\Services;

use App\Domains\Cart\DTOs\AddItemDto;
use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Carbon\CarbonInterface;

final readonly class CartService
{
    private const MAX_CARTS_PER_USER = 20;
    private const RESERVE_MINUTES = 20;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly Request $request,
        private readonly Guard $guard,
        private readonly CarbonInterface $carbon,
    ) {}

    /**
     * Add item to cart
     */
    public function addItem(AddItemDto $dto, string $correlationId): CartItem
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check([
            'operation_type' => 'cart_add_item',
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            // Check stock availability
            $available = $this->getAvailableStock($dto->productId);
            if ($available < $dto->quantity) {
                throw new \DomainException('Insufficient stock');
            }

            // Resolve or create cart
            $cart = $this->resolveCart($dto->userId, $dto->sellerId, $correlationId);

            // Add or update item
            $item = CartItem::firstOrNew([
                'cart_id' => $cart->id,
                'product_id' => $dto->productId,
            ]);

            if ($item->exists) {
                $item->quantity += $dto->quantity;
                $item->current_price = $dto->currentPrice;
                $item->save();
            } else {
                $item->fill([
                    'quantity' => $dto->quantity,
                    'price_at_add' => $dto->currentPrice,
                    'current_price' => $dto->currentPrice,
                    'correlation_id' => $correlationId,
                ])->save();
            }

            // Update reserve
            $cart->update(['reserved_until' => $this->carbon->now()->addMinutes(self::RESERVE_MINUTES)]);

            $this->audit->record(
                action: 'cart_item_added',
                subjectType: Cart::class,
                subjectId: $cart->id,
                newValues: [
                    'product_id' => $dto->productId,
                    'quantity' => $dto->quantity,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Cart item added', [
                'cart_id' => $cart->id,
                'product_id' => $dto->productId,
                'quantity' => $dto->quantity,
                'correlation_id' => $correlationId,
            ]);

            return $item;
        });
    }

    /**
     * Resolve or create cart for user+seller
     */
    private function resolveCart(int $userId, int $sellerId, string $correlationId): Cart
    {
        $existing = Cart::where('user_id', $userId)
            ->where('seller_id', $sellerId)
            ->active()
            ->first();

        if ($existing) {
            return $existing;
        }

        $activeCount = Cart::where('user_id', $userId)
            ->active()
            ->count();

        if ($activeCount >= self::MAX_CARTS_PER_USER) {
            throw new \DomainException('Maximum cart limit exceeded');
        }

        return Cart::create([
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'status' => 'active',
            'reserved_until' => $this->carbon->now()->addMinutes(self::RESERVE_MINUTES),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Refresh cart prices
     */
    public function refreshPrices(int $cartId, array $newPrices, string $correlationId): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($cartId, $newPrices, $correlationId) {
            $items = CartItem::where('cart_id', $cartId)->get();

            foreach ($items as $item) {
                $newPrice = $newPrices[$item->product_id] ?? $item->current_price;
                $item->current_price = max($item->price_at_add, $newPrice);
                $item->save();
            }
        });
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $cartId, int $productId, string $correlationId): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($cartId, $productId, $correlationId) {
            CartItem::where('cart_id', $cartId)
                ->where('product_id', $productId)
                ->delete();

            $this->audit->record(
                action: 'cart_item_removed',
                subjectType: Cart::class,
                subjectId: $cartId,
                newValues: ['product_id' => $productId],
                correlationId: $correlationId,
            );

            $this->logger->info('Cart item removed', [
                'cart_id' => $cartId,
                'product_id' => $productId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Clear cart
     */
    public function clear(int $cartId, string $reason = 'ordered', string $correlationId): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($cartId, $reason, $correlationId) {
            $cart = Cart::findOrFail($cartId);
            $cart->items()->delete();
            $cart->update(['status' => $reason, 'reserved_until' => null]);
        });
    }

    /**
     * Get user carts
     */
    public function getUserCarts(int $userId)
    {
        return Cart::where('user_id', $userId)
            ->active()
            ->with('items')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get cart total
     */
    public function getTotal(int $cartId): int
    {
        return CartItem::where('cart_id', $cartId)
            ->get()
            ->sum(fn ($item) => $item->getTotal());
    }

    /**
     * Get available stock (integrate with Inventory domain)
     */
    private function getAvailableStock(int $productId): int
    {
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

        $product = $this->db->table('products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return 0;
        }

        // Calculate available stock considering reserved quantities
        $reserved = $this->db->table('cart_items')
            ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
            ->where('cart_items.product_id', $productId)
            ->where('carts.status', 'active')
            ->where('carts.reserved_until', '>', $this->carbon->now())
            ->sum('cart_items.quantity');

        $available = max(0, ($product->stock_quantity ?? 0) - $reserved);

        $this->logger->debug('Stock availability checked', [
            'product_id' => $productId,
            'total_stock' => $product->stock_quantity ?? 0,
            'reserved' => $reserved,
            'available' => $available,
        ]);

        return $available;
    }
}
