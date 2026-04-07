<?php declare(strict_types=1);

namespace App\Services\Party;


use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Party\PartyProduct;
use App\Models\Party\PartyOrder;
use App\Models\Party\PartyTheme;
use Illuminate\Database\Eloquent\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class PartySuppliesService
{

    public function __construct(
        private readonly Request $request,
            private FraudControlService $fraud,
            private WalletService $wallet,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

        private function correlationId(): string
        {
            return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        }

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
            $this->logger->channel('audit')->info('Initializing PartyOrder creation', [
                'correlation_id' => $this->correlationId(),
                'user_id' => $data['user_id'] ?? null,
                'store_id' => $data['party_store_id'],
            ]);

            return $this->db->transaction(function () use ($data) {
                $this->fraud->check((int) $this->guard->id(), 'party_order_create', $this->request->ip());

                $order = new PartyOrder();
                $order->fill($data);
                $order->correlation_id = $this->correlationId();
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

                $this->logger->channel('audit')->info('Order successfully created', [
                    'order_uuid' => $order->uuid,
                    'correlation_id' => $this->correlationId(),
                ]);

                return $order;
            });
        }

        /**
         * Process order payment and notify store.
         */
        public function processPayment(int $orderId, int $amountCents): bool
        {
            return $this->db->transaction(function () use ($orderId, $amountCents) {
                $order = PartyOrder::lockForUpdate()->findOrFail($orderId);

                if ($order->payment_status === 'paid') {
                    return true;
                }

                // Credit wallet if payment is successful (simulation)
                $this->wallet->credit($order->party_store_id, $amountCents, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $this->correlationId(), null, null, ['order_id' => $order->id]);

                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'correlation_id' => $this->correlationId(),
                ]);

                return true;
            });
        }
}
