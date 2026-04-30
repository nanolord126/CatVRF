<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Material — Shared domain entity for materials used across Fashion and Footwear.
 *
 * Contains allergen information for healthcare compliance (152-ФЗ, ФЗ-323).
 * Materials can trigger allergic reactions (e.g., wool → lanolin, leather → chromium).
 *
 * @property string $uuid
 * @property string $name (e.g., "cotton", "wool", "leather", "latex")
 * @property string $slug URL-friendly slug
 * @property string|null $description Material description
 * @property string $category (natural, synthetic, blend, special)
 * @property array|null $properties Material properties (breathable, waterproof, stretch, etc.)
 * @property array|null $care_instructions Care instructions for this material
 * @property bool $is_sustainable Whether material is eco-friendly
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Material extends Model
{
    protected $table = 'shared_materials';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'category',
        'properties',
        'care_instructions',
        'is_sustainable',
        'is_active',
    ];

    protected $casts = [
        'properties' => 'json',
        'care_instructions' => 'json',
        'is_sustainable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const CATEGORY_NATURAL = 'natural';
    public const CATEGORY_SYNTHETIC = 'synthetic';
    public const CATEGORY_BLEND = 'blend';
    public const CATEGORY_SPECIAL = 'special';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeSustainable(Builder $query): Builder
    {
        return $query->where('is_sustainable', true);
    }

    /**
     * Get allergen information for this material.
     */
    public function allergens(): HasMany
    {
        return $this->hasMany(MaterialAllergy::class, 'material_name', 'name');
    }

    /**
     * Check if material has any allergens.
     */
    public function hasAllergens(): bool
    {
        return $this->allergens()->where('is_active', true)->exists();
    }

    /**
     * Get all allergens for this material.
     */
    public function getAllergens(): array
    {
        return Cache::remember(
            "material:{$this->uuid}:allergens",
            now()->addDays(7),
            fn() => $this->allergens()->where('is_active', true)->get()->toArray()
        );
    }

    /**
     * Check if material is safe for user with given allergies.
     */
    public function isSafeForAllergies(array $userAllergenTypes): bool
    {
        $materialAllergens = $this->getAllergens();

        foreach ($materialAllergens as $allergy) {
            if (in_array($allergy['allergen_type'], $userAllergenTypes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get material by slug with caching.
     */
    public static function getBySlug(string $slug): ?self
    {
        return Cache::remember(
            "material:by_slug:{$slug}",
            now()->addDays(7),
            fn() => self::where('slug', $slug)->active()->first()
        );
    }

    /**
     * Get material by name with caching.
     */
    public static function getByName(string $name): ?self
    {
        return Cache::remember(
            "material:by_name:{$name}",
            now()->addDays(7),
            fn() => self::where('name', $name)->active()->first()
        );
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
        });

        static::updated(function ($model) {
            Cache::forget("material:by_slug:{$model->slug}");
            Cache::forget("material:by_name:{$model->name}");
            Cache::forget("material:{$model->uuid}:allergens");
            Cache::tags(['materials'])->flush();
        });

        static::deleted(function ($model) {
            Cache::forget("material:by_slug:{$model->slug}");
            Cache::forget("material:by_name:{$model->name}");
            Cache::forget("material:{$model->uuid}:allergens");
            Cache::tags(['materials'])->flush();
        });
    }
}
