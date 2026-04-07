<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use App\Models\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TaxiDriver extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes;

        protected $table = 'taxi_drivers';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'user_id',
            'license_number',
            'rating',
            'completed_rides',
            'current_location',
            'is_active',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $casts = [
            'current_location' => 'json',
            'tags' => 'collection',
            'metadata' => 'json',
            'is_active' => 'boolean',
            'rating' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant()?->id ?? 0));
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'user_id');
        }

        public function vehicles(): HasMany
        {
            return $this->hasMany(TaxiVehicle::class, 'driver_id');
        }

        public function rides(): HasMany
        {
            return $this->hasMany(TaxiRide::class, 'driver_id');
        }
}
