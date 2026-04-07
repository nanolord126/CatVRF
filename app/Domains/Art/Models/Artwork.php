<?php
declare(strict_types=1);

namespace App\Domains\Art\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Artwork extends Model
{
    use HasFactory;

    protected $table = 'artworks';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'artist_id',
        'project_id',
        'title',
        'description',
        'price_cents',
        'is_visible',
        'delivered_at',
        'tags',
        'meta',
    ];

    protected $hidden = ['password', 'token', 'secret'];

    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'is_visible' => 'boolean',
        'delivered_at' => 'datetime',
        'price_cents' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            $builder->where('tenant_id', self::resolveTenantId());
        });

        static::creating(static function (Artwork $artwork): void {
            $artwork->uuid = $artwork->uuid ?: (string) Str::uuid();
            $artwork->correlation_id = $artwork->correlation_id ?: (string) Str::uuid();
            $artwork->tenant_id = $artwork->tenant_id ?: self::resolveTenantId();
            $artwork->business_group_id = $artwork->business_group_id ?? self::resolveBusinessGroupId();
            $artwork->is_visible = $artwork->is_visible ?? true;
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

    public function priceWithCommission(float $commissionPercent): int
    {
        $base = $this->price_cents;
        $commission = (int) round($base * ($commissionPercent / 100));

        return $base + $commission;
    }

    public function scopeVisible(Builder $builder): Builder
    {
        return $builder->where('is_visible', true);
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
