<?php

namespace App\Services\Marketing;

use Illuminate\Support\{Carbon, Facades, Str};
use App\Models\AuditLog;

class BehavioralTracker
{
    /**
     * Захват поведенческого события (View, Search, Add to Cart).
     * Канон: Использование correlation_id и асинхронное сохранение (имитируем).
     */
    public function recordEvent(int $userId, string $vertical, string $action, float $monetaryValue = 0, array $metadata = [])
    {
        $correlationId = (string) Str::uuid();

        // 1. Сохранение события для BigData аналитики
        DB::table('behavioral_events')->insert([
            'user_id' => $userId,
            'vertical' => $vertical,
            'action' => $action,
            'monetary_value' => $monetaryValue,
            'metadata' => json_encode($metadata),
            'correlation_id' => $correlationId,
            'occurred_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 2. Логирование аномалий (фрод-контроль на основе активности)
        if ($action === 'VIEW' && $this->isSpamming($userId)) {
            $this->triggerSecurityAlert($userId, "User is suspiciously browsing through {$vertical} module.");
        }

        // 3. Контекстный триггер Кросс-сейла: Если после брони отеля
        if ($vertical === 'HOTELS' && $action === 'PURCHASE') {
             $this->triggerCrossSell($userId, 'FLOWERS', 'Special Room Decoration Offer');
        }

        return $correlationId;
    }

    private function isSpamming(int $userId): bool
    {
        return Facades\DB::table('behavioral_events')
            ->where('user_id', $userId)
            ->where('occurred_at', '>=', Carbon::now()->subMinutes(5))
            ->count() > 100;
    }

    private function triggerSecurityAlert(int $userId, string $msg)
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'SUSPICIOUS_BEHAVIOR_DETECTED',
            'description' => $msg,
            'correlation_id' => Str::random(10),
        ]);
    }

    private function triggerCrossSell(int $userId, string $targetVertical, string $offer)
    {
        // Канон: Создаем отложенный Job для отправки оффера через 10 минут после покупки основного сервиса
        // \App\Jobs\Marketing\SendTimedOfferJob::dispatch($userId, $targetVertical, $offer)->delay(now()->addMinutes(10));
    }
}
