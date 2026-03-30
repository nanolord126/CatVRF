<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIConstruction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes, BelongsToTenant;

        protected $table = 'ai_constructions';

        protected $fillable = [
            'uuid',
            'user_id',
            'tenant_id',
            'type',
            'input_data',
            'photo_path',
            'analysis_result',
            'construction_data',
            'recommended_items',
            'taste_profile_used',
            'explicit_preferences_used',
            'implicit_preferences_used',
            'confidence_score',
            'confidence_breakdown',
            'fraud_score',
            'fraud_flagged',
            'view_count',
            'saved',
            'saved_at',
            'items_added_to_cart',
            'items_purchased',
            'purchase_total',
            'rating',
            'feedback',
            'correlation_id',
        ];

        protected $casts = [
            'input_data' => 'array',
            'analysis_result' => 'array',
            'construction_data' => 'array',
            'recommended_items' => 'array',
            'taste_profile_used' => 'array',
            'explicit_preferences_used' => 'array',
            'implicit_preferences_used' => 'array',
            'confidence_breakdown' => 'array',
            'saved' => 'boolean',
            'fraud_flagged' => 'boolean',
            'saved_at' => 'datetime',
        ];

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function tasteProfile(): BelongsTo
        {
            return $this->belongsTo(UserTasteProfile::class, 'user_id', 'user_id');
        }

        /**
         * Получить URL фото
         */
        public function getPhotoUrl(): ?string
        {
            if (!$this->photo_path) {
                return null;
            }

            return \Storage::url($this->photo_path);
        }

        /**
         * Удалить фото из хранилища
         */
        public function deletePhoto(): void
        {
            if ($this->photo_path && \Storage::exists($this->photo_path)) {
                \Storage::delete($this->photo_path);
            }
        }

        /**
         * Сохранить в избранное
         */
        public function markAsSaved(): void
        {
            $this->update([
                'saved' => true,
                'saved_at' => now(),
            ]);
        }

        /**
         * Удалить из избранного
         */
        public function unmarkAsSaved(): void
        {
            $this->update([
                'saved' => false,
                'saved_at' => null,
            ]);
        }

        /**
         * Добавить отзыв
         */
        public function addFeedback(int $rating, ?string $feedback = null): void
        {
            $this->update([
                'rating' => min(5, max(1, $rating)),
                'feedback' => $feedback,
            ]);
        }

        /**
         * Записать покупку товара
         */
        public function recordPurchase(int $itemCount, int $totalAmount): void
        {
            $this->increment('items_purchased', $itemCount);
            $this->increment('purchase_total', $totalAmount);
        }

        /**
         * Вычислить конверсию товаров (сколько добавлено, сколько куплено)
         */
        public function getConversionRate(): float
        {
            if ($this->items_added_to_cart === 0) {
                return 0.0;
            }

            return (float)$this->items_purchased / $this->items_added_to_cart;
        }

        /**
         * Получить средний чек товаров из конструкции
         */
        public function getAverageOrderValue(): int
        {
            if ($this->items_purchased === 0) {
                return 0;
            }

            return (int)($this->purchase_total / $this->items_purchased);
        }

        /**
         * Проверить, доверена ли эта конструкция (не заблокирована)
         */
        public function isTrusted(): bool
        {
            return !$this->fraud_flagged && $this->fraud_score < 0.7;
        }

        /**
         * Получить использованные вкусы как строку
         */
        public function getUsedPreferencesDescription(): string
        {
            $explicit = \count($this->explicit_preferences_used ?? []);
            $implicit = \count($this->implicit_preferences_used ?? []);

            return "explicit: {$explicit}, implicit: {$implicit}";
        }
}
