<?php
declare(strict_types=1);

namespace App\Domains\Art\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Artist extends Model
{

    protected $table = 'artists';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'name',
        'slug',
        'bio',
        'style',
        'rating',
        'is_active',
        'tags',
        'meta',
    ];

    protected $hidden = ['password', 'token', 'secret'];

    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
        'rating' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            $builder->where('tenant_id', self::resolveTenantId());
        });

        static::creating(static function (Artist $artist): void {
            $artist->uuid = $artist->uuid ?: (string) Str::uuid();
            $artist->correlation_id = $artist->correlation_id ?: (string) Str::uuid();
            $artist->tenant_id = $artist->tenant_id ?: self::resolveTenantId();
            $artist->business_group_id = $artist->business_group_id ?? self::resolveBusinessGroupId();
            $artist->is_active = $artist->is_active ?? true;
        });
    }

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    private static function resolveTenantId(): int
    {
        if (function_exists('tenant') && tenant()) {
            return (int) tenant()->id;
        }

        return 0;
    }

    private static function resolveBusinessGroupId(): int
    {
        if (tenant()) {
            $tenant = tenant();
            if (isset($tenant->active_business_group_id)) {
                return (int) $tenant->active_business_group_id;
            }
        }

        return 0;
    }
}
