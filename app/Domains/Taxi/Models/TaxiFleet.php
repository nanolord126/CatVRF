<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Автопарк такси.
 * Production 2026.
 */
final class TaxiFleet extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'taxi_fleets';

    protected $fillable = [
        'tenant_id',
        'name',
        'company_name',
        'vehicle_count',
        'rating',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'rating' => 'float',
        'vehicle_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(TaxiVehicle::class, 'fleet_id');
    }
}
