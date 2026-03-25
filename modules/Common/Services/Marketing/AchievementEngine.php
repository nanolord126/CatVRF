<?php

namespace App\Domains\Common\Services\Marketing;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class AchievementEngine
{
    protected array $achievements = [
        'first_ad_campaign' => [
            'name' => 'Первый шаг к успеху',
            'points' => 500,
            'description' => 'Запуск первой рекламной кампании (минимум 10k показов).',
        ],
        'eco_pioneer' => [
            'name' => 'Эко-пионер',
            'points' => 300,
            'description' => 'Успешная сдача отчетности в ЕРИР (ОРД) без единой ошибки.',
        ],
        'loyal_merchant' => [
            'name' => 'Преданный партнер',
            'points' => 1000,
            'description' => 'Активность в системе на протяжении 90 дней подряд.',
        ],
        'merch_owner' => [
            'name' => 'Адепт платформы',
            'points' => 200,
            'description' => 'Получение первого лимитированного мерча (Platinum Badge).',
        ],
    ];

    private string $correlationId;
    private ?string $tenantId;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
        $this->tenantId = $this->auth->guard('tenant')?->id();
    }

    /**
     * Присуждение ачивмента.
     */
    public function grantAchievement(User $user, string $achievementKey): bool
    {
        $this->correlationId = Str::uuid();

        try {
            if (!isset($this->achievements[$achievementKey])) {
                $this->log->warning('AchievementEngine: unknown achievement', [
                    'correlation_id' => $this->correlationId,
                    'achievement_key' => $achievementKey,
                    'user_id' => $user->id,
                ]);
                return false;
            }

            $id = "achievement_{$user->id}_{$achievementKey}";

            // Проверка, не получен ли уже этот ачивмент
            if ($this->cache->has($id)) {
                $this->log->info('AchievementEngine: achievement already granted', [
                    'correlation_id' => $this->correlationId,
                    'achievement_key' => $achievementKey,
                    'user_id' => $user->id,
                ]);
                return false;
            }

            $this->log->channel('marketing')->info('AchievementEngine: granting achievement', [
                'correlation_id' => $this->correlationId,
                'achievement_key' => $achievementKey,
                'user_id' => $user->id,
                'points' => $this->achievements[$achievementKey]['points'],
            ]);

            // Начисление бонусных баллов
            $points = $this->achievements[$achievementKey]['points'];
            $user->deposit($points, [
                'type' => 'achievement_reward',
                'achievement' => $achievementKey,
                'id' => $id,
                'correlation_id' => $this->correlationId,
            ], 'loyalty_points');

            // Отметить в кэше на год
            $this->cache->put($id, now()->toIso8601String(), 365 * 24 * 3600);

            // Логирование в audit trail
            Audit$this->log->create([
                'entity_type' => 'Achievement',
                'entity_id' => $achievementKey,
                'action' => 'granted',
                'user_id' => $this->auth->id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'user_id' => $user->id,
                    'achievement_name' => $this->achievements[$achievementKey]['name'],
                    'points_awarded' => $points,
                    'achievement_key' => $achievementKey,
                ],
            ]);

            $this->log->channel('marketing')->info('AchievementEngine: achievement granted successfully', [
                'correlation_id' => $this->correlationId,
                'achievement_key' => $achievementKey,
                'user_id' => $user->id,
                'points_awarded' => $points,
            ]);

            return true;
        } catch (Throwable $e) {
            $this->log->error('AchievementEngine: achievement grant failed', [
                'correlation_id' => $this->correlationId,
                'achievement_key' => $achievementKey,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            return false;
        }
    }

    /**
     * Получить список достигнутых ачивментов пользователя.
     */
    public function getUserAchievements(User $user): array
    {
        try {
            $userAchievements = [];
            foreach ($this->achievements as $key => $achievement) {
                $cacheKey = "achievement_{$user->id}_{$key}";
                if ($this->cache->has($cacheKey)) {
                    $userAchievements[$key] = array_merge($achievement, [
                        'granted_at' => $this->cache->get($cacheKey),
                    ]);
                }
            }
            return $userAchievements;
        } catch (Throwable $e) {
            $this->log->error('AchievementEngine: get achievements failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
