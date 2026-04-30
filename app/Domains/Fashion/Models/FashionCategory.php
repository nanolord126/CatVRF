<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class FashionCategory extends Model
{
    use TenantScoped;

    protected $table = 'fashion_categories';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'description',
        'slug',
        'image_url',
        'parent_category_id',
        'display_order',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(FashionProduct::class, 'category_id');
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function ($query) {
            if (tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
