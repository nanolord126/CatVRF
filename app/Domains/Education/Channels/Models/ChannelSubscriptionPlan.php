<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ChannelSubscriptionPlan extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'channel_subscription_plans';

        protected $fillable = [
            'slug',
            'name',
            'price_kopecks',
            'posts_per_day',
            'photos_per_post',
            'shorts_enabled',
            'polls_enabled',
            'promo_enabled',
            'advanced_stats',
            'scheduled_posts',
            'features',
            'is_active',
            'correlation_id',
        ];

        protected $casts = [
            'price_kopecks'   => 'integer',
            'posts_per_day'   => 'integer',
            'photos_per_post' => 'integer',
            'shorts_enabled'  => 'boolean',
            'polls_enabled'   => 'boolean',
            'promo_enabled'   => 'boolean',
            'advanced_stats'  => 'boolean',
            'scheduled_posts' => 'boolean',
            'features'        => 'json',
            'is_active'       => 'boolean',
        ];

        /** Каналы на данном плане */
        public function channels(): HasMany
        {
            return $this->hasMany(BusinessChannel::class, 'plan_id');
        }

        /** Усageы этого плана */
        public function usages(): HasMany
        {
            return $this->hasMany(ChannelSubscriptionUsage::class, 'plan_id');
        }

        /** Цена в рублях (для отображения) */
        public function getPriceRublesAttribute(): float
        {
            return $this->price_kopecks / 100;
        }
}
