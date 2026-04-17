<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionDiscountService
{
    private const CACHE_TTL = 3600;

    /**
     * Apply discount to a product
     */
    public function applyDiscount(int $productId, float $discountPercent, string $discountType, ?string $endDate = null, int $tenantId): bool
    {
        try {
            $product = DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$product) {
                return false;
            }

            // Calculate new price
            $originalPrice = $product->price_b2c;
            $discountAmount = $originalPrice * ($discountPercent / 100);
            $discountedPrice = $originalPrice - $discountAmount;

            // Update product with discount
            DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'old_price' => $originalPrice,
                    'price_b2c' => $discountedPrice,
                    'discount_percent' => $discountPercent,
                    'discount_type' => $discountType,
                    'discount_start_at' => Carbon::now(),
                    'discount_end_at' => $endDate ? Carbon::parse($endDate) : null,
                    'updated_at' => Carbon::now(),
                ]);

            // Clear cache
            Cache::tags(["fashion_products:{$tenantId}"])->flush();

            Log::info('Discount applied to product', [
                'product_id' => $productId,
                'discount_percent' => $discountPercent,
                'discount_type' => $discountType,
                'original_price' => $originalPrice,
                'discounted_price' => $discountedPrice,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to apply discount', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove discount from a product
     */
    public function removeDiscount(int $productId, int $tenantId): bool
    {
        try {
            $product = DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$product || !$product->old_price) {
                return false;
            }

            // Restore original price
            DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'price_b2c' => $product->old_price,
                    'old_price' => null,
                    'discount_percent' => null,
                    'discount_type' => null,
                    'discount_start_at' => null,
                    'discount_end_at' => null,
                    'updated_at' => Carbon::now(),
                ]);

            // Clear cache
            Cache::tags(["fashion_products:{$tenantId}"])->flush();

            Log::info('Discount removed from product', [
                'product_id' => $productId,
                'restored_price' => $product->old_price,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove discount', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get active discounts for a store
     */
    public function getActiveDiscounts(int $storeId, int $tenantId): array
    {
        $cacheKey = "fashion_discounts:{$tenantId}:{$storeId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($storeId, $tenantId) {
            return DB::table('fashion_products')
                ->where('fashion_store_id', $storeId)
                ->where('tenant_id', $tenantId)
                ->whereNotNull('discount_percent')
                ->where(function ($query) {
                    $query->whereNull('discount_end_at')
                          ->orWhere('discount_end_at', '>', Carbon::now());
                })
                ->select('id', 'name', 'price_b2c', 'old_price', 'discount_percent', 'discount_type', 'discount_end_at')
                ->get()
                ->toArray();
        });
    }

    /**
     * Create coupon code
     */
    public function createCoupon(string $code, float $discountPercent, string $discountType, ?int $maxUses = null, ?string $expiryDate = null, int $tenantId): bool
    {
        try {
            DB::table('fashion_coupons')->insert([
                'code' => strtoupper($code),
                'discount_percent' => $discountPercent,
                'discount_type' => $discountType,
                'max_uses' => $maxUses,
                'uses_count' => 0,
                'expiry_date' => $expiryDate ? Carbon::parse($expiryDate) : null,
                'is_active' => true,
                'tenant_id' => $tenantId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            Log::info('Coupon created', [
                'code' => $code,
                'discount_percent' => $discountPercent,
                'tenant_id' => $tenantId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create coupon', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate and apply coupon
     */
    public function applyCoupon(string $code, int $userId, int $tenantId): array
    {
        $coupon = DB::table('fashion_coupons')
            ->where('code', strtoupper($code))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code'];
        }

        // Check expiry
        if ($coupon->expiry_date && Carbon::parse($coupon->expiry_date)->isPast()) {
            return ['valid' => false, 'message' => 'Coupon has expired'];
        }

        // Check max uses
        if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            return ['valid' => false, 'message' => 'Coupon usage limit reached'];
        }

        // Check if user already used this coupon
        $userUsage = DB::table('fashion_coupon_usages')
            ->where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->count();

        if ($userUsage > 0) {
            return ['valid' => false, 'message' => 'You have already used this coupon'];
        }

        return [
            'valid' => true,
            'discount_percent' => $coupon->discount_percent,
            'discount_type' => $coupon->discount_type,
        ];
    }

    /**
     * Record coupon usage
     */
    public function recordCouponUsage(string $code, int $userId, int $orderId, int $tenantId): bool
    {
        try {
            $coupon = DB::table('fashion_coupons')
                ->where('code', strtoupper($code))
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$coupon) {
                return false;
            }

            DB::table('fashion_coupon_usages')->insert([
                'coupon_id' => $coupon->id,
                'user_id' => $userId,
                'order_id' => $orderId,
                'tenant_id' => $tenantId,
                'used_at' => Carbon::now(),
            ]);

            DB::table('fashion_coupons')
                ->where('id', $coupon->id)
                ->increment('uses_count');

            Log::info('Coupon usage recorded', [
                'coupon_id' => $coupon->id,
                'user_id' => $userId,
                'order_id' => $orderId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to record coupon usage', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get flash sale products
     */
    public function getFlashSaleProducts(int $tenantId, int $limit = 10): array
    {
        return DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('is_flash_sale', true)
            ->where('flash_sale_end_at', '>', Carbon::now())
            ->where('available_stock', '>', 0)
            ->select('id', 'name', 'price_b2c', 'old_price', 'discount_percent', 'flash_sale_end_at', 'image_url')
            ->orderByDesc('discount_percent')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
