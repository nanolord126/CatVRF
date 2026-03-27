<?php declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\Referral;
use App\Models\ReferralReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ReferralService
{
    private const BUSINESS_REFERRAL_BONUS = 50000; // 500 руб
    private const CONSUMER_REFERRAL_BONUS = 100000; // 1000 руб
    private const CONSUMER_TURNOVER_THRESHOLD = 1000000; // 10000 руб

    public function generateReferralLink(int $referrerId, string $type = 'user'): string
    {
        $code = Str::upper(Str::random(8));
        $link = route('referral.register', ['code' => $code]);

        Log::channel('referral')->info('Referral link generated', [
            'referrer_id' => $referrerId,
            'type' => $type,
            'code' => $code,
        ]);

        return $link;
    }

    public function registerReferral(string $code, int $newUserId): bool
    {
        return DB::transaction(function () use ($code, $newUserId) {
            $referral = Referral::where('referral_code', $code)->first();

            if (!$referral) {
                return false;
            }

            $referral->update([
                'referee_id' => $newUserId,
                'status' => 'registered',
            ]);

            Log::channel('referral')->info('Referral registered', [
                'referral_id' => $referral->id,
                'referee_id' => $newUserId,
            ]);

            return true;
        });
    }

    public function checkQualification(int $referralId): array
    {
        $referral = Referral::findOrFail($referralId);

        $turnover = DB::table('orders')
            ->where('user_id', $referral->referee_id)
            ->sum('total_price');

        if ($turnover >= $referral->turnover_threshold) {
            return [
                'qualified' => true,
                'bonus_amount' => $referral->bonus_amount,
                'turnover' => $turnover,
            ];
        }

        return [
            'qualified' => false,
            'bonus_amount' => 0,
            'turnover' => $turnover,
            'remaining' => $referral->turnover_threshold - $turnover,
        ];
    }

    public function awardBonus(int $referralId, int $recipientId, string $correlationId = ''): bool
    {
        return DB::transaction(function () use ($referralId, $recipientId, $correlationId) {
            $referral = Referral::findOrFail($referralId);

            ReferralReward::create([
                'referral_id' => $referralId,
                'recipient_id' => $recipientId,
                'amount' => $referral->bonus_amount,
                'type' => 'referral_bonus',
                'status' => 'credited',
                'credited_at' => now(),
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
            ]);

            $referral->update(['status' => 'rewarded']);

            Log::channel('referral')->info('Bonus awarded', [
                'correlation_id' => $correlationId,
                'referral_id' => $referralId,
                'amount' => $referral->bonus_amount,
            ]);

            return true;
        });
    }

    public function getReferralStats(int $referrerId): array
    {
        $referrals = Referral::where('referrer_id', $referrerId)
            ->where('status', 'qualified')
            ->count();

        $totalTurnover = DB::table('orders')
            ->whereIn('user_id', function ($query) use ($referrerId) {
                $query->select('referee_id')
                    ->from('referrals')
                    ->where('referrer_id', $referrerId);
            })
            ->sum('total_price');

        $totalBonuses = ReferralReward::where('recipient_id', $referrerId)
            ->sum('amount');

        return [
            'qualified_referrals' => $referrals,
            'total_turnover' => $totalTurnover,
            'total_bonuses' => $totalBonuses,
        ];
    }
}
