<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class FashionStore extends Model
{




        protected $table = 'fashion_stores';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'slug',
            'inn',
            'type',
            'schedule_json',
            'rating',
            'is_verified',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'schedule_json' => 'json',
            'tags' => 'json',
            'is_verified' => 'boolean',
            'rating' => 'float',
        ];

        protected $hidden = [
            'id',
            'correlation_id',
        ];

        /**
         * Глобальный скоуп на tenant_id
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                $tenantId = (function_exists('tenant') && tenant()) ? tenant()->id : null;
                if ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                }
            });

            static::creating(function ($model) {
                if (empty($model->uuid)) {
                    $model->uuid = \Illuminate\Support\Str::uuid()->toString();
                }
                if (empty($model->tenant_id)) {
                    $model->tenant_id = (function_exists('tenant') && tenant()) ? tenant()->id : 0;
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
            });
        }

        /**
         * Все товары магазина
         */
        public function products(): HasMany
        {
            return $this->hasMany(FashionProduct::class);
        }

        /**
         * Коллекции магазина
         */
        public function collections(): HasMany
        {
            return $this->hasMany(FashionCollection::class);
        }

        /**
         * Оптовые заказы
         */
        public function b2bOrders(): HasMany
        {
            return $this->hasMany(FashionB2BOrder::class);
        }

        public function isB2B(): bool
        {
            return !empty($this->inn);
        }
    }
