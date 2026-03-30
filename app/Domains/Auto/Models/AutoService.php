<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'auto_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'description',
            'category',
            'base_price_kopecks',
            'estimated_hours',
            'consumables_json',
            'correlation_id',
            'tags',
            'metadata',
            'is_active',
        ];

        protected $casts = [
            'tenant_id' => 'integer',
            'base_price_kopecks' => 'integer',
            'estimated_hours' => 'float',
            'consumables_json' => 'json',
            'tags' => 'json',
            'metadata' => 'json',
            'is_active' => 'boolean',
        ];

        /**
         * Автоматическая генерация UUID и tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function ($model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant('id') ?? 1);
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                $builder->where('auto_services.tenant_id', tenant('id') ?? 1);
            });
        }

        /**
         * Расчет стоимости работ на основе норм-часа.
         */
        public function calculateLaborCost(int $hourlyRateKopecks): int
        {
            return (int) ($this->estimated_hours * $hourlyRateKopecks);
        }
}
