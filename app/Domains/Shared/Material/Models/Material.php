<?php

declare(strict_types=1);

namespace App\Domains\Shared\Material\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

final class Material extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'materials';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'category',
        'type',
        'composition',
        'properties',
        'care_instructions',
        'environmental_impact',
        'certifications',
        'is_natural',
        'is_sustainable',
        'is_recyclable',
        'is_biodegradable',
        'is_hypoallergenic',
        'is_antimicrobial',
        'is_water_resistant',
        'is_breathable',
        'is_stretchable',
        'is_durable',
        'thermal_properties',
        'weight_gsm',
        'thickness_mm',
        'durability_rating',
        'comfort_rating',
        'breathability_rating',
        'water_resistance_rating',
        'correlation_id',
        'metadata',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'composition' => 'json',
        'properties' => 'json',
        'environmental_impact' => 'json',
        'certifications' => 'json',
        'thermal_properties' => 'json',
        'is_natural' => 'boolean',
        'is_sustainable' => 'boolean',
        'is_recyclable' => 'boolean',
        'is_biodegradable' => 'boolean',
        'is_hypoallergenic' => 'boolean',
        'is_antimicrobial' => 'boolean',
        'is_water_resistant' => 'boolean',
        'is_breathable' => 'boolean',
        'is_stretchable' => 'boolean',
        'is_durable' => 'boolean',
        'weight_gsm' => 'float',
        'thickness_mm' => 'float',
        'durability_rating' => 'integer',
        'comfort_rating' => 'integer',
        'breathability_rating' => 'integer',
        'water_resistance_rating' => 'integer',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const CATEGORY_FABRIC = 'fabric';
    public const CATEGORY_LEATHER = 'leather';
    public const CATEGORY_SYNTHETIC = 'synthetic';
    public const CATEGORY_RUBBER = 'rubber';
    public const CATEGORY_FOAM = 'foam';
    public const CATEGORY_METAL = 'metal';
    public const CATEGORY_PLASTIC = 'plastic';
    public const CATEGORY_WOOD = 'wood';
    public const CATEGORY_NATURAL_FIBER = 'natural_fiber';
    public const CATEGORY_BLEND = 'blend';

    public const TYPE_COTTON = 'cotton';
    public const TYPE_WOOL = 'wool';
    public const TYPE_SILK = 'silk';
    public const TYPE_LINEN = 'linen';
    public const TYPE_HEMP = 'hemp';
    public const TYPE_POLYESTER = 'polyester';
    public const TYPE_NYLON = 'nylon';
    public const TYPE_SPANDEX = 'spandex';
    public const TYPE_ELASTANE = 'elastane';
    public const TYPE_VISCOSE = 'viscose';
    public const TYPE_RAYON = 'rayon';
    public const TYPE_ACRYLIC = 'acrylic';
    public const TYPE_DENIM = 'denim';
    public const TYPE_VELVET = 'velvet';
    public const TYPE_SATIN = 'satin';
    public const TYPE_CANVAS = 'canvas';
    public const TYPE_FLEECE = 'fleece';
    public const TYPE_GENUINE_LEATHER = 'genuine_leather';
    public const TYPE_SYNTHETIC_LEATHER = 'synthetic_leather';
    public const TYPE_SUEDE = 'suede';
    public const TYPE_NUBUCK = 'nubuck';
    public const TYPE_PATENT_LEATHER = 'patent_leather';
    public const TYPE_RUBBER = 'rubber';
    public const TYPE_EVA = 'eva';
    public const TYPE_PU = 'pu';
    public const TYPE_MEMORY_FOAM = 'memory_foam';
    public const TYPE_GEL = 'gel';

    public function scopeNatural(Builder $query): Builder
    {
        return $query->where('is_natural', true);
    }

    public function scopeSustainable(Builder $query): Builder
    {
        return $query->where('is_sustainable', true);
    }

    public function scopeHypoallergenic(Builder $query): Builder
    {
        return $query->where('is_hypoallergenic', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeWaterResistant(Builder $query): Builder
    {
        return $query->where('is_water_resistant', true);
    }

    public function scopeBreathable(Builder $query): Builder
    {
        return $query->where('is_breathable', true);
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'material_allergens')
            ->withTimestamps()
            ->withPivot('severity', 'reaction_type', 'prevalence_percentage');
    }

    public function fashionProducts(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domains\Fashion\Models\FashionProduct::class, 'fashion_product_materials')
            ->withTimestamps()
            ->withPivot('percentage', 'is_primary');
    }

    public function footwearProducts(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domains\Footwear\Models\FootwearProduct::class, 'footwear_product_materials')
            ->withTimestamps()
            ->withPivot('percentage', 'is_primary', 'location');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(\App\Models\Media::class, 'mediable');
    }

    public function getAllergensListAttribute(): array
    {
        return Cache::remember(
            "material:{$this->id}:allergens",
            now()->addDays(7),
            fn() => $this->allergens()->get()->toArray()
        );
    }

    public function hasAllergen(string $allergenName): bool
    {
        return $this->allergens()->where('name', $allergenName)->exists();
    }

    public function getAllergenSeverity(string $allergenName): ?string
    {
        $pivot = $this->allergens()->where('name', $allergenName)->first()?->pivot;
        return $pivot?->severity;
    }

    public function isCompatibleWithUserAllergies(array $userAllergens): bool
    {
        foreach ($userAllergens as $allergen) {
            if ($this->hasAllergen($allergen)) {
                return false;
            }
        }
        return true;
    }

    public function getCompatibilityReport(array $userAllergens): array
    {
        $conflicts = [];
        $warnings = [];

        foreach ($this->allergens as $materialAllergen) {
            if (in_array($materialAllergen->name, $userAllergens, true)) {
                $conflicts[] = [
                    'allergen' => $materialAllergen->name,
                    'severity' => $materialAllergen->pivot->severity,
                    'reaction_type' => $materialAllergen->pivot->reaction_type,
                    'prevalence' => $materialAllergen->pivot->prevalence_percentage,
                ];
            }
        }

        if (!$this->is_hypoallergenic && in_array('general_sensitive_skin', $userAllergens, true)) {
            $warnings[] = [
                'type' => 'sensitive_skin',
                'message' => 'This material is not hypoallergenic and may cause irritation for sensitive skin',
            ];
        }

        return [
            'is_compatible' => empty($conflicts),
            'conflicts' => $conflicts,
            'warnings' => $warnings,
            'material_name' => $this->name,
            'is_hypoallergenic' => $this->is_hypoallergenic,
        ];
    }

    public function getEnvironmentalScoreAttribute(): float
    {
        if (!$this->environmental_impact) {
            return 0.0;
        }

        $score = 0.0;
        $maxScore = 100.0;

        if ($this->is_natural) $score += 20;
        if ($this->is_sustainable) $score += 20;
        if ($this->is_recyclable) $score += 15;
        if ($this->is_biodegradable) $score += 15;

        $impact = $this->environmental_impact;
        if (isset($impact['carbon_footprint']) && $impact['carbon_footprint'] <= 5) $score += 10;
        if (isset($impact['water_usage']) && $impact['water_usage'] <= 100) $score += 10;
        if (isset($impact['chemical_free']) && $impact['chemical_free']) $score += 10;

        return min($score, $maxScore);
    }

    public function getSustainabilityBadgeAttribute(): string
    {
        $score = $this->environmental_score;

        return match(true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'moderate',
            $score >= 20 => 'poor',
            default => 'unknown',
        };
    }

    public function clearCache(): void
    {
        Cache::forget("material:{$this->id}:allergens");
        Cache::tags(['materials', "material:{$this->id}"])->flush();
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
            if ($model->isDirty(['allergens', 'is_hypoallergenic'])) {
                $model->clearCache();
            }
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
