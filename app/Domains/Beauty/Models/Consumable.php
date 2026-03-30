<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Consumable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes;

        protected $table = 'beauty_consumables';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'salon_id',
            'name',
            'sku',
            'unit',
            'current_stock',
            'min_stock_threshold',
            'price_per_unit_kopeki',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'current_stock' => 'integer',
            'min_stock_threshold' => 'integer',
            'price_per_unit_kopeki' => 'integer',
            'tags' => 'json',
        ];

        /**
         * Global scope для tenant и business_group.
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scoped', function ($query) {
                if ($tenantId = tenant('id')) {
                    $query->where('tenant_id', $tenantId);
                }
            });

            // Если в сессии есть активная бизнес-группа, применяем скоуп
            static::addGlobalScope('business_group_scoped', function ($query) {
                if (session()->has('active_business_group')) {
                    $query->where('business_group_id', session('active_business_group'));
                }
            });
        }

        /**
         * Отношение к салону.
         */
        public function salon(): BelongsTo
        {
            return $this->belongsTo(BeautySalon::class, 'salon_id');
        }
}
