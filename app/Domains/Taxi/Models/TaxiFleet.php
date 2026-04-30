<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TaxiFleet extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'taxi_fleets';

    protected $fillable = [
        'uuid',
        'correlation_id',
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

    public function vehicles(): HasMany
    {
        return $this->hasMany(TaxiVehicle::class, 'fleet_id');
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant()?->id ?? 0));
    }
}
