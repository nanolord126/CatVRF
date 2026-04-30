<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

final class Trainer extends Model
{
    use TenantScoped;

    protected $table = 'trainers';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'studio_id',
        'user_id',
        'full_name',
        'bio',
        'specializations',
        'certifications',
        'experience_years',
        'avatar_url',
        'hourly_rate',
        'is_active',
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'specializations' => AsCollection::class,
        'certifications' => AsCollection::class,
        'tags' => AsCollection::class,
        'rating' => 'float',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'hourly_rate' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
