<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ToyDomainService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControl
        ) {}

        /**
         * Upsert a toy with safety certification and B2B/B2C pricing.
         */
        public function upsertToy(ToySaveDto $dto): Toy
        {
            Log::channel('audit')->info('Toy Upsert Initiated', [
                'cid' => $dto->correlationId,
                'sku' => $dto->sku
            ]);

            return DB::transaction(function () use ($dto) {
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

                Log::channel('audit')->info('Toy Upsert Successful', [
                    'cid' => $dto->correlationId,
                    'toy_uuid' => $toy->uuid
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
            Log::channel('audit')->info('B2B Toy Order Processed', ['cid' => $dto->correlationId]);

            // Security: Block suspicious bulk orders
            $this->fraudControl->check(['cid' => $dto->correlationId, 'type' => 'b2b_toy_order']);

            return DB::transaction(function () use ($dto) {
                $totalAmount = 0;
                $orderItems = [];

                foreach ($dto->items as $item) {
                    $toy = Toy::lockForUpdate()->find($item['toy_id']);

                    if (!$toy || $toy->stock_quantity < $item['quantity']) {
                        throw new \Exception('Insufficient stock for toy: ' . ($toy->title ?? 'Unknown'));
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
                    'tenant_id' => auth()->user()->tenant_id ?? 1,
                    'user_id' => auth()->id() ?? 0,
                    'b2b_company_id' => $dto->companyId,
                    'store_id' => $dto->storeId,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'gift_requested' => $dto->giftPackaging,
                    'correlation_id' => $dto->correlationId,
                    'metadata' => array_merge($dto->metadata, ['items' => $orderItems])
                ]);

                Log::channel('audit')->info('B2B Toy Order Completed', [
                    'cid' => $dto->correlationId,
                    'order_uuid' => $order->uuid,
                    'total' => $totalAmount
                ]);

                return $order;
            });
        }

        /**
         * Submit Review with Age Appropriateness Feedback.
         */
        public function submitReview(int $toyId, int $userId, int $rating, string $comment, string $cid): ToyReview
        {
            return DB::transaction(function () use ($toyId, $userId, $rating, $comment, $cid) {
                $review = ToyReview::create([
                    'toy_id' => $toyId,
                    'user_id' => $userId,
                    'rating' => $rating,
                    'comment' => $comment,
                    'correlation_id' => $cid,
                    'metadata' => [
                        'client_ip' => request()->ip(),
                        'ai_sentiment' => 'pending'
                    ]
                ]);

                Log::channel('audit')->info('Toy Review Added', [
                    'cid' => $cid,
                    'toy_id' => $toyId,
                    'rating' => $rating
                ]);

                return $review;
            });
        }
}
