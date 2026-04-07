<?php declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\Services;

use Carbon\Carbon;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class HobbyDomainService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Create or Update a Hobby Product with full audit trail.
         */
        public function upsertProduct(HobbyProductSaveDto $dto): HobbyProduct
        {
            $cid = $dto->correlationId ?: (string) Str::uuid();

            $this->logger->info('Hobby Product Upsert Started', [
                'sku' => $dto->sku,
                'cid' => $cid,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            return $this->db->transaction(function () use ($dto, $cid) {
                $product = HobbyProduct::updateOrCreate(
                    ['sku' => $dto->sku],
                    [
                        'store_id' => $dto->storeId,
                        'category_id' => $dto->categoryId,
                        'title' => $dto->title,
                        'description' => $dto->description,
                        'price_b2c' => $dto->priceB2c,
                        'price_b2b' => $dto->priceB2b,
                        'stock_quantity' => $dto->stockQuantity,
                        'skill_level' => $dto->skillLevel,
                        'images' => $dto->images,
                        'tags' => $dto->tags,
                        'is_active' => $dto->isActive,
                        'correlation_id' => $cid
                    ]
                );

                $this->logger->info('Hobby Product Upsert Success', [
                    'id' => $product->id,
                    'cid' => $cid,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return $product;
            });
        }

        /**
         * Process B2B Volume Procurement (Wholesale mode).
         * Rule: Minimum 5 units triggers wholesale price_b2b.
         */
        public function createB2BOrder(VolumeOrderDto $dto): \Illuminate\Database\Eloquent\Model
        {
            $this->logger->info('B2B Craft Material Procurement Initiated', [
                'user_id' => $dto->userId,
                'product_id' => $dto->productId,
                'quantity' => $dto->quantity,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            return $this->db->transaction(function () use ($dto) {
                $product = HobbyProduct::lockForUpdate()->findOrFail($dto->productId);

                if ($product->stock_quantity < $dto->quantity) {
                    throw new \RuntimeException('Insufficient inventory for wholesale volume.');
                }

                // Pricing logic for B2B wholesale
                $finalPrice = ($dto->quantity >= 5 && $product->price_b2b)
                    ? $product->price_b2b
                    : $product->price_b2c;

                $orderAmount = $finalPrice * $dto->quantity;

                // Simplified Order creation surrogate (Actual Order logic in separate layer)
                $order = $this->db->table('hobby_orders')->insertGetId([
                    'user_id' => $dto->userId,
                    'total_amount' => $orderAmount,
                    'status' => 'pending',
                    'is_b2b' => true,
                    'correlation_id' => $dto->correlationId ?: (string) Str::uuid(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                // Inventory decrement
                $product->decrement('stock_quantity', $dto->quantity);

                $this->logger->info('B2B Wholesale Order Created', [
                    'order_id' => $order,
                    'total' => $orderAmount,
                    'sku' => $product->sku,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return (object) ['id' => $order, 'total_amount' => $orderAmount, 'is_b2b' => true];
            });
        }

        /**
         * Submit Review with Fraud Check.
         */
        public function submitReview(int $userId, string $reviewableType, int $reviewableId, int $rating, string $comment): HobbyReview
        {
            // Fraud check surrogate (Actual logic in FraudControlService)
            if ($rating === 5 && empty($comment)) {
                $this->logger->warning('Suspicious 5-star review (no comment)', [
                    'user_id' => $userId,
                    'item_id' => $reviewableId
                ]);
            }

            return HobbyReview::create([
                'user_id' => $userId,
                'reviewable_type' => $reviewableType,
                'reviewable_id' => $reviewableId,
                'rating' => $rating,
                'comment' => $comment,
                'tenant_id' => $this->guard->user()->tenant_id ?? 1
            ]);
        }
}
