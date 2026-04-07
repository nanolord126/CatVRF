<?php
declare(strict_types=1);

namespace App\Domains\Art\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class PortfolioItem extends Model
{
    use HasFactory;

    protected $table = 'portfolio_items';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'artist_id',
        'project_id',
        'title',
        'cover_url',
        'description',
        'published_at',
        'tags',
        'meta',
    ];

    protected $hidden = ['password', 'token', 'secret'];

    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            $builder->where('tenant_id', self::resolveTenantId());
        });

        static::creating(static function (PortfolioItem $item): void {
            $item->uuid = $item->uuid ?: (string) Str::uuid();
            $item->correlation_id = $item->correlation_id ?: (string) Str::uuid();
            $item->tenant_id = $item->tenant_id ?: self::resolveTenantId();
            $item->business_group_id = $item->business_group_id ?? self::resolveBusinessGroupId();
        });
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopePublished(Builder $builder): Builder
    {
        return $builder->whereNotNull('published_at');
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
