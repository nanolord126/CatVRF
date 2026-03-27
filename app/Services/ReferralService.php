<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Referral Program Service
 * Production 2026 CANON
 *
 * Manages referral links and bonuses
 * - Link generation
 * - Registration tracking
 * - Qualification checking (5000₽ minimum spending)
 * - Bonus crediting
 * - Migration source tracking
 *
 * @author CatVRF Team
 * @version 2026.03.24
 */
final class ReferralService
{
    private const QUALIFICATION_THRESHOLD = 500000; // 5000₽ in kopeks
    private const REFERRER_BONUS = 20000; // 200₽ in kopeks
    private const MIGRATION_BENEFITS = [
        'dikidi' => ['rate' => 10, 'months' => 4],
        'booking' => ['rate' => 12, 'months' => 24],
        'ostrovok' => ['rate' => 12, 'months' => 24],
        'yandex_eats' => ['rate' => 12, 'months' => 24],
        'flowwow' => ['rate' => 10, 'months' => 4],
    ];

    /**
     * Generate referral link
     *
     * @param int $referrerId User ID of referrer
     * @param string $correlationId Tracing ID
     * @return array {code, link, created_at}
     * @throws \Exception
     */
    public function generateReferralLink(int $referrerId, string $correlationId): array
    {
        Log::channel('audit')->info('Method generateReferralLink() called', [
            'correlation_id' => $correlationId ?? Str::uuid(),
        ]);


        return DB::transaction(function () use ($referrerId, $correlationId): array {
            // Generate unique code
            $code = $this->generateUniqueCode();

            // Create referral record
            $referral = DB::table('referrals')->insertGetId([
                'referrer_id' => $referrerId,
                'referral_code' => $code,
                'referral_link' => route('referral.register', ['code' => $code]),
                'status' => 'pending',
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $link = route('referral.register', ['code' => $code]);

            Log::channel('referral')->info('Referral link generated', [
                'correlation_id' => $correlationId,
                'referrer_id' => $referrerId,
                'code' => $code,
                'link' => $link,
            ]);

            return [
                'code' => $code,
                'link' => $link,
                'created_at' => now(),
            ];
        });
    }

    /**
     * Register new user with referral code
     *
     * @param string $code Referral code
     * @param int $newUserId New user ID
     * @param string|null $sourcePlatform Migration source (dikidi, booking, etc.)
     * @param string $correlationId Tracing ID
     * @return array {referral_id, status, referrer_id}
     * @throws \Exception
     */
    public function registerReferral(string $code, int $newUserId, ?string $sourcePlatform, string $correlationId): array
    {
        $this->fraudControl->check([
            'operation' => referral_register,
            'referrer_id' => $referrerId,
            'referee_id' => $newUserId,
            'correlation_id' => $correlationId,
        ]);


        Log::channel('audit')->info('Method registerReferral() called', [
            'correlation_id' => $correlationId ?? Str::uuid(),
        ]);


        return DB::transaction(function () use ($code, $newUserId, $sourcePlatform, $correlationId): array {
            // Find referral
            $referral = DB::table('referrals')
                ->where('referral_code', $code)
                ->where('tenant_id', tenant()->id)
                ->lockForUpdate()
                ->first();

            if (!$referral) {
                throw new \Exception('Referral code not found');
            }

            // Check if already used
            if ($referral->referee_id) {
                throw new \Exception('Referral code already used');
            }

            // Check self-referral
            if ($referral->referrer_id === $newUserId) {
                throw new \Exception('Cannot use your own referral code');
            }

            // Update referral
            DB::table('referrals')
                ->where('id', $referral->id)
                ->update([
                    'referee_id' => $newUserId,
                    'status' => 'registered',
                    'source_platform' => $sourcePlatform,
                    'migrated_at' => now(),
                    'correlation_id' => $correlationId,
                    'updated_at' => now(),
                ]);

            Log::channel('referral')->info('Referral registered', [
                'correlation_id' => $correlationId,
                'referral_id' => $referral->id,
                'referrer_id' => $referral->referrer_id,
                'referee_id' => $newUserId,
                'source_platform' => $sourcePlatform,
            ]);

            return [
                'referral_id' => $referral->id,
                'status' => 'registered',
                'referrer_id' => $referral->referrer_id,
            ];
        });
    }

    /**
     * Check if referral qualifies and award bonus
     *
     * @param int $referralId Referral ID
     * @param string $correlationId Tracing ID
     * @return array {qualified: bool, bonus_amount, qualified_at}
     * @throws \Exception
     */
    public function checkQualification(int $referralId, string $correlationId): array
    {
        Log::channel('audit')->info('Method checkQualification() called', [
            'correlation_id' => $correlationId ?? Str::uuid(),
        ]);


        return DB::transaction(function () use ($referralId, $correlationId): array {
            $referral = DB::table('referrals')
                ->where('id', $referralId)
                ->lockForUpdate()
                ->first();

            if (!$referral || !$referral->referee_id) {
                throw new \Exception('Referral not found or not registered');
            }

            // Calculate referee spending
            $spending = DB::table('balance_transactions')
                ->where('user_id', $referral->referee_id)
                ->where('type', 'debit')
                ->where('created_at', '>=', $referral->migrated_at ?? $referral->created_at)
                ->sum('amount');

            if ($spending < self::QUALIFICATION_THRESHOLD) {
                return [
                    'qualified' => false,
                    'spending' => $spending,
                    'threshold' => self::QUALIFICATION_THRESHOLD,
                    'remaining' => self::QUALIFICATION_THRESHOLD - $spending,
                ];
            }

            // Already qualified?
            if ($referral->status === 'rewarded') {
                return [
                    'qualified' => true,
                    'bonus_already_awarded' => true,
                ];
            }

            // Award bonus
            $walletService = app(WalletService::class);
            $walletService->credit(
                $referral->referrer_id, // referrer's wallet
                self::REFERRER_BONUS,
                'Referral bonus for ' . $referral->referee_id,
                $correlationId
            );

            // Create reward record
            DB::table('referral_rewards')->insert([
                'referral_id' => $referral->id,
                'recipient_type' => 'referrer',
                'recipient_id' => $referral->referrer_id,
                'amount' => self::REFERRER_BONUS,
                'type' => 'referral_bonus',
                'status' => 'credited',
                'credited_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            // Update referral status
            DB::table('referrals')
                ->where('id', $referralId)
                ->update([
                    'status' => 'rewarded',
                    'bonus_amount' => self::REFERRER_BONUS,
                    'updated_at' => now(),
                ]);

            Log::channel('referral')->info('Referral qualified and rewarded', [
                'correlation_id' => $correlationId,
                'referral_id' => $referralId,
                'referrer_id' => $referral->referrer_id,
                'referee_id' => $referral->referee_id,
                'bonus_amount' => self::REFERRER_BONUS,
                'spending' => $spending,
            ]);

            return [
                'qualified' => true,
                'bonus_amount' => self::REFERRER_BONUS,
                'qualified_at' => now(),
            ];
        });
    }

    /**
     * Get referral statistics
     *
     * @param int $referrerId User ID
     * @return array {total_referrals, qualified, pending, earnings, total_earning}
     */
    public function getReferralStats(int $referrerId): array
    {
        $total = DB::table('referrals')
            ->where('referrer_id', $referrerId)
            ->count();

        $qualified = DB::table('referrals')
            ->where('referrer_id', $referrerId)
            ->where('status', 'rewarded')
            ->count();

        $pending = DB::table('referrals')
            ->where('referrer_id', $referrerId)
            ->where('status', 'registered')
            ->count();

        $earnings = DB::table('referral_rewards')
            ->where('recipient_id', $referrerId)
            ->where('type', 'referral_bonus')
            ->sum('amount');

        return [
            'total_referrals' => $total,
            'qualified' => $qualified,
            'pending' => $pending,
            'earnings' => $earnings ?? 0,
        ];
    }

    /**
     * Generate unique referral code
     *
     * @return string 8-character code
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (DB::table('referrals')->where('referral_code', $code)->exists());

        return $code;
    }
}
