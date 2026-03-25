<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Promo Campaign Management Service
 * Production 2026 CANON
 *
 * Manages promotion codes: apply, validate, track usage, manage budgets
 * - Budget enforcement
 * - Per-user usage caps
 * - Fraud abuse detection
 * - Commission calculations
 *
 * @author CatVRF Team
 * @version 2026.03.24
 */
final class PromoService
{
    /**
     * Apply promo code to order
     *
     * @param string $code Promo code
     * @param int $orderAmount Order amount in kopeks
     * @param string $vertical Vertical (beauty, food, hotels, auto)
     * @param int $userId User ID
     * @param string $correlationId Tracing ID
     * @return array {discount_amount, new_amount, code, applied_at}
     * @throws \Exception
     */
    public function applyPromo(string $code, int $orderAmount, string $vertical, int $userId, string $correlationId): array
    {
        $this->fraudControl->check([
            'operation' => promo_apply,
            'user_id' => $userId,
            'discount' => $discount,
            'correlation_id' => $correlationId,
        ]);


        $this->log->channel('audit')->info('Method applyPromo() called', [
            'correlation_id' => $correlationId ?? Str::uuid(),
        ]);


        return $this->db->transaction(function () use ($code, $orderAmount, $vertical, $userId, $correlationId): array {
            // Get campaign
            $campaign = $this->db->table('promo_campaigns')
                ->where('code', strtoupper($code))
                ->where('tenant_id', tenant()->id)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw new \Exception('Promo code not found');
            }

            // Check if active
            if ($campaign->status !== 'active') {
                throw new \Exception('Promo code is not active');
            }

            // Check if expired
            if ($campaign->end_at && $campaign->end_at < now()) {
                throw new \Exception('Promo code has expired');
            }

            // Check start date
            if ($campaign->start_at > now()) {
                throw new \Exception('Promo code is not yet active');
            }

            // Check minimum order amount
            if ($orderAmount < ($campaign->min_order_amount ?? 0)) {
                throw new \Exception('Order amount is below minimum for this promo');
            }

            // Check applicable verticals
            $applicableVerticals = json_decode($campaign->applicable_verticals, true) ?? [];
            if (!empty($applicableVerticals) && !in_array($vertical, $applicableVerticals)) {
                throw new \Exception('This promo is not applicable to this vertical');
            }

            // Check per-user usage limit
            $userUsageCount = $this->db->table('promo_uses')
                ->where('promo_campaign_id', $campaign->id)
                ->where('user_id', $userId)
                ->count();

            if ($userUsageCount >= ($campaign->max_uses_per_user ?? PHP_INT_MAX)) {
                throw new \Exception('You have reached the maximum uses for this promo');
            }

            // Check total usage limit
            if (($campaign->used_count ?? 0) >= ($campaign->max_uses_total ?? PHP_INT_MAX)) {
                throw new \Exception('This promo code has reached its limit');
            }

            // Check budget
            if ($campaign->spent_budget >= $campaign->budget) {
                throw new \Exception('Promo code budget exhausted');
            }

            // Calculate discount
            $discountAmount = $this->calculateDiscount($campaign, $orderAmount);

            // Check if discount would exceed budget
            if ($campaign->spent_budget + $discountAmount > $campaign->budget) {
                throw new \Exception('Promo code has insufficient budget');
            }

            // Record usage
            $this->db->table('promo_uses')->insert([
                'promo_campaign_id' => $campaign->id,
                'tenant_id' => tenant()->id,
                'user_id' => $userId,
                'discount_amount' => $discountAmount,
                'correlation_id' => $correlationId,
                'used_at' => now(),
            ]);

            // Update campaign budget
            $this->db->table('promo_campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'spent_budget' => $campaign->spent_budget + $discountAmount,
                    'used_count' => ($campaign->used_count ?? 0) + 1,
                    'status' => $campaign->spent_budget + $discountAmount >= $campaign->budget ? 'exhausted' : 'active',
                ]);

            $this->log->channel('promo')->info('Promo applied', [
                'correlation_id' => $correlationId,
                'code' => $code,
                'user_id' => $userId,
                'order_amount' => $orderAmount,
                'discount_amount' => $discountAmount,
                'vertical' => $vertical,
            ]);

            return [
                'discount_amount' => $discountAmount,
                'new_amount' => max(0, $orderAmount - $discountAmount),
                'code' => $code,
                'applied_at' => now(),
            ];
        });
    }

    /**
     * Validate promo (preview discount without applying)
     *
     * @param string $code Promo code
     * @param int $orderAmount Order amount in kopeks
     * @param string $vertical Vertical
     * @return array {code, discount_amount, new_amount, valid}
     */
    public function validatePromo(string $code, int $orderAmount, string $vertical): array
    {
        $this->log->channel('audit')->info('Method validatePromo() called', [
            'correlation_id' => $correlationId ?? Str::uuid(),
        ]);


        $campaign = $this->db->table('promo_campaigns')
            ->where('code', strtoupper($code))
            ->where('tenant_id', tenant()->id)
            ->first();

        if (!$campaign) {
            return [
                'code' => $code,
                'valid' => false,
                'reason' => 'Code not found',
            ];
        }

        // Check if active
        if ($campaign->status !== 'active') {
            return [
                'code' => $code,
                'valid' => false,
                'reason' => 'Code is not active',
            ];
        }

        // Check if expired
        if ($campaign->end_at && $campaign->end_at < now()) {
            return [
                'code' => $code,
                'valid' => false,
                'reason' => 'Code has expired',
            ];
        }

        // Calculate discount
        $discountAmount = $this->calculateDiscount($campaign, $orderAmount);

        return [
            'code' => $code,
            'valid' => true,
            'discount_amount' => $discountAmount,
            'new_amount' => max(0, $orderAmount - $discountAmount),
            'type' => $campaign->type,
        ];
    }

    /**
     * Calculate discount amount based on promo type
     *
     * @param object $campaign Campaign object
     * @param int $orderAmount Order amount in kopeks
     * @return int Discount amount in kopeks
     */
    private function calculateDiscount(object $campaign, int $orderAmount): int
    {
        return match ($campaign->type) {
            'discount_percent' => (int) floor($orderAmount * $campaign->value / 100),
            'fixed_amount' => (int) min($campaign->value, $orderAmount),
            'buy_x_get_y' => 0,
            'referral_bonus' => (int) $campaign->value,
            'turnover_bonus' => (int) $campaign->value,
            default => 0,
        };
    }

    /**
     * Cancel promo use (refund discount to budget)
     *
     * @param int $useId Promo use ID
     * @param string $correlationId Tracing ID
     * @return bool
     */
    public function cancelPromoUse(int $useId, string $correlationId): bool
    {
        $this->log->channel('audit')->info('Method cancelPromoUse() called', [
            'correlation_id' => $correlationId ?? Str::uuid(),
        ]);


        return $this->db->transaction(function () use ($useId, $correlationId): bool {
            $use = $this->db->table('promo_uses')->findOrFail($useId);
            $campaign = $this->db->table('promo_campaigns')->findOrFail($use->promo_campaign_id);

            // Return discount to budget
            $this->db->table('promo_campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'spent_budget' => max(0, $campaign->spent_budget - $use->discount_amount),
                    'used_count' => max(0, ($campaign->used_count ?? 0) - 1),
                    'status' => 'active',
                ]);

            // Mark use as cancelled
            $this->db->table('promo_uses')
                ->where('id', $useId)
                ->update([
                    'cancelled_at' => now(),
                ]);

            $this->log->channel('promo')->info('Promo use cancelled', [
                'correlation_id' => $correlationId,
                'promo_use_id' => $useId,
                'discount_refunded' => $use->discount_amount,
            ]);

            return true;
        });
    }
}
