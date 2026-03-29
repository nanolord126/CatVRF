<?php

declare(strict_types=1);

namespace App\Domains\Content\Bloggers\Services;

use App\Domains\Education\Bloggers\Models\Stream;
use App\Domains\Education\Bloggers\Models\StreamOrder;
use App\Domains\Education\Bloggers\Models\StreamProduct;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\PaymentService;
use App\Services\RateLimiterService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Live Commerce Service
 * - Управление товарами в стриме
 * - Live-корзина и checkout
 * - Интеграция с платежами и кошельками
 */
class LiveCommerceService
{
    public function __construct(
        private readonly StreamService $streamService,
        private readonly FraudControlService $fraudControl,
        private readonly RateLimiterService $rateLimiter,
        private readonly PaymentService $paymentService,
        private readonly WalletService $walletService,
        private readonly InventoryManagementService $inventoryService,
    ) {}

    /**
     * Добавить товар в стрим
     */
    public function addProductToStream(
        int $streamId,
        int $productId,
        string $productName,
        int $priceKopiykas,
        ?int $originalPriceKopiykas = null,
        int $quantityAvailable = 999,
        string $correlationId = '',
    ): StreamProduct {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $stream = Stream::findOrFail($streamId);

        if (! $stream->canAcceptOrders()) {
            throw new \RuntimeException('Stream does not allow commerce');
        }

        // Rate limiting
        if (! $this->rateLimiter->allow('live_commerce:add:' . $streamId, config('bloggers.rate_limit.live_commerce_add'))) {
            throw new \RuntimeException('Rate limit exceeded for adding products');
        }

        return DB::transaction(function () use (
            $stream,
            $productId,
            $productName,
            $priceKopiykas,
            $originalPriceKopiykas,
            $quantityAvailable,
            $correlationId,
        ) {
            $product = StreamProduct::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'stream_id' => $stream->id,
                'product_id' => $productId,
                'business_group_id' => filament()?->getTenant()?->active_business_group?->id,
                'product_name' => $productName,
                'price_during_stream' => $priceKopiykas / 100, // Convert to rubles
                'original_price' => $originalPriceKopiykas ? $originalPriceKopiykas / 100 : null,
                'quantity_available' => $quantityAvailable,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Product added to stream', [
                'stream_id' => $stream->id,
                'product_id' => $productId,
                'price' => $priceKopiykas,
                'correlation_id' => $correlationId,
            ]);

            // Broadcast update to stream viewers
            broadcast(new \App\Domains\Content\Bloggers\Events\ProductAddedToStream($product))->toOthers();

            return $product;
        });
    }

    /**
     * Закрепить товар в плеере (pinned)
     */
    public function pinProduct(int $productId, int $position = 1, string $correlationId = ''): StreamProduct
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $product = StreamProduct::findOrFail($productId);

        if (! $product->stream->isLive()) {
            throw new \RuntimeException('Can only pin products in live streams');
        }

        // Check max pinned products
        $pinnedCount = $product->stream->pinnedProducts()->count();
        if ($pinnedCount >= config('bloggers.live_commerce.max_pinned_products')) {
            throw new \RuntimeException('Maximum pinned products limit reached');
        }

        return DB::transaction(function () use ($product, $position, $correlationId) {
            $product->update([
                'is_pinned' => true,
                'pin_position' => $position,
                'pinned_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            // Broadcast pin update
            broadcast(new \App\Domains\Content\Bloggers\Events\ProductPinned($product))->toOthers();

            Log::channel('audit')->info('Product pinned', [
                'product_id' => $product->id,
                'position' => $position,
                'correlation_id' => $correlationId,
            ]);

            return $product;
        });
    }

    /**
     * Открепить товар
     */
    public function unpinProduct(int $productId, string $correlationId = ''): StreamProduct
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $product = StreamProduct::findOrFail($productId);

        return DB::transaction(function () use ($product, $correlationId) {
            $product->update([
                'is_pinned' => false,
                'pin_position' => null,
                'unpinned_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            broadcast(new \App\Domains\Content\Bloggers\Events\ProductUnpinned($product))->toOthers();

            return $product;
        });
    }

    /**
     * Создать заказ и оплатить
     */
    public function createAndPayOrder(
        int $streamId,
        int $userId,
        int $productId,
        int $quantity = 1,
        string $paymentMethod = 'yuassa',
        string $correlationId = '',
    ): StreamOrder {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $stream = Stream::findOrFail($streamId);
        $product = StreamProduct::findOrFail($productId);

        if ($product->stream_id !== $stream->id) {
            throw new \RuntimeException('Product does not belong to this stream');
        }

        if ($product->isSoldOut()) {
            throw new \RuntimeException('Product is sold out');
        }

        // Fraud check
        $this->fraudControl->check([
            'operation_type' => 'stream_order_create',
            'user_id' => $userId,
            'amount' => (int)($product->price_during_stream * 100 * $quantity),
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use (
            $stream,
            $product,
            $userId,
            $quantity,
            $paymentMethod,
            $correlationId,
        ) {
            // Calculate totals
            $subtotal = (int)($product->price_during_stream * 100 * $quantity);
            $discount = 0;
            $shippingCost = 0;
            $total = $subtotal - $discount + $shippingCost;

            // Create order
            $order = StreamOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'stream_id' => $stream->id,
                'user_id' => $userId,
                'business_group_id' => filament()?->getTenant()?->active_business_group?->id,
                'order_reference' => 'SO-' . strtoupper(Str::random(12)),
                'stream_product_id' => $product->id,
                'status' => 'pending',
                'subtotal' => $subtotal / 100,
                'discount' => $discount / 100,
                'shipping_cost' => $shippingCost / 100,
                'total' => $total / 100,
                'payment_method' => $paymentMethod,
                'correlation_id' => $correlationId,
            ]);

            // Process payment
            try {
                $paymentResult = $this->paymentService->initPayment(
                    $total,
                    "Stream Order {$order->order_reference}",
                    $userId,
                    [
                        'order_id' => $order->id,
                        'stream_id' => $stream->id,
                        'product_id' => $product->id,
                    ],
                    $correlationId,
                );

                $order->update([
                    'payment_id' => $paymentResult['id'],
                    'idempotency_key' => $paymentResult['idempotency_key'] ?? null,
                ]);

                Log::channel('audit')->info('Stream order created', [
                    'order_id' => $order->id,
                    'amount' => $total,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            } catch (\Throwable $e) {
                $order->delete();
                throw $e;
            }
        });
    }

    /**
     * Отметить заказ как оплаченный
     */
    public function confirmPayment(int $orderId, string $correlationId = ''): StreamOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $order = StreamOrder::findOrFail($orderId);

        return DB::transaction(function () use ($order, $correlationId) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            // Update product quantity sold
            $product = $order->product;
            $product->increment('quantity_sold');

            // Reserve inventory (if applicable)
            // $this->inventoryService->deductStock(...)

            // Log revenue
            $stream = $order->stream;
            $stream->increment('total_revenue', $order->total);

            // Calculate and apply commission
            $commissionPercent = config('bloggers.monetization.commission_percent');
            $commission = (int)($order->total * $commissionPercent * 100);
            $stream->increment('platform_commission', $commission / 100);

            // Payout to blogger wallet
            $bloggerEarnings = (int)($order->total * 100) - $commission;

            Log::channel('audit')->info('Stream order paid', [
                'order_id' => $order->id,
                'amount' => $order->total,
                'commission' => $commission,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }
}
