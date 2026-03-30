<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeterinaryConsumable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'veterinary_consumables';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'name',
            'sku',
            'current_stock',
            'min_stock_threshold',
            'price_per_unit',
            'type',
            'expiration_date',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'current_stock' => 'integer',
            'min_stock_threshold' => 'integer',
            'price_per_unit' => 'integer',
            'expiration_date' => 'date',
            'tags' => 'json',
        ];

        /**
         * Boot logic
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scope', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('veterinary_consumables.tenant_id', tenant()->id);
                }
            });

            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $model->tenant_id = $model->tenant_id ?? tenant()->id;
                }
            });
        }

        /**
         * Relations: Clinic
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(VeterinaryClinic::class, 'clinic_id');
        }

        /**
         * Check if item is low on stock
         */
        public function getIsLowAttribute(): bool
        {
            return $this->current_stock <= $this->min_stock_threshold;
        }

        /**
         * Check if expired (if date set)
         */
        public function getIsExpiredAttribute(): bool
        {
            return $this->expiration_date ? $this->expiration_date->isPast() : false;
        }
}
