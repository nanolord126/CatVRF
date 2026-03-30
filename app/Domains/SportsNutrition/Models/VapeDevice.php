<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VapeDevice extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'vapes_devices';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'brand_id',
            'model_name',
            'type',
            'wattage_max',
            'battery_capacity_mah',
            'price_kopecks',
            'current_stock',
            'has_marking_znack',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'tags' => 'json',
            'price_kopecks' => 'integer',
            'current_stock' => 'integer',
            'has_marking_znack' => 'boolean',
            'wattage_max' => 'integer',
            'battery_capacity_mah' => 'integer',
            'tenant_id' => 'integer',
            'brand_id' => 'integer',
        ];

        protected $hidden = [
            'id',
            'deleted_at',
        ];

        /**
         * Booted method for global scoping and data protection.
         */
        protected static function booted(): void
        {
            // Изоляция данных на уровне базы (Tenant Scoping Канон 2026)
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', (int) tenant('id'));
                }
            });

            // Автогенерация UUID и Correlation ID
            static::creating(function (VapeDevice $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = (int) tenant('id');
                }
            });
        }

        /**
         * Бренд-производитель.
         */
        public function brand(): BelongsTo
        {
            return $this->belongsTo(VapeBrand::class, 'brand_id');
        }

        /**
         * Проверка наличия на складе.
         */
        public function isInStock(): bool
        {
            return $this->current_stock > 0;
        }

        /**
         * Требуется ли обязательная маркировка для этого устройства.
         */
        public function requiresMarking(): bool
        {
            return $this->has_marking_znack;
        }
}
