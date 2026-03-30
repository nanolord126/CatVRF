<?php declare(strict_types=1);

namespace App\Domains\Auto\VehicleDealing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Vehicle extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'vehicles';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'dealer_id',
            'correlation_id',
            'make',
            'model',
            'year',
            'price_kopecks',
            'mileage',
            'status',
            'rating',
            'tags',
        ];

        protected $casts = [
            'price_kopecks' => 'integer',
            'mileage' => 'integer',
            'year' => 'integer',
            'rating' => 'float',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('vehicles.tenant_id', tenant()->id));
        }
}
