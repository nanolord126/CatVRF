<?php

declare(strict_types=1);

namespace App\Services\Party;

use App\Models\Party\PartyStore;
use App\Models\Party\PartyProduct;
use App\Models\Party\PartyOrder;
use App\Models\Party\PartyTheme;
use App\Models\Party\PartyCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Exceptions\InsufficientStockException;
use Illuminate\Database\Eloquent\Collection;

/**
 * PartySuppliesService.
 * Orchestrates decor, balloons, and fireworks management.
 */
final readonly class PartySuppliesService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private WalletService $wallet,
        private string $correlationId
    ) {}

    /**
     * Get active products filtered by seasonal theme and category.
     */
    public function getCatalog(array $filters = []): Collection
    {
        $query = PartyProduct::query()->where('is_active', true);

        if (isset($filters['theme_id'])) {
            $query->where('party_theme_id', $filters['theme_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('party_category_id', $filters['category_id']);
        }

        if (isset($filters['is_b2b'])) {
            $query->where('is_b2b', (bool) $filters['is_b2b']);
        }

        return $query->with(['store', 'theme', 'category'])->get();
    }

    /**
     * Create a new party order with stock management and prepay logic.
     */
    public function createOrder(array $data): PartyOrder
    {
        Log::channel('audit')->info('Initializing PartyOrder creation', [
            'correlation_id' => $this->correlationId,
            'user_id' => $data['user_id'] ?? null,
            'store_id' => $data['party_store_id'],
        ]);

        return DB::transaction(function () use ($data) {
            $this->fraudControl->check($data);

            $order = new PartyOrder();
            $order->fill($data);
            $order->correlation_id = $this->correlationId;
            $order->status = 'pending';
            $order->payment_status = 'unpaid';

            // Automatic prepayment calculation for large orders (>50000 cents)
            if ($order->total_cents > 50000 && $order->prepayment_cents <= 0) {
                $order->prepayment_cents = (int) ($order->total_cents * 0.3); // 30% prepayment
            }

            // Deduct stock for all items
            foreach ($data['items'] as $item) {
                $product = PartyProduct::lockForUpdate()->find($item['product_id']);
                if (!$product || $product->current_stock < $item['quantity']) {
                    throw new InsufficientStockException("Insufficient stock for product: {$product->name}");
                }
                $product->decrement('current_stock', $item['quantity']);
            }

            $order->save();

            Log::channel('audit')->info('Order successfully created', [
                'order_uuid' => $order->uuid,
                'correlation_id' => $this->correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Process order payment and notify store.
     */
    public function processPayment(int $orderId, int $amountCents): bool
    {
        return DB::transaction(function () use ($orderId, $amountCents) {
            $order = PartyOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->payment_status === 'paid') {
                return true;
            }

            // Credit wallet if payment is successful (simulation)
            $this->wallet->credit($order->party_store_id, $amountCents, 'order_payment', $this->correlationId);

            $order->payment_status = $amountCents >= $order->total_cents ? 'paid' : 'partially_paid';
            $order->save();

            return true;
        });
    }

    /**
     * Get seasonal themes available now.
     */
    public function getActiveThemes(): Collection
    {
        $now = now();
        return PartyTheme::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->where('is_seasonal', false)
                    ->orWhere(function ($q) use ($now) {
                        $q->whereDate('season_start', '<=', $now)
                          ->whereDate('season_end', '>=', $now);
                    });
            })
            ->get();
    }
}
