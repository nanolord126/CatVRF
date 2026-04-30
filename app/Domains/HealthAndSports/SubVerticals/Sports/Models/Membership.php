<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;

final class Membership extends Model
{
    use TenantScoped;

    protected $table = 'memberships';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'studio_id',
        'name',
        'description',
        'type',
        'duration_days',
        'price',
        'classes_per_month',
        'included_classes',
        'benefits',
        'allow_guests',
        'max_guests_per_visit',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'included_classes' => AsCollection::class,
        'benefits' => AsCollection::class,
        'tags' => AsCollection::class,
        'allow_guests' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'float',
    ];

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
