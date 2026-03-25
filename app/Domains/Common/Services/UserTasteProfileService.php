<?php

declare(strict_types=1);

namespace App\Domains\Common\Services;

use App\Models\User;
use App\Models\UserTasteProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CANON 2026: User Taste ML Analysis - Main Service
 * Управляет профилями вкусов пользователей, embeddings, рекомендациями
 * На основе явных и неявных данных о предпочтениях
 */
final readonly class UserTasteProfileService
{
    public function __construct(
        private TasteMLService $mlService,
    ) {}

    /**
     * Получить или создать профиль вкусов пользователя
     */
    public function getOrCreateProfile(int $userId, int $tenantId): UserTasteProfile
    {
        try {
            return UserTasteProfile::where([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ])->firstOrCreate(
                [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                ],
                [
                    'uuid' => Str::uuid(),
                    'embedding' => null,
                    'explicit_preferences' => [],
                    'implicit_score' => [],
                    'is_enabled' => true,
                    'opt_out' => false,
                ]
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to get or create taste profile', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Обновить профиль после взаимодействия пользователя
     */
    public function updateProfileFromInteraction(
        int $userId,
        int $tenantId,
        string $interactionType,
        array $data,
        string $correlationId = '',
    ): void
    {
        try {
            $profile = $this->getOrCreateProfile($userId, $tenantId);

            // Обновить timestamp последнего взаимодействия
            $profile->update([
                'last_interaction_at' => now(),
                'interaction_count' => ($profile->interaction_count ?? 0) + 1,
            ]);

            Log::channel('audit')->info('User taste profile updated', [
                'user_id' => $userId,
                'interaction_type' => $interactionType,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update taste profile from interaction', [
                'user_id' => $userId,
                'interaction_type' => $interactionType,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    /**
     * Получить явные предпочтения пользователя
     */
    public function getExplicitPreferences(int $userId, int $tenantId): array
    {
        $cacheKey = "taste:explicit:{$tenantId}:{$userId}";

        return Cache::remember($cacheKey, 86400, function () use ($userId, $tenantId) {
            $profile = UserTasteProfile::where([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ])->first();

            return $profile?->explicit_preferences ?? [];
        });
    }

    /**
     * Получить неявные предпочтения (категории с весами)
     */
    public function getImplicitScores(int $userId, int $tenantId): array
    {
        $cacheKey = "taste:implicit:{$tenantId}:{$userId}";

        return Cache::remember($cacheKey, 86400, function () use ($userId, $tenantId) {
            $profile = UserTasteProfile::where([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ])->first();

            return $profile?->implicit_score ?? [];
        });
    }

    /**
     * Установить размеры пользователя (явные данные)
     */
    public function setSizeProfile(
        int $userId,
        int $tenantId,
        array $sizes,
        string $correlationId = '',
    ): void
    {
        try {
            DB::transaction(function () use ($userId, $tenantId, $sizes, $correlationId) {
                $profile = $this->getOrCreateProfile($userId, $tenantId);

                $profile->update([
                    'size_profile' => $sizes,
                    'correlation_id' => $correlationId,
                ]);

                $this->invalidateCache($userId, $tenantId);

                Log::channel('audit')->info('User size profile set', [
                    'user_id' => $userId,
                    'sizes' => array_keys($sizes),
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to set size profile', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Установить предпочтения пользователя (явные данные)
     */
    public function setExplicitPreferences(
        int $userId,
        int $tenantId,
        array $preferences,
        string $correlationId = '',
    ): void
    {
        try {
            DB::transaction(function () use ($userId, $tenantId, $preferences, $correlationId) {
                $profile = $this->getOrCreateProfile($userId, $tenantId);

                $profile->update([
                    'explicit_preferences' => $preferences,
                    'correlation_id' => $correlationId,
                ]);

                $this->invalidateCache($userId, $tenantId);

                Log::channel('audit')->info('User explicit preferences set', [
                    'user_id' => $userId,
                    'preferences_keys' => array_keys($preferences),
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to set explicit preferences', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Отключить персональные рекомендации (но анализ продолжается)
     */
    public function disablePersonalization(int $userId, int $tenantId): void
    {
        try {
            $profile = $this->getOrCreateProfile($userId, $tenantId);
            $profile->update(['opt_out' => true]);

            $this->invalidateCache($userId, $tenantId);

            Log::channel('audit')->info('Personalization disabled for user', [
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to disable personalization', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Включить персональные рекомендации
     */
    public function enablePersonalization(int $userId, int $tenantId): void
    {
        try {
            $profile = $this->getOrCreateProfile($userId, $tenantId);
            $profile->update(['opt_out' => false]);

            $this->invalidateCache($userId, $tenantId);

            Log::channel('audit')->info('Personalization enabled for user', [
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to enable personalization', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Проверить, включены ли рекомендации для пользователя
     */
    public function isPersonalizationEnabled(int $userId, int $tenantId): bool
    {
        $profile = UserTasteProfile::where([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ])->first();

        return $profile && !$profile->opt_out && $profile->is_enabled;
    }

    /**
     * Инвалидировать кэш профиля пользователя
     */
    public function invalidateCache(int $userId, int $tenantId): void
    {
        Cache::forget("taste:explicit:{$tenantId}:{$userId}");
        Cache::forget("taste:implicit:{$tenantId}:{$userId}");
        Cache::forget("taste:profile:{$tenantId}:{$userId}");
    }

    /**
     * Получить статистику профиля (для дашборда)
     */
    public function getProfileStats(int $userId, int $tenantId): array
    {
        $profile = UserTasteProfile::where([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ])->first();

        if (!$profile) {
            return [
                'interaction_count' => 0,
                'ctr' => 0,
                'version' => 0,
                'last_calculated_at' => null,
            ];
        }

        return [
            'interaction_count' => $profile->interaction_count,
            'ctr' => $profile->ctr,
            'recommendation_acceptance_rate' => $profile->recommendation_acceptance_rate,
            'version' => $profile->version,
            'last_calculated_at' => $profile->last_calculated_at?->toIso8601String(),
            'opt_out' => $profile->opt_out,
            'is_enabled' => $profile->is_enabled,
        ];
    }
}
