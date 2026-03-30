<?php
declare(strict_types=1);

namespace App\Domains\Art\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'project_id',
        'artist_id',
        'user_id',
        'rating',
        'comment',
        'tags',
        'meta',
    ];

    protected $hidden = ['password', 'token', 'secret'];

    protected $casts = [
        'rating' => 'integer',
        'tags' => 'array',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            $builder->where('tenant_id', self::resolveTenantId());
        });

        static::creating(static function (Review $review): void {
            $review->uuid = $review->uuid ?: (string) Str::uuid();
            $review->correlation_id = $review->correlation_id ?: (string) Str::uuid();
            $review->tenant_id = $review->tenant_id ?: self::resolveTenantId();
            $review->business_group_id = $review->business_group_id ?? self::resolveBusinessGroupId();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function isPositive(): bool
    {
        return $this->rating >= 4;
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
