<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyConsumable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;

        protected $table = 'beauty_consumables';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'salon_id',
            'name',
            'unit',
            'current_stock',
            'min_threshold',
            'unit_cost',
            'correlation_id',
        ];

        protected $casts = [
            'current_stock' => 'integer',
            'min_threshold' => 'integer',
            'unit_cost' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scoping', function ($builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });
        }

        public function salon(): BelongsTo
        {
            return $this->belongsTo(BeautySalon::class, 'salon_id');
        }
}
