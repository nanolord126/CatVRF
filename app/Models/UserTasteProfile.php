<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UserTasteProfile extends Model
{
    use HasFactory;

        protected $table = 'user_taste_profiles';

        protected $fillable = [
            'user_id',
            'tenant_id',
            'uuid',
            'version',
            'calculated_at',
            'last_interaction_at',
            'explicit_preferences',
            'implicit_scores',
            'behavioral_metrics',
            'embeddings',
            'history',
            'metadata',
            'correlation_id',
            'is_active',
            'allow_personalization',
            'tags',
        ];

        protected $casts = [
            'explicit_preferences' => 'json',
            'implicit_scores' => 'json',
            'behavioral_metrics' => 'json',
            'embeddings' => 'json',
            'history' => 'json',
            'metadata' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'allow_personalization' => 'boolean',
            'updated_at' => 'datetime',
            'calculated_at' => 'datetime',
            'last_interaction_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

        // ========== ОТНОШЕНИЯ ==========

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        public function histories(): HasMany
        {
            return $this->hasMany(UserTasteProfileHistory::class, 'taste_profile_id');
        }

        // ========== ACCESSORS / GETTERS ==========

        /**
         * Получить data quality score (0–1)
         * Показывает, насколько зрелый и надёжный профиль
         */
        public function getDataQualityScore(): float
        {
            $metadata = $this->metadata ?? [];
            return min(1.0, (float) ($metadata['data_quality_score'] ?? 0.0));
        }

        /**
         * Получить текущее влияние ML-рекомендаций (0–0.7)
         * Снижается если пользователь игнорирует рекомендации
         */
        public function getRecommendationInfluence(): float
        {
            $metadata = $this->metadata ?? [];
            return min(0.7, (float) ($metadata['recommendation_influence'] ?? 0.5));
        }

        /**
         * Получить ML-версию модели
         */
        public function getModelVersion(): string
        {
            $metadata = $this->metadata ?? [];
            return $metadata['ml_model_version'] ?? 'taste-v2.0';
        }

        /**
         * Получить категорийные ML-скоры
         * @return array ['fashion_women' => 0.94, 'italian_food' => 0.89, ...]
         */
        public function getCategoryScores(): array
        {
            $implicit = $this->implicit_scores ?? [];
            return $implicit['category_scores'] ?? [];
        }

        /**
         * Получить основной embedding (768 или 384 размерности)
         */
        public function getMainEmbedding(): ?array
        {
            $embeddings = $this->embeddings ?? [];
            return $embeddings['main'] ?? null;
        }

        /**
         * Получить embedding для конкретной категории
         * @param string $category fashion, food, interior, beauty и т.д.
         */
        public function getCategoryEmbedding(string $category): ?array
        {
            $embeddings = $this->embeddings ?? [];
            return $embeddings[$category] ?? null;
        }

        /**
         * Получить явные предпочтения (размеры, стиль, диета)
         */
        public function getExplicitPreferences(): array
        {
            return $this->explicit_preferences ?? [];
        }

        /**
         * Получить behavioral метрики
         */
        public function getBehavioralMetrics(): array
        {
            $implicit = $this->implicit_scores ?? [];
            return $implicit['behavioral'] ?? [];
        }

        /**
         * Получить историю последних изменений
         */
        public function getRecentHistory(int $days = 30): array
        {
            $history = $this->history ?? [];
            $cutoff = now()->subDays($days)->toDateString();

            return array_filter($history, function ($item) use ($cutoff) {
                return ($item['date'] ?? '') >= $cutoff;
            });
        }

        /**
         * Получить количество взаимодействий
         */
        public function getTotalInteractions(): int
        {
            $metadata = $this->metadata ?? [];
            return (int) ($metadata['total_interactions'] ?? 0);
        }

        // ========== HELPER METHODS ==========

        /**
         * Этот профиль «готов» для рекомендаций?
         * Минимум 10 взаимодействий и data quality > 0.6
         */
        public function isReadyForRecommendations(): bool
        {
            return $this->getTotalInteractions() >= 10
                && $this->getDataQualityScore() >= 0.6
                && $this->allow_personalization;
        }

        /**
         * Этот профиль новый (холодный старт)?
         */
        public function isColdStart(): bool
        {
            return $this->getTotalInteractions() < 5;
        }

        /**
         * Нужен ли пересчёт embeddings?
         */
        public function needsRecalculation(): bool
        {
            $calculatedAt = $this->calculated_at ?? $this->created_at;
            return $calculatedAt->diffInHours(now()) > 24;
        }

        // ============ Global Scopes ============

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (tenancy()->initialized) {
                    $query->where('user_taste_profiles.tenant_id', tenant()->id);
                }
            });
        }

        // ============ Accessors & Mutators ============
}
