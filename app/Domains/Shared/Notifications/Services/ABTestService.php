<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Services;

use App\Domains\Shared\Notifications\Models\NotificationExperiment;
use App\Domains\Shared\Notifications\Models\NotificationExperimentLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final readonly class ABTestService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function getVariant(string $eventType, User $user): array
    {
        $cacheKey = "ab_test:{$eventType}:{$user->id}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $experiment = NotificationExperiment::active()
            ->where('event_type', $eventType)
            ->first();

        if (!$experiment) {
            $default = $this->getDefaultTemplate($eventType);
            Cache::put($cacheKey, $default, self::CACHE_TTL);
            return $default;
        }

        $variant = $this->selectVariant($user, $experiment);
        $template = $variant === 'A' ? $experiment->variant_a : $experiment->variant_b;

        $this->logExperimentParticipation($experiment, $user, $variant, $eventType);

        Cache::put($cacheKey, $template, self::CACHE_TTL);

        return $template;
    }

    public function trackOpen(int $experimentId, int $userId): void
    {
        $log = NotificationExperimentLog::where('experiment_id', $experimentId)
            ->where('user_id', $userId)
            ->first();

        if ($log && !$log->opened_at) {
            $log->update(['opened_at' => now()]);
            Log::info('AB Test: notification opened', [
                'experiment_id' => $experimentId,
                'user_id' => $userId,
                'variant' => $log->variant
            ]);
        }
    }

    public function trackClick(int $experimentId, int $userId): void
    {
        $log = NotificationExperimentLog::where('experiment_id', $experimentId)
            ->where('user_id', $userId)
            ->first();

        if ($log && !$log->clicked_at) {
            $log->update(['clicked_at' => now()]);
            Log::info('AB Test: notification clicked', [
                'experiment_id' => $experimentId,
                'user_id' => $userId,
                'variant' => $log->variant
            ]);
        }
    }

    public function trackConversion(int $experimentId, int $userId): void
    {
        $log = NotificationExperimentLog::where('experiment_id', $experimentId)
            ->where('user_id', $userId)
            ->first();

        if ($log && !$log->converted_at) {
            $log->update(['converted_at' => now()]);
            Log::info('AB Test: conversion tracked', [
                'experiment_id' => $experimentId,
                'user_id' => $userId,
                'variant' => $log->variant
            ]);
        }
    }

    public function getExperimentStats(int $experimentId): array
    {
        $experiment = NotificationExperiment::findOrFail($experimentId);

        $logs = $experiment->logs;

        $variantALogs = $logs->where('variant', 'A');
        $variantBLogs = $logs->where('variant', 'B');

        return [
            'experiment_id' => $experimentId,
            'name' => $experiment->name,
            'event_type' => $experiment->event_type,
            'traffic_percent' => $experiment->traffic_percent,
            'total_participants' => $logs->count(),
            'variant_a' => [
                'participants' => $variantALogs->count(),
                'opened' => $variantALogs->opened()->count(),
                'clicked' => $variantALogs->clicked()->count(),
                'converted' => $variantALogs->converted()->count(),
                'open_rate' => $variantALogs->count() > 0 
                    ? round(($variantALogs->opened()->count() / $variantALogs->count()) * 100, 2) 
                    : 0,
                'click_rate' => $variantALogs->count() > 0 
                    ? round(($variantALogs->clicked()->count() / $variantALogs->count()) * 100, 2) 
                    : 0,
                'conversion_rate' => $variantALogs->count() > 0 
                    ? round(($variantALogs->converted()->count() / $variantALogs->count()) * 100, 2) 
                    : 0,
            ],
            'variant_b' => [
                'participants' => $variantBLogs->count(),
                'opened' => $variantBLogs->opened()->count(),
                'clicked' => $variantBLogs->clicked()->count(),
                'converted' => $variantBLogs->converted()->count(),
                'open_rate' => $variantBLogs->count() > 0 
                    ? round(($variantBLogs->opened()->count() / $variantBLogs->count()) * 100, 2) 
                    : 0,
                'click_rate' => $variantBLogs->count() > 0 
                    ? round(($variantBLogs->clicked()->count() / $variantBLogs->count()) * 100, 2) 
                    : 0,
                'conversion_rate' => $variantBLogs->count() > 0 
                    ? round(($variantBLogs->converted()->count() / $variantBLogs->count()) * 100, 2) 
                    : 0,
            ],
        ];
    }

    private function selectVariant(User $user, NotificationExperiment $experiment): string
    {
        $hash = crc32((string) $user->id . $experiment->event_type) % 100;
        return $hash < $experiment->traffic_percent ? 'A' : 'B';
    }

    private function logExperimentParticipation(NotificationExperiment $experiment, User $user, string $variant, string $eventType): void
    {
        try {
            NotificationExperimentLog::create([
                'experiment_id' => $experiment->id,
                'user_id' => $user->id,
                'variant' => $variant,
                'event_type' => $eventType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log AB test participation', [
                'experiment_id' => $experiment->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getDefaultTemplate(string $eventType): array
    {
        return match($eventType) {
            'pre_delivery_reminder' => [
                'text' => "📦 Напоминание: завтра доставка по подписке!",
                'buttons' => ['track', 'pause']
            ],
            'subscription_created' => [
                'text' => "✅ Ваша подписка успешно оформлена!",
                'buttons' => ['track', 'manage']
            ],
            'payment_failed' => [
                'text' => "❌ Проблема с оплатой подписки. Пожалуйста, обновите способ оплаты.",
                'buttons' => ['update_payment']
            ],
            default => [
                'text' => 'Обновление по подписке',
                'buttons' => []
            ]
        };
    }
}
