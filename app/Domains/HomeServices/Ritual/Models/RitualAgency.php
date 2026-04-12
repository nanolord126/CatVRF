<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Ritual\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RitualAgency extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'ritual_agencies';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'license_number',
            'address',
            'contact_info',
            'rating',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $hidden = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        protected $casts = [
            'contact_info' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'rating' => 'float',
            'tenant_id' => 'integer',
        ];

        /**
         * Booted method for global scoping and UUID generation.
         */
        protected static function booted_disabled(): void
        {
            // Изоляция данных на уровне базы (Tenant Scoping)
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()->id) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });

            // Автогенерация UUID и Correlation ID
            static::creating(function (RitualAgency $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = (int) tenant()->id;
                }
            });
        }

        /**
         * Все товары агентства (Мемориальные изделия).
         */
        public function memorialProducts(): HasMany
        {
            return $this->hasMany(MemorialProduct::class, 'agency_id');
        }

        /**
         * Все комплексные заказы агентства.
         */
        public function funeralOrders(): HasMany
        {
            return $this->hasMany(FuneralOrder::class, 'agency_id');
        }

        /**
         * Получить активные агентства текущего теннанта.
         */
        public function scopeActive(Builder $query): Builder
        {
            return $query->where('is_active', true);
        }
}
