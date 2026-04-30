<?php

declare(strict_types=1);

namespace Modules\Cart\Application\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Modules\Cart\Domain\DTOs\AddItemDto;
use Modules\Cart\Domain\Entities\CartItem;
use Modules\Cart\Domain\Repositories\CartItemRepositoryInterface;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class CartService
{
    private const int MAX_CARTS_PER_USER = 20;
    private const int RESERVE_MINUTES = 20;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartItemRepositoryInterface $cartItemRepository,
        private readonly UuidInterface $uuid,
    ) {}

    public function addItem(AddItemDto $dto, string $correlationId): CartItem
    {
        $correlationId ??= $this->uuid->toString();

        $this->fraud->check([
            'operation_type' => 'cart_add_item',
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $available = $this->getAvailableStock($dto->productId);
            if ($available < $dto->quantity) {
                throw new \DomainException('Insufficient stock');
            }

            $cart = $this->resolveCart($dto->userId, $dto->sellerId, $correlationId);

            $existing = $this->cartItemRepository->findByCartAndProduct($cart->id, $dto->productId);

            if ($existing) {
                $updated = $this->cartItemRepository->update($existing->id, [
                    'quantity' => $existing->quantity + $dto->quantity,
                    'current_price' => $dto->currentPrice,
                ]);
            } else {
                $updated = $this->cartItemRepository->create([
                    'cart_id' => $cart->id,
                    'product_id' => $dto->productId,
                    'quantity' => $dto->quantity,
                    'price_at_add' => $dto->currentPrice,
                    'current_price' => $dto->currentPrice,
                    'correlation_id' => $correlationId,
                    'uuid' => $this->uuid->toString(),
                ]);
            }

            $this->cartRepository->update($cart->id, [
                'reserved_until' => CarbonImmutable::now()->addMinutes(self::RESERVE_MINUTES),
            ]);

            $this->audit->log(
                action: 'cart_item_added',
                subjectType: \Modules\Cart\Domain\Entities\Cart::class,
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

            return $updated;
        });
    }

    public function refreshPrices(int $cartId, array $newPrices, string $correlationId): void
    {
        $correlationId ??= $this->uuid->toString();

        $this->db->transaction(function () use ($cartId, $newPrices) {
            $items = $this->cartItemRepository->findByCartId($cartId);

            foreach ($items as $item) {
                $newPrice = $newPrices[$item->productId] ?? $item->currentPrice;
                $this->cartItemRepository->update($item->id, [
                    'current_price' => max($item->priceAtAdd, $newPrice),
                ]);
            }
        });
    }

    public function removeItem(int $cartId, int $productId, string $correlationId): void
    {
        $correlationId ??= $this->uuid->toString();

        $this->db->transaction(function () use ($cartId, $productId, $correlationId) {
            $this->cartItemRepository->deleteByCartAndProduct($cartId, $productId);

            $this->audit->log(
                action: 'cart_item_removed',
                subjectType: \Modules\Cart\Domain\Entities\Cart::class,
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

    public function clear(int $cartId, string $reason, string $correlationId): void
    {
        $correlationId ??= $this->uuid->toString();

        $this->db->transaction(function () use ($cartId, $reason) {
            $this->cartItemRepository->deleteByCartId($cartId);
            $this->cartRepository->update($cartId, [
                'status' => $reason,
                'reserved_until' => null,
            ]);
        });
    }

    public function getUserCarts(int $userId): array
    {
        return $this->cartRepository->findActiveByUser($userId);
    }

    public function getTotal(int $cartId): int
    {
        return $this->cartItemRepository->getTotal($cartId);
    }

    private function resolveCart(int $userId, int $sellerId, string $correlationId): \Modules\Cart\Domain\Entities\Cart
    {
        $existing = $this->cartRepository->findByUserAndSeller($userId, $sellerId);
        if ($existing && $existing->isActive()) {
            return $existing;
        }

        $activeCount = $this->cartRepository->countActiveByUser($userId);
        if ($activeCount >= self::MAX_CARTS_PER_USER) {
            throw new \DomainException('Maximum cart limit exceeded');
        }

        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

        return $this->cartRepository->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'status' => 'active',
            'reserved_until' => CarbonImmutable::now()->addMinutes(self::RESERVE_MINUTES),
            'correlation_id' => $correlationId,
            'uuid' => $this->uuid->toString(),
        ]);
    }

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

        $reserved = $this->db->table('cart_items')
            ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
            ->where('cart_items.product_id', $productId)
            ->where('carts.status', 'active')
            ->where('carts.reserved_until', '>', CarbonImmutable::now())
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
