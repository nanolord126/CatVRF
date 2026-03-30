<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SportsNutritionDomainService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlationId;

        public function __construct(
            private readonly FraudControlService $fraudControl
        ) {
            $this->correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());
        }

        /**
         * Save or update a supplement product with strict transactional checks.
         */
        public function saveProduct(SportsNutritionProductDto $dto, string $cid = null): SportsNutritionProduct
        {
            $cid = $cid ?? $this->correlationId;

            Log::channel('audit')->info('Supplement product update requested', [
                'cid' => $cid,
                'sku' => $dto->sku,
                'user' => auth()->id() ?? 'system',
            ]);

            return DB::transaction(function () use ($dto, $cid) {
                // 1. Mandatory Fraud Check (L8 integration)
                $this->fraudControl->check($cid, 'sn_product_save');

                // 2. Expiry validation (no entry for already expired or <30d products)
                $expiry = Carbon::parse($dto->expiry_date);
                if ($expiry->isPast() || $expiry->diffInDays(now()) < 30) {
                     Log::channel('fraud_alert')->warning('Attempt to stock near-expiry supplement.', [
                        'cid' => $cid,
                        'sku' => $dto->sku,
                        'expiry' => $dto->expiry_date
                     ]);
                     throw new \RuntimeException('Cannot stock supplements with less than 30 days of shelf life.');
                }

                // 3. Mutation
                $product = SportsNutritionProduct::updateOrCreate(
                    ['sku' => $dto->sku, 'tenant_id' => tenant()->id ?? 0],
                    [
                        'store_id' => $dto->store_id,
                        'category_id' => $dto->category_id,
                        'name' => $dto->name,
                        'brand' => $dto->brand,
                        'description' => 'Cleaned Sports Nutrition Item: ' . $dto->name,
                        'price_b2c' => $dto->price_b2c,
                        'price_b2b' => $dto->price_b2b,
                        'stock_quantity' => $dto->stock_quantity,
                        'form_factor' => $dto->form_factor,
                        'servings_count' => $dto->servings_count,
                        'nutrition_facts' => $dto->nutrition_facts,
                        'allergens' => $dto->allergens,
                        'expiry_date' => $dto->expiry_date,
                        'is_vegan' => $dto->is_vegan,
                        'is_gmo_free' => $dto->is_gmo_free,
                        'is_published' => $dto->is_published,
                        'tags' => $dto->tags,
                        'correlation_id' => $cid,
                    ]
                );

                Log::channel('audit')->info('Supplement product saved successfully.', [
                    'cid' => $cid,
                    'pid' => $product->id,
                ]);

                return $product;
            });
        }

        /**
         * Deduct raw consumables (ingredients) when a private label batch is manufactured.
         */
        public function useConsumable(int $consumableId, float $kgValue, string $reason, string $cid = null): void
        {
            $cid = $cid ?? $this->correlationId;

            DB::transaction(function () use ($consumableId, $kgValue, $reason, $cid) {
                $consumable = SportsNutritionConsumable::lockForUpdate()->findOrFail($consumableId);

                if ($consumable->stock_kg < $kgValue) {
                    throw new \RuntimeException("Insufficient raw material stock for {$consumable->name}.");
                }

                $currentStock = $consumable->stock_kg;
                $newStock = $currentStock - $kgValue;

                $consumable->update(['stock_kg' => $newStock, 'correlation_id' => $cid]);

                Log::channel('inventory')->info('Supplement raw material deducted', [
                    'cid' => $cid,
                    'item' => $consumableId,
                    'kg' => $kgValue,
                    'before' => $currentStock,
                    'after' => $newStock,
                    'reason' => $reason
                ]);

                if ($newStock <= $consumable->min_threshold) {
                    Log::channel('fraud_alert')->warning('LOW STOCK: Re-order raw ingredient soon.', [
                        'cid' => $cid,
                        'item' => $consumable->name
                    ]);
                }
            });
        }

        /**
         * Create personalized subscription box for specific training goals.
         */
        public function createSubscriptionBox(SubscriptionBoxDto $dto, string $cid = null): SportsNutritionSubscriptionBox
        {
            $cid = $cid ?? $this->correlationId;

            return DB::transaction(function () use ($dto, $cid) {
                 return SportsNutritionSubscriptionBox::create([
                    'name' => $dto->name,
                    'description' => $dto->description,
                    'price_monthly' => $dto->price_monthly,
                    'included_skus' => $dto->included_skus,
                    'training_goal' => $dto->training_goal,
                    'is_active' => $dto->is_active,
                    'correlation_id' => $cid,
                    'tenant_id' => tenant()->id ?? 0,
                 ]);
            });
        }

        /**
         * B2B mode: Bulk discount logic based on item category.
         */
        public function calculateWholesalePrice(SportsNutritionProduct $product, int $quantity): int
        {
            $base = $product->price_b2b;

            // Custom B2B bulk rules
            if ($quantity >= 100) return (int) ($base * 0.85); // 15% discount for 100+
            if ($quantity >= 50) return (int) ($base * 0.92);   // 8% discount for 50+

            return $base;
        }
}
