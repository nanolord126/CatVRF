<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ShoeLastType extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'shoe_last_types';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'toe_shape',
        'toe_box_width',
        'toe_box_height',
        'arch_support',
        'arch_height',
        'heel_height',
        'heel_type',
        'fit_characteristics',
        'suitable_for_foot_types',
        'suitable_for_activities',
        'material_recommendations',
        'comfort_level',
        'is_standard',
        'brand_specific',
        'correlation_id',
        'metadata',
        'technical_specs',
    ];

    protected $casts = [
        'toe_box_width' => 'float',
        'toe_box_height' => 'float',
        'arch_height' => 'float',
        'heel_height' => 'float',
        'suitable_for_foot_types' => 'json',
        'suitable_for_activities' => 'json',
        'material_recommendations' => 'json',
        'comfort_level' => 'integer',
        'is_standard' => 'boolean',
        'brand_specific' => 'boolean',
        'metadata' => 'json',
        'technical_specs' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const TOE_SHAPE_ROUND = 'round';
    public const TOE_SHAPE_POINTED = 'pointed';
    public const TOE_SHAPE_SQUARE = 'square';
    public const TOE_SHAPE_ALMOND = 'almond';
    public const TOE_SHAPE_OVAL = 'oval';
    public const TOE_SHAPE_CHISEL = 'chisel';

    public const ARCH_SUPPORT_LOW = 'low';
    public const ARCH_SUPPORT_MEDIUM = 'medium';
    public const ARCH_SUPPORT_HIGH = 'high';
    public const ARCH_SUPPORT_CUSTOM = 'custom';

    public const FIT_NARROW = 'narrow';
    public const FIT_REGULAR = 'regular';
    public const FIT_WIDE = 'wide';
    public const FIT_EXTRA_WIDE = 'extra_wide';

    public const HEEL_TYPE_FLAT = 'flat';
    public const HEEL_TYPE_LOW = 'low';
    public const HEEL_TYPE_MID = 'mid';
    public const HEEL_TYPE_HIGH = 'high';
    public const HEEL_TYPE_PLATFORM = 'platform';
    public const HEEL_TYPE_WEDGE = 'wedge';
    public const HEEL_TYPE_BLOCK = 'block';
    public const HEEL_TYPE_STILETTO = 'stiletto';

    public const FOOT_TYPE_FLAT = 'flat';
    public const FOOT_TYPE_NORMAL = 'normal';
    public const FOOT_TYPE_HIGH_ARCH = 'high_arch';
    public const FOOT_TYPE_WIDE = 'wide';
    public const FOOT_TYPE_NARROW = 'narrow';

    public const ACTIVITY_WALKING = 'walking';
    public const ACTIVITY_RUNNING = 'running';
    public const ACTIVITY_SPORTS = 'sports';
    public const ACTIVITY_FORMAL = 'formal';
    public const ACTIVITY_CASUAL = 'casual';
    public const ACTIVITY_WORK = 'work';
    public const ACTIVITY_OUTDOOR = 'outdoor';

    public function scopeByToeShape(Builder $query, string $toeShape): Builder
    {
        return $query->where('toe_shape', $toeShape);
    }

    public function scopeByArchSupport(Builder $query, string $archSupport): Builder
    {
        return $query->where('arch_support', $archSupport);
    }

    public function scopeByFit(Builder $query, string $fit): Builder
    {
        return $query->where('fit_characteristics', $fit);
    }

    public function scopeByHeelHeight(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('heel_height', [$min, $max]);
    }

    public function scopeStandard(Builder $query): Builder
    {
        return $query->where('is_standard', true);
    }

    public function scopeForFootType(Builder $query, string $footType): Builder
    {
        return $query->whereJsonContains('suitable_for_foot_types', $footType);
    }

    public function scopeForActivity(Builder $query, string $activity): Builder
    {
        return $query->whereJsonContains('suitable_for_activities', $activity);
    }

    public function products(): HasMany
    {
        return $this->hasMany(FootwearProduct::class, 'shoe_last_type_id');
    }

    public function isSuitableForFootType(string $footType): bool
    {
        return in_array($footType, $this->suitable_for_foot_types ?? [], true);
    }

    public function isSuitableForActivity(string $activity): bool
    {
        return in_array($activity, $this->suitable_for_activities ?? [], true);
    }

    public function getComfortRatingAttribute(): string
    {
        return match(true) {
            $this->comfort_level >= 90 => 'excellent',
            $this->comfort_level >= 75 => 'very_good',
            $this->comfort_level >= 60 => 'good',
            $this->comfort_level >= 40 => 'fair',
            default => 'poor',
        };
    }

    public function getToeBoxVolumeAttribute(): float
    {
        return $this->toe_box_width * $this->toe_box_height;
    }

    public function compareWith(self $other): array
    {
        return [
            'toe_box_difference' => $this->toe_box_width - $other->toe_box_width,
            'arch_difference' => $this->arch_height - $other->arch_height,
            'heel_difference' => $this->heel_height - $other->heel_height,
            'comfort_difference' => $this->comfort_level - $other->comfort_level,
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = tenant()->id ?? auth()->user()?->tenant_id;
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
        });
    }
}
