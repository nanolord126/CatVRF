<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\DB;

class CalculateRFMSegments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Пересчет RFM сегментов для всех пользователей тенанта.
     * RFM: Recency (Давность), Frequency (Частота), Monetary (Деньги)
     */
    public function handle()
    {
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $lastPurchase = DB::table('behavioral_events')
                ->where('user_id', $user->id)
                ->where('action', 'PURCHASE')
                ->latest('occurred_at')
                ->first();

            $totalSpend = DB::table('behavioral_events')
                ->where('user_id', $user->id)
                ->where('action', 'PURCHASE')
                ->sum('monetary_value');

            $purchaseCount = DB::table('behavioral_events')
                ->where('user_id', $user->id)
                ->where('action', 'PURCHASE')
                ->count();

            // 1. Recency Score (1-5): Чем меньше дней назад была покупка - тем выше балл
            $recencyDays = $lastPurchase ? now()->diffInDays(Carbon::parse($lastPurchase->occurred_at)) : 365;
            $recencyScore = $this->calculateScore($recencyDays, [3, 7, 30, 90], true);

            // 2. Frequency Score (1-5): Чем чаще покупки - тем выше балл
            $frequencyScore = $this->calculateScore($purchaseCount, [2, 5, 10, 20], false);

            // 3. Monetary Score (1-5): Чем выше общая выручка - тем выше балл
            $monetaryScore = $this->calculateScore($totalSpend, [1000, 5000, 20000, 100000], false);

            // 4. Определение сегмента
            $segment = $this->determineSegment($recencyScore, $frequencyScore, $monetaryScore);

            Facades\DB::table('user_segments')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'rfm_segment' => $segment,
                    'recency_score' => $recencyScore,
                    'frequency_score' => $frequencyScore,
                    'monetary_score' => $monetaryScore,
                    'last_calculated_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }

    private function calculateScore($value, $thresholds, $isInverse)
    {
        $score = 1;
        foreach ($thresholds as $t) {
            if ($isInverse) {
                if ($value <= $t) $score++;
            } else {
                if ($value >= $t) $score++;
            }
        }
        return min($score, 5);
    }

    private function determineSegment($r, $f, $m)
    {
        $total = $r + $f + $m;
        if ($total >= 13) return 'VIP';
        if ($total >= 10) return 'LOYAL';
        if ($r == 1) return 'AT_RISK / CHURNING';
        if ($total >= 7) return 'PROMISING';
        return 'NEW / IDLE';
    }
}
