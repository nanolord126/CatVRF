<?php declare(strict_types=1);

namespace App\Domains\Auto\CarWashing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarWashStation extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'car_wash_stations';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'owner_id',
            'correlation_id',
            'name',
            'address',
            'price_kopecks_per_service',
            'service_type',
            'rating',
            'tags',
        ];

        protected $casts = [
            'price_kopecks_per_service' => 'integer',
            'rating' => 'float',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('car_wash_stations.tenant_id', tenant()->id));
        }
}
