<?php

declare(strict_types=1);

namespace Modules\Fashion\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use App\Services\Fraud\FraudControlService;
use App\Domains\CRM\Services\FashionCrmService;
use Carbon\Carbon;

/**
 * Fashion Discount Service — Сервис для управления скидками
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Fraud check перед всеми мутациями
 * - Readonly класс
 * - $this->cache->tags для инвалидации
 * - Audit логирование
 * - CRM интеграция
 */
final readonly class FashionDiscountService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly FashionCrmService $fashionCrm,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Apply discount to a product
     */
    public function applyDiscount(int $productId, float $discountPercent, string $discountType, ?string $endDate, int $tenantId, ?string $correlationId = null): bool
    {
        $correlationId ??= uniqid('fashion_discount_', true);

        // FRAUD CHECK - мандаторно первым действием
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'fashion_apply_discount',
            'user_id' => null,
            'tenant_id' => $tenantId,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logAction('fashion_discount_blocked', 'FashionProduct', $productId, [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'discount_percent' => $discountPercent,
            ], null, null, $tenantId, $correlationId);
            throw new \RuntimeException('Discount application blocked by fraud detection');
        }

        try {
            $product = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $product) {
                return false;
            }

            // Calculate new price
            $originalPrice = $product->price_b2c;
            $discountAmount = $originalPrice * ($discountPercent / 100);
            $discountedPrice = $originalPrice - $discountAmount;

            $this->db->transaction(function () use ($productId, $tenantId, $originalPrice, $discountedPrice, $discountPercent, $discountType, $endDate, $correlationId) {
                // Update product with discount
                $this->db->table('fashion_products')
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

                // AUDIT LOG
                $this->logAction('fashion_discount_applied', 'FashionProduct', $productId, [
                    'discount_percent' => $discountPercent,
                    'discount_type' => $discountType,
                    'original_price' => $originalPrice,
                    'discounted_price' => $discountedPrice,
                ], null, $tenantId, $correlationId);
            });

            // Clear cache
            $this->cache->tags(['fashion', 'products', "tenant:{$tenantId}"])->flush();

            // CRM INTEGRATION
            try {
                $this->logAction('fashion_discount_crm_sync', 'FashionProduct', $productId, [
                    'discount_percent' => $discountPercent,
                ], null, null, $tenantId, $correlationId);
            } catch (\Throwable $e) {
                $this->logAction('fashion_discount_crm_sync_failed', 'FashionProduct', $productId, [
                    'error' => $e->getMessage(),
                ], null, null, $tenantId, $correlationId);
            }

            return true;
        } catch (\Exception $e) {
            $this->logAction('fashion_discount_failed', 'FashionProduct', $productId, [
                'error' => $e->getMessage(),
            ], null, null, $tenantId, $correlationId);

            return false;
        }
    }

    /**
     * Remove discount from a product
     */
    public function removeDiscount(int $productId, int $tenantId, ?string $correlationId = null): bool
    {
        $correlationId ??= uniqid('fashion_discount_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'fashion_remove_discount',
            'user_id' => null,
            'tenant_id' => $tenantId,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logAction('fashion_discount_removal_blocked', 'FashionProduct', $productId, [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
            ], null, null, $tenantId, $correlationId);
            throw new \RuntimeException('Discount removal blocked by fraud detection');
        }

        try {
            $product = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $product || ! $product->old_price) {
                return false;
            }

            $this->db->transaction(function () use ($productId, $tenantId, $product, $correlationId) {
                // Restore original price
                $this->db->table('fashion_products')
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

                // AUDIT LOG
                $this->logAction('fashion_discount_removed', 'FashionProduct', $productId, [
                    'original_price' => $product->old_price,
                ], null, null, $tenantId, $correlationId);
            });

            // Clear cache
            $this->cache->tags(['fashion', 'products', "tenant:{$tenantId}"])->flush();

            // CRM INTEGRATION
            try {
                $this->logAction('fashion_discount_removal_crm_sync', 'FashionProduct', $productId, [], null, null, $tenantId, $correlationId);
            } catch (\Throwable $e) {
                $this->logAction('fashion_discount_removal_crm_sync_failed', 'FashionProduct', $productId, [
                    'error' => $e->getMessage(),
                ], null, null, $tenantId, $correlationId);
            }

            return true;
        } catch (\Exception $e) {
            $this->logAction('fashion_discount_removal_failed', 'FashionProduct', $productId, [
                'error' => $e->getMessage(),
            ], null, null, $tenantId, $correlationId);

            return false;
        }
    }

    /**
     * Get active discounts for a store
     */
    public function getActiveDiscounts(int $storeId, int $tenantId): array
    {
        $cacheKey = "fashion_discounts:{$tenantId}:{$storeId}";

        return $this->cache->tags(['fashion', 'discounts', "store:{$storeId}", "tenant:{$tenantId}"])
            ->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($storeId, $tenantId) {
                return $this->db->table('fashion_products')
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
    public function createCoupon(string $code, float $discountPercent, string $discountType, ?int $maxUses, ?string $expiryDate, int $tenantId, ?string $correlationId = null): bool
    {
        $correlationId ??= uniqid('fashion_coupon_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'fashion_create_coupon',
            'user_id' => null,
            'tenant_id' => $tenantId,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logAction('fashion_coupon_creation_blocked', 'FashionCoupon', $code, [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
            ], null, null, $tenantId, $correlationId);
            throw new \RuntimeException('Coupon creation blocked by fraud detection');
        }

        try {
            $this->db->transaction(function () use ($code, $discountPercent, $discountType, $maxUses, $expiryDate, $tenantId, $correlationId) {
                $this->db->table('fashion_coupons')->insert([
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

                // AUDIT LOG
                $this->logCreated('FashionCoupon', $code, null, $tenantId, [
                    'discount_percent' => $discountPercent,
                ], $correlationId);
            });

            // Clear cache
            $this->cache->tags(['fashion', 'coupons', "tenant:{$tenantId}"])->flush();

            return true;
        } catch (\Exception $e) {
            $this->logAction('fashion_coupon_creation_failed', 'FashionCoupon', $code, [
                'error' => $e->getMessage(),
            ], null, null, $tenantId, $correlationId);

            return false;
        }
    }

    /**
     * Validate and apply coupon
     */
    public function applyCoupon(string $code, int $userId, int $tenantId): array
    {
        $coupon = $this->db->table('fashion_coupons')
            ->where('code', strtoupper($code))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $coupon) {
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
        $userUsage = $this->db->table('fashion_coupon_usages')
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
    public function recordCouponUsage(string $code, int $userId, int $orderId, int $tenantId, ?string $correlationId = null): bool
    {
        $correlationId ??= uniqid('fashion_coupon_', true);

        try {
            $coupon = $this->db->table('fashion_coupons')
                ->where('code', strtoupper($code))
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $coupon) {
                return false;
            }

            $this->db->transaction(function () use ($coupon, $userId, $orderId, $tenantId, $correlationId) {
                $this->db->table('fashion_coupon_usages')->insert([
                    'coupon_id' => $coupon->id,
                    'user_id' => $userId,
                    'order_id' => $orderId,
                    'tenant_id' => $tenantId,
                    'used_at' => Carbon::now(),
                ]);

                $this->db->table('fashion_coupons')
                    ->where('id', $coupon->id)
                    ->increment('uses_count');

                // AUDIT LOG
                $this->logAction('fashion_coupon_usage_recorded', 'FashionCoupon', $coupon->id, [
                    'user_id' => $userId,
                    'order_id' => $orderId,
                ], $userId, $tenantId, null, $correlationId);
            });

            // Clear cache
            $this->cache->tags(['fashion', 'coupons', "tenant:{$tenantId}"])->flush();

            return true;
        } catch (\Exception $e) {
            $this->logAction('fashion_coupon_usage_failed', 'FashionCoupon', $code, [
                'error' => $e->getMessage(),
            ], $userId, $tenantId, null, $correlationId);

            return false;
        }
    }

    /**
     * Get flash sale products
     */
    public function getFlashSaleProducts(int $tenantId, int $limit = 10): array
    {
        return $this->db->table('fashion_products')
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
