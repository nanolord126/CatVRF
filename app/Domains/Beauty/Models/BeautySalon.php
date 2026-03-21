<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель салона красоты.
 * Production 2026.
 */
final class BeautySalon extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'beauty_salons';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'name',
        'address',
        'phone',
        'email',
        'description',
        'working_hours',
        'geo_point',
        'rating',
        'review_count',
        'is_verified',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $hidden = [];

    protected $casts = [
        'working_hours' => 'json',
        'geo_point' => 'json',
        'tags' => 'collection',
        'metadata' => 'json',
        'is_verified' => 'boolean',
        'rating' => 'float',
        'review_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function masters(): HasMany
    {
        return $this->hasMany(Master::class, 'salon_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'salon_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'salon_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(BeautyProduct::class, 'salon_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'salon_id');
    }
}
