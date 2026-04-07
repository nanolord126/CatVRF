<?php declare(strict_types=1);

namespace App\Services\ML;

use App\Models\UserTasteProfile;
use App\Models\UserTasteProfileHistory;


use Illuminate\Support\Str;
use Throwable;
use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class UserTasteProfileService
{
    public function __construct(
        private TasteMLService $mlService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

        /**
         * Получить или создать профиль для пользователя
         */
        public function getOrCreateProfile(int $userId, int $tenantId, ?string $correlationId = null): UserTasteProfile
        {
            try {
                $profile = UserTasteProfile::where('user_id', $userId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if (!$profile) {
                    $profile = $this->db->transaction(function () use ($userId, $tenantId, $correlationId) {
                        return UserTasteProfile::create([
                            'user_id' => $userId,
                            'tenant_id' => $tenantId,
                            'uuid' => Str::uuid(),
                            'version' => 1,
                            'correlation_id' => $correlationId ?? Str::uuid(),
                            'is_active' => true,
                            'allow_personalization' => true,
                            'explicit_preferences' => $this->getDefaultExplicitPreferences(),
                            'implicit_scores' => $this->getDefaultImplicitScores(),
                            'behavioral_metrics' => [],
                            'embeddings' => [],
                            'history' => [],
                            'metadata' => $this->getDefaultMetadata(),
                        ]);
                    });

                    $this->logger->channel('audit')->info('User taste profile created', [
                        'user_id' => $userId,
                        'tenant_id' => $tenantId,
                        'profile_id' => $profile->id,
                        'correlation_id' => $correlationId,
                    ]);
                }

                return $profile;
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Failed to get or create taste profile', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Записать явные предпочтения пользователя (размеры, стиль, диета и т.д.)
         */
        public function updateExplicitPreferences(
            int $userId,
            int $tenantId,
            array $preferences,
            ?string $correlationId = null
        ): UserTasteProfile {
            $this->fraud->check(new \stdClass());

            return $this->db->transaction(function () use ($userId, $tenantId, $preferences, $correlationId) {
                $profile = $this->getOrCreateProfile($userId, $tenantId, $correlationId);

                $oldExplicit = $profile->explicit_preferences ?? [];
                $newExplicit = array_merge($oldExplicit, $preferences);

                $profile->update([
                    'explicit_preferences' => $newExplicit,
                    'updated_at' => now(),
                ]);

                // Логировать изменения
                $this->recordHistoryChange(
                    $profile->id,
                    $userId,
                    $tenantId,
                    ['explicit_preferences' => ['old' => $oldExplicit, 'new' => $newExplicit]],
                    'explicit_update',
                    $correlationId
                );

                return $profile->refresh();
            });
        }

        /**
         * Обновить неявные ML-скоры категорий
         * Вызывается из MLRecalculateUserTastesJob
         */
        public function updateImplicitScores(
            int $userId,
            int $tenantId,
            array $categoryScores,
            array $behavioralMetrics,
            array $embeddings,
            ?string $correlationId = null
        ): UserTasteProfile {
            $this->fraud->check(new \stdClass());

            return $this->db->transaction(function () use (
                $userId,
                $tenantId,
                $categoryScores,
                $behavioralMetrics,
                $embeddings,
                $correlationId
            ) {
                $profile = $this->getOrCreateProfile($userId, $tenantId, $correlationId);

                $oldImplicit = $profile->implicit_scores ?? [];

                $newImplicit = [
                    'category_scores' => $categoryScores,
                    'behavioral' => $behavioralMetrics,
                ];

                $dataQualityScore = $this->calculateDataQualityScore($profile, $newImplicit);

                $metadata = $profile->metadata ?? [];
                $metadata['data_quality_score'] = $dataQualityScore;
                $metadata['ml_model_version'] = $this->mlService->getCurrentModelVersion();
                $metadata['total_interactions'] = ($metadata['total_interactions'] ?? 0) + 1;

                $profile->update([
                    'implicit_scores' => $newImplicit,
                    'embeddings' => $embeddings,
                    'calculated_at' => now(),
                    'metadata' => $metadata,
                    'version' => $profile->version + 1,
                ]);

                // Логировать обновление
                $this->recordHistoryChange(
                    $profile->id,
                    $userId,
                    $tenantId,
                    ['implicit_scores' => ['old' => $oldImplicit, 'new' => $newImplicit]],
                    'ml_recalculation',
                    $correlationId
                );

                return $profile->refresh();
            });
        }

        /**
         * Записать взаимодействие пользователя (просмотр, добавление в корзину, покупка)
         */
        public function recordInteraction(
            int $userId,
            int $tenantId,
            string $interactionType,
            array $details,
            ?string $correlationId = null
        ): void {
            try {
                $profile = $this->getOrCreateProfile($userId, $tenantId, $correlationId);

                $metadata = $profile->metadata ?? [];
                $metadata['last_interaction_at'] = now()->toIso8601String();
                $metadata['total_interactions'] = ($metadata['total_interactions'] ?? 0) + 1;

                // Добавить в history
                $history = $profile->history ?? [];
                $todayKey = now()->toDateString();

                if (!isset($history[$todayKey])) {
                    $history[$todayKey] = [
                        'date' => $todayKey,
                        'actions' => 0,
                        'purchases' => 0,
                    ];
                }

                $history[$todayKey]['actions']++;

                if ($interactionType === 'purchase') {
                    $history[$todayKey]['purchases']++;
                }

                $profile->update([
                    'metadata' => $metadata,
                    'history' => $history,
                    'last_interaction_at' => now(),
                ]);

                $this->logger->channel('audit')->info('User taste interaction recorded', [
                    'user_id' => $userId,
                    'interaction_type' => $interactionType,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Failed to record taste interaction', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        /**
         * Отключить/включить персональные рекомендации
         */
        public function setPersonalizationEnabled(int $userId, int $tenantId, bool $enabled): UserTasteProfile
        {
            $profile = $this->getOrCreateProfile($userId, $tenantId);

            $profile->update(['allow_personalization' => $enabled]);

            $this->logger->channel('audit')->info('Personalization toggled', [
                'user_id' => $userId,
                'enabled' => $enabled,
            ]);

            return $profile->refresh();
        }

        /**
         * Вычислить data quality score (0–1)
         */
        private function calculateDataQualityScore(UserTasteProfile $profile, array $implicitScores): float
        {
            $factors = [];

            // Фактор 1: Количество взаимодействий
            $totalInteractions = $profile->getTotalInteractions();
            $factors['interactions'] = min(1.0, $totalInteractions / 100);

            // Фактор 2: Наличие явных предпочтений
            $explicit = $profile->explicit_preferences ?? [];
            $explicitFields = count(array_filter($explicit));
            $factors['explicit'] = count($explicit) > 0 ? min(1.0, $explicitFields / 10) : 0.0;

            // Фактор 3: Качество embeddings
            $hasEmbeddings = !empty($implicitScores['behavioral'] ?? []);
            $factors['embeddings'] = $hasEmbeddings ? 0.8 : 0.4;

            // Фактор 4: Разнообразие категорий
            $categoryScores = $implicitScores['category_scores'] ?? [];
            $factors['diversity'] = count($categoryScores) > 0
                ? min(1.0, count(array_filter($categoryScores, fn ($s) => $s > 0.3)) / 10)
                : 0.0;

            // Средневзвешенная оценка
            $weights = [
                'interactions' => 0.3,
                'explicit' => 0.2,
                'embeddings' => 0.25,
                'diversity' => 0.25,
            ];

            $quality = 0.0;
            foreach ($weights as $factor => $weight) {
                $quality += ($factors[$factor] ?? 0.0) * $weight;
            }

            return min(1.0, $quality);
        }

        /**
         * Записать историческое изменение профиля
         */
        private function recordHistoryChange(
            int $profileId,
            int $userId,
            int $tenantId,
            array $changes,
            string $reason,
            ?string $correlationId = null
        ): void {
            UserTasteProfileHistory::create([
                'taste_profile_id' => $profileId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'version' => UserTasteProfile::findOrFail($profileId)->version,
                'changes' => $changes,
                'trigger_reason' => $reason,
                'interaction_count' => 0,
                'purchase_count' => 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);
        }

        /**
         * Стандартные явные предпочтения (пусто)
         */
        private function getDefaultExplicitPreferences(): array
        {
            return [
                'sizes' => [],
                'body_metrics' => [],
                'dietary' => [
                    'type' => [],
                    'allergies' => [],
                    'avoid' => [],
                    'preferred_cuisines' => [],
                ],
                'style_preferences' => [
                    'fashion' => [],
                    'colors' => [],
                    'brands' => [],
                ],
                'lifestyle' => [
                    'activity_level' => null,
                    'interests' => [],
                    'values' => [],
                ],
            ];
        }

        /**
         * Стандартные неявные скоры (нули)
         */
        private function getDefaultImplicitScores(): array
        {
            return [
                'category_scores' => [],
                'behavioral' => [
                    'avg_session_duration_sec' => 0,
                    'purchase_frequency_days' => 0,
                    'avg_cart_value' => 0,
                    'price_sensitivity' => 0.5,
                    'brand_loyalty_score' => 0.0,
                ],
            ];
        }

        /**
         * Стандартные метаданные
         */
        private function getDefaultMetadata(): array
        {
            return [
                'data_quality_score' => 0.0,
                'total_interactions' => 0,
                'ml_model_version' => 'taste-v2.0',
                'recommendation_influence' => 0.3,
                'created_at' => now()->toIso8601String(),
            ];
        }
}
