<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserInteraction extends Model
{
    use HasFactory;

        protected $table = 'user_interactions';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'user_id',
            'interaction_type',
            'interactable_type',
            'interactable_id',
            'vertical',
            'category',
            'item_attributes',
            'duration_seconds',
            'metadata',
            'correlation_id',
        ];

        protected $casts = [
            'item_attributes' => 'json',
            'metadata' => 'json',
        ];

        // ============ Relations ============

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function interactable()
        {
            return $this->morphTo();
        }

        // ============ Global Scopes ============

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('user_interactions.tenant_id', tenant()->id);
            });
        }

        // ============ Methods ============

        /**
         * Получить тип взаимодействия (для анализа)
         */
        public function getInteractionWeight(): float
        {
            return match ($this->interaction_type) {
                'product_click' => 0.2,
                'add_to_cart' => 0.5,
                'add_to_wishlist' => 0.6,
                'purchase' => 1.0,
                'rating_submit' => 0.8,
                'ai_constructor_use' => 0.7,
                default => 0.0,
            };
        }

        /**
         * Является ли взаимодействие "позитивным" (клик, покупка, рейтинг)?
         */
        public function isPositive(): bool
        {
            return \in_array($this->interaction_type, [
                'product_click',
                'add_to_cart',
                'add_to_wishlist',
                'purchase',
                'rating_submit',
            ]);
        }
}
