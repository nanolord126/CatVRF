<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageSubscription extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'beverage_subscriptions';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'shop_id',
            'plan_type',
            'price',
            'limit_count',
            'used_count',
            'starts_at',
            'expires_at',
            'auto_renew',
            'status',
            'correlation_id',
        ];

        protected $casts = [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'auto_renew' => 'boolean',
            'price' => 'integer',
            'limit_count' => 'integer',
            'used_count' => 'integer',
            'status' => 'string',
            'plan_type' => 'string',
        ];

        /**
         * Boot the model.
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = (string) Str::uuid();
            });

            // 2026 Canon: Global Scope Tenant
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant() !== null) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Parent shop.
         */
        public function shop(): BelongsTo
        {
            return $this->belongsTo(BeverageShop::class, 'shop_id');
        }

        /**
         * User who owns the subscription.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        /**
         * Scope for active subscriptions.
         */
        public function scopeActive(Builder $query): Builder
        {
            return $query->where('status', 'active')
                ->where('expires_at', '>', Carbon::now());
        }

        /**
         * Check if subscription has units left.
         */
        public function hasLimitLeft(): bool
        {
            return $this->used_count < $this->limit_count;
        }

        /**
         * Increment used count.
         */
        public function incrementUsage(): void
        {
            $this->increment('used_count');
        }

        /**
         * Subscription price in human readable format ($XX.YY)
         */
        public function getPriceFormattedAttribute(): string
        {
            return number_format($this->price / 100, 2, '.', ',');
        }
}
