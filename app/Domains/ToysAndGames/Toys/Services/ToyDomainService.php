<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class ToyDomainService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Upsert a toy with safety certification and B2B/B2C pricing.
         */
        public function upsertToy(ToySaveDto $dto): Toy
        {
            $this->logger->info('Toy Upsert Initiated', [
                'cid' => $dto->correlationId,
                'sku' => $dto->sku,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            return $this->db->transaction(function () use ($dto) {
                $toy = Toy::updateOrCreate(
                    ['sku' => $dto->sku, 'tenant_id' => $dto->tenantId],
                    [
                        'store_id' => $dto->storeId,
                        'category_id' => $dto->categoryId,
                        'age_group_id' => $dto->ageGroupId,
                        'title' => $dto->title,
                        'description' => $dto->description,
                        'price_b2c' => $dto->priceB2c,
                        'price_b2b' => $dto->priceB2b,
                        'stock_quantity' => $dto->stockQuantity,
                        'safety_certification' => $dto->safetyCertification,
                        'material_type' => $dto->materialType,
                        'is_gift_wrappable' => $dto->isGiftWrappable,
                        'is_active' => $dto->isActive,
                        'tags' => $dto->tags,
                        'metadata' => $dto->metadata,
                        'correlation_id' => $dto->correlationId
                    ]
                );

                $this->logger->info('Toy Upsert Successful', [
                    'cid' => $dto->correlationId,
                    'toy_uuid' => $toy->uuid,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return $toy;
            });
        }

        /**
         * Create a Volume Order for B2B institutions (Kindergartens/Schools).
         * Enforces minimum quantities for wholesale pricing.
         */
        public function createB2BOrder(VolumeToyOrderDto $dto): ToyOrder
        {
            $this->logger->info('B2B Toy Order Processed', ['cid' => $dto->correlationId]);

            // Security: Block suspicious bulk orders
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'b2b_toy_order', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($dto) {
                $totalAmount = 0;
                $orderItems = [];

                foreach ($dto->items as $item) {
                    $toy = Toy::lockForUpdate()->find($item['toy_id']);

                    if (!$toy || $toy->stock_quantity < $item['quantity']) {
                        throw new \RuntimeException('Insufficient stock for toy: ' . ($toy->title ?? 'Unknown'));
                    }

                    // Minimum 10 items for B2B pricing in this vertical domain
                    $price = ($item['quantity'] >= 10) ? $toy->price_b2b : $toy->price_b2c;
                    $lineTotal = $price * $item['quantity'];

                    $totalAmount += $lineTotal;
                    $orderItems[] = [
                        'toy_id' => $toy->id,
                        'sku' => $toy->sku,
                        'qty' => $item['quantity'],
                        'unit_price' => $price
                    ];

                    // Inventory decrement
                    $toy->decrement('stock_quantity', $item['quantity']);
                }

                $order = ToyOrder::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $this->guard->user()->tenant_id ?? 1,
                    'user_id' => $this->guard->id() ?? 0,
                    'b2b_company_id' => $dto->companyId,
                    'store_id' => $dto->storeId,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'gift_requested' => $dto->giftPackaging,
                    'correlation_id' => $dto->correlationId,
                    'metadata' => array_merge($dto->metadata, ['items' => $orderItems])
                ]);

                $this->logger->info('B2B Toy Order Completed', [
                    'cid' => $dto->correlationId,
                    'order_uuid' => $order->uuid,
                    'total' => $totalAmount,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return $order;
            });
        }

        /**
         * Submit Review with Age Appropriateness Feedback.
         */
        public function submitReview(int $toyId, int $userId, int $rating, string $comment, string $cid): ToyReview
        {
            return $this->db->transaction(function () use ($toyId, $userId, $rating, $comment, $cid) {
                $review = ToyReview::create([
                    'toy_id' => $toyId,
                    'user_id' => $userId,
                    'rating' => $rating,
                    'comment' => $comment,
                    'correlation_id' => $cid,
                    'metadata' => [
                        'client_ip' => $this->request->ip(),
                        'ai_sentiment' => 'pending'
                    ]
                ]);

                $this->logger->info('Toy Review Added', [
                    'cid' => $cid,
                    'toy_id' => $toyId,
                    'rating' => $rating,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return $review;
            });
        }
}
