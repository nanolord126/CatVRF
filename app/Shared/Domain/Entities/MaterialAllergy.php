<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * MaterialAllergy — Shared domain entity for material allergen information.
 *
 * Healthcare compliance (152-ФЗ, ФЗ-323). Maps materials to allergen types and severity.
 *
 * @property string $uuid
 * @property string $material_name Material name (e.g., "wool", "leather", "latex")
 * @property string $allergen_type (contact_dermatitis, respiratory, food, chemical, metal, textile, rubber, latex)
 * @property string $severity (mild, moderate, severe, life_threatening)
 * @property array $triggering_materials List of materials that trigger this allergy
 * @property string $description Allergy description
 * @property string|null $medical_reference Medical reference code
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class MaterialAllergy extends Model
{
    protected $table = 'shared_material_allergies';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'material_name',
        'allergen_type',
        'severity',
        'triggering_materials',
        'description',
        'medical_reference',
        'is_active',
    ];

    protected $casts = [
        'triggering_materials' => 'json',
        'is_active' => 'boolean',
    ];

    public const ALLERGEN_TYPE_CONTACT_DERMATITIS = 'contact_dermatitis';
    public const ALLERGEN_TYPE_RESPIRATORY = 'respiratory';
    public const ALLERGEN_TYPE_FOOD = 'food';
    public const ALLERGEN_TYPE_CHEMICAL = 'chemical';
    public const ALLERGEN_TYPE_METAL = 'metal';
    public const ALLERGEN_TYPE_TEXTILE = 'textile';
    public const ALLERGEN_TYPE_RUBBER = 'rubber';
    public const ALLERGEN_TYPE_LATEX = 'latex';

    public const SEVERITY_MILD = 'mild';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_SEVERE = 'severe';
    public const SEVERITY_LIFE_THREATENING = 'life_threatening';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByMaterial(Builder $query, string $materialName): Builder
    {
        return $query->where('material_name', $materialName);
    }

    public function scopeByAllergenType(Builder $query, string $allergenType): Builder
    {
        return $query->where('allergen_type', $allergenType);
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeSevere(Builder $query): Builder
    {
        return $query->whereIn('severity', [
            self::SEVERITY_SEVERE,
            self::SEVERITY_LIFE_THREATENING,
        ]);
    }

    /**
     * Check if this allergy affects specific material.
     */
    public function affectsMaterial(string $materialName): bool
    {
        if ($this->material_name === $materialName) {
            return true;
        }

        return in_array($materialName, $this->triggering_materials ?? [], true);
    }

    /**
     * Get allergies by material name with caching.
     */
    public static function getByMaterial(string $materialName): array
    {
        return Cache::remember(
            "material_allergy:material:{$materialName}",
            now()->addDays(7),
            fn() => self::byMaterial($materialName)->active()->get()->toArray()
        );
    }

    /**
     * Get all allergen types for a material.
     */
    public static function getAllergenTypesForMaterial(string $materialName): array
    {
        $allergies = self::getByMaterial($materialName);

        return array_column($allergies, 'allergen_type');
    }

    /**
     * Check if material has severe allergies.
     */
    public static function hasSevereAllergies(string $materialName): bool
    {
        return self::byMaterial($materialName)
            ->active()
            ->severe()
            ->exists();
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::updated(function ($model) {
            Cache::forget("material_allergy:material:{$model->material_name}");
            Cache::tags(['material_allergies'])->flush();
        });

        static::deleted(function ($model) {
            Cache::forget("material_allergy:material:{$model->material_name}");
            Cache::tags(['material_allergies'])->flush();
        });
    }
}
