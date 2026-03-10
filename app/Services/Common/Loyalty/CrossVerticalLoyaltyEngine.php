<?php

namespace App\Services\Common\Loyalty;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrossVerticalLoyaltyEngine
{
    /**
     * Calculate and Reward Loyalty Points (V-Coins) after a transaction across any vertical.
     */
    public function earnVCoins(User $user, string $vertical, float $transactionAmount, array $metadata = []): void
    {
        $rule = DB::table('loyalty_rules')
            ->where('vertical', $vertical)
            ->where('is_active', true)
            ->first();

        // Default: 5% back if no rule set
        $earnRate = $rule ? (float)$rule->earn_rate : 0.05;
        
        $wallet = DB::table('ecosystem_loyalty_wallets')
            ->where('user_id', $user->id)
            ->first();

        if (!$wallet) {
            $this->initLoyaltyWallet($user);
            $wallet = DB::table('ecosystem_loyalty_wallets')->where('user_id', $user->id)->first();
        }

        // Apply bonus multiplier for higher tiers (Gold, Platinum)
        $amountToEarn = ($transactionAmount * $earnRate) * $wallet->multiplier;

        DB::transaction(function () use ($user, $amountToEarn, $vertical, $metadata) {
            // Update wallet balance
            DB::table('ecosystem_loyalty_wallets')
                ->where('user_id', $user->id)
                ->increment('balance', $amountToEarn);

            // Log Transaction (Traceable)
            DB::table('loyalty_transactions')->insert([
                'user_id' => $user->id,
                'amount' => $amountToEarn,
                'vertical' => $vertical,
                'type' => 'earn',
                'reason' => "Ecosystem Reward: " . ucfirst($vertical),
                'correlation_id' => $metadata['correlation_id'] ?? (string) Str::uuid(),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Use V-Coins to pay for a service (Redemption).
     * @return array { success: bool, discount_amount: float }
     */
    public function redeemVCoins(User $user, string $vertical, float $servicePrice, ?float $coinsToRedeem = null): array
    {
        $wallet = DB::table('ecosystem_loyalty_wallets')
            ->where('user_id', $user->id)
            ->first();

        if (!$wallet || $wallet->balance <= 0) {
            return ['success' => false, 'discount_amount' => 0];
        }

        $rule = DB::table('loyalty_rules')
            ->where('vertical', $vertical)
            ->where('is_active', true)
            ->first();

        // Limit redemption based on vertical rules (e.g., max 30% of price in Taxi)
        $maxRedemptionPct = $rule ? (float)$rule->redeem_limit : 0.50; // default 50%
        $maxPriceInCoins = $servicePrice * $maxRedemptionPct;

        $toRedeem = $coinsToRedeem ?? min($wallet->balance, $maxPriceInCoins);

        if ($toRedeem > $wallet->balance) return ['success' => false];

        DB::transaction(function () use ($user, $toRedeem, $vertical) {
            // Subtract balance
            DB::table('ecosystem_loyalty_wallets')
                ->where('user_id', $user->id)
                ->decrement('balance', $toRedeem);

            // Log Transaction
            DB::table('loyalty_transactions')->insert([
                'user_id' => $user->id,
                'amount' => - $toRedeem,
                'vertical' => $vertical,
                'type' => 'redeem',
                'reason' => "Redemption for " . ucfirst($vertical),
                'correlation_id' => (string) Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return [
            'success' => true,
            'discount_amount' => $toRedeem
        ];
    }

    private function initLoyaltyWallet(User $user): void
    {
        DB::table('ecosystem_loyalty_wallets')->insert([
            'user_id' => $user->id,
            'balance' => 0,
            'multiplier' => 1.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
