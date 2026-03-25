<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Studio extends Model
{
    use SoftDeletes;

    protected $table = 'studios';
    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'owner_id',
        'name',
        'description',
        'address',
        'geo_point',
        'amenities',
        'schedule',
        'phone',
        'website',
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'amenities' => AsCollection::class,
        'schedule' => 'json',
        'tags' => AsCollection::class,
        'is_verified' => 'boolean',
        'rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            $query->where('tenant_id', tenant('id'));
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Class$this->session->class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }
}
