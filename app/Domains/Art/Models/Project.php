<?php
declare(strict_types=1);

namespace App\Domains\Art\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Project extends Model
{
    use HasFactory;

    protected $table = 'projects';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'artist_id',
        'title',
        'brief',
        'budget_cents',
        'status',
        'mode',
        'deadline_at',
        'preferences',
        'tags',
        'meta',
    ];

    protected $hidden = ['password', 'token', 'secret'];

    protected $casts = [
        'budget_cents' => 'integer',
        'deadline_at' => 'datetime',
        'preferences' => 'array',
        'tags' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            $builder->where('tenant_id', self::resolveTenantId());
        });

        static::creating(static function (Project $project): void {
            $project->uuid = $project->uuid ?: (string) Str::uuid();
            $project->correlation_id = $project->correlation_id ?: (string) Str::uuid();
            $project->tenant_id = $project->tenant_id ?: self::resolveTenantId();
            $project->business_group_id = $project->business_group_id ?? self::resolveBusinessGroupId();
            $project->status = $project->status ?: 'draft';
            $project->mode = $project->mode ?: 'b2c';
        });
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('status', 'active');
    }

    private static function resolveTenantId(): int
    {
        if (function_exists('tenant') && tenant()) {
            return (int) tenant()->id;
        }

        $request = app()->bound('request') ? app('request') : null;
        if ($request && $request->user() && isset($request->user()->tenant_id)) {
            return (int) $request->user()->tenant_id;
        }

        return (int) config('app.tenant_id', 0);
    }

    private static function resolveBusinessGroupId(): ?int
    {
        if (function_exists('filament') && filament()->getTenant()) {
            $tenant = filament()->getTenant();
            if (isset($tenant->active_business_group_id)) {
                return (int) $tenant->active_business_group_id;
            }
        }

        return null;
    }
}
