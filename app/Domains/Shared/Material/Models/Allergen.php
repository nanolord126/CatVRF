<?php

declare(strict_types=1);

namespace App\Domains\Shared\Material\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

final class Allergen extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'allergens';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'scientific_name',
        'description',
        'category',
        'severity',
        'common_sources',
        'symptoms',
        'prevention_tips',
        'cross_reactivity',
        'prevalence_percentage',
        'is_common',
        'is_lifelong',
        'is_food_related',
        'is_contact_related',
        'is_inhalation_related',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'common_sources' => 'json',
        'symptoms' => 'json',
        'prevention_tips' => 'json',
        'cross_reactivity' => 'json',
        'prevalence_percentage' => 'float',
        'is_common' => 'boolean',
        'is_lifelong' => 'boolean',
        'is_food_related' => 'boolean',
        'is_contact_related' => 'boolean',
        'is_inhalation_related' => 'boolean',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const CATEGORY_PROTEIN = 'protein';
    public const CATEGORY_CHEMICAL = 'chemical';
    public const CATEGORY_METAL = 'metal';
    public const CATEGORY_LATEX = 'latex';
    public const CATEGORY_DUST = 'dust';
    public const CATEGORY_POLLEN = 'pollen';
    public const CATEGORY_FRAGRANCE = 'fragrance';
    public const CATEGORY_DYE = 'dye';
    public const CATEGORY_PRESERVATIVE = 'preservative';

    public const SEVERITY_MILD = 'mild';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_SEVERE = 'severe';
    public const SEVERITY_LIFE_THREATENING = 'life_threatening';

    public const COMMON_ALLERGENS = [
        'wool' => 'Wool (Lanolin)',
        'latex' => 'Latex',
        'nickel' => 'Nickel',
        'chromium' => 'Chromium',
        'formaldehyde' => 'Formaldehyde',
        'polyurethane' => 'Polyurethane',
        'acrylic' => 'Acrylic',
        'polyester' => 'Polyester',
        'dyes' => 'Synthetic Dyes',
        'fragrances' => 'Synthetic Fragrances',
        'rubber' => 'Natural Rubber',
        'adhesives' => 'Adhesives/Resins',
    ];

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeCommon(Builder $query): Builder
    {
        return $query->where('is_common', true);
    }

    public function scopeContactRelated(Builder $query): Builder
    {
        return $query->where('is_contact_related', true);
    }

    public function scopeFoodRelated(Builder $query): Builder
    {
        return $query->where('is_food_related', true);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'material_allergens')
            ->withTimestamps()
            ->withPivot('severity', 'reaction_type', 'prevalence_percentage');
    }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_MILD => 'Mild',
            self::SEVERITY_MODERATE => 'Moderate',
            self::SEVERITY_SEVERE => 'Severe',
            self::SEVERITY_LIFE_THREATENING => 'Life Threatening',
            default => 'Unknown',
        };
    }

    public function getSeverityLevelAttribute(): int
    {
        return match($this->severity) {
            self::SEVERITY_MILD => 1,
            self::SEVERITY_MODERATE => 2,
            self::SEVERITY_SEVERE => 3,
            self::SEVERITY_LIFE_THREATENING => 4,
            default => 0,
        };
    }

    public function isRelevantForVertical(string $vertical): bool
    {
        $relevantCategories = match($vertical) {
            'fashion', 'footwear' => [
                self::CATEGORY_PROTEIN,
                self::CATEGORY_CHEMICAL,
                self::CATEGORY_METAL,
                self::CATEGORY_LATEX,
                self::CATEGORY_DYE,
                self::CATEGORY_FRAGRANCE,
                self::CATEGORY_PRESERVATIVE,
            ],
            default => [],
        };

        return in_array($this->category, $relevantCategories, true);
    }

    public function getCrossReactiveAllergensAttribute(): array
    {
        if (!$this->cross_reactivity) {
            return [];
        }

        return self::whereIn('name', $this->cross_reactivity)->get()->toArray();
    }

    public function getRiskAssessmentAttribute(): array
    {
        return [
            'severity' => $this->severity_label,
            'severity_level' => $this->severity_level,
            'is_common' => $this->is_common,
            'prevalence' => $this->prevalence_percentage,
            'is_lifelong' => $this->is_lifelong,
            'contact_related' => $this->is_contact_related,
            'symptoms' => $this->symptoms,
            'prevention' => $this->prevention_tips,
        ];
    }

    public static function getCommonMaterialAllergens(): array
    {
        return Cache::remember('common_material_allergens', now()->addDays(30), function () {
            return self::where('is_common', true)
                ->where('is_contact_related', true)
                ->orderBy('prevalence_percentage', 'desc')
                ->get()
                ->toArray();
        });
    }

    public static function getBySeverityLevel(int $minLevel): array
    {
        $severityMap = [
            1 => self::SEVERITY_MILD,
            2 => self::SEVERITY_MODERATE,
            3 => self::SEVERITY_SEVERE,
            4 => self::SEVERITY_LIFE_THREATENING,
        ];

        $severities = array_slice($severityMap, $minLevel - 1);

        return self::whereIn('severity', $severities)->get()->toArray();
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

        static::saved(function () {
            Cache::forget('common_material_allergens');
        });

        static::deleted(function () {
            Cache::forget('common_material_allergens');
        });
    }
}
