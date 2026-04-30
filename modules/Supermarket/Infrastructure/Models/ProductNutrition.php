<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProductNutrition — Нутриционная информация и аллергены товаров
 * 
 * Хранит калорийность, БЖУ, состав, аллергены и противопоказания
 * для каждого товара в супермаркете.
 */
final class ProductNutrition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'tenant_id',
        'business_group_id',
        // Basic Nutritional Info (per 100g)
        'calories_per_100g',
        'proteins_per_100g',
        'fats_per_100g',
        'carbs_per_100g',
        'fiber_per_100g',
        'sugar_per_100g',
        'sodium_per_100g',
        // Serving Information
        'serving_size',
        'servings_per_package',
        // Additional Nutrients
        'saturated_fats_per_100g',
        'trans_fats_per_100g',
        'cholesterol_per_100g',
        'potassium_per_100g',
        'calcium_per_100g',
        'iron_per_100g',
        'vitamin_a_per_100g',
        'vitamin_c_per_100g',
        'vitamin_d_per_100g',
        // Ingredients
        'ingredients_list',
        'ingredients_json',
        // Additives
        'additives',
        'preservatives',
        'colorants',
        'flavor_enhancers',
        // Mandatory Allergens (152-FZ)
        'contains_gluten',
        'contains_crustaceans',
        'contains_eggs',
        'contains_fish',
        'contains_peanuts',
        'contains_soy',
        'contains_milk',
        'contains_nuts',
        'contains_celery',
        'contains_mustard',
        'contains_sesame',
        'contains_sulfites',
        'contains_lupin',
        'contains_molluscs',
        // Additional Allergens
        'contains_lactose',
        'contains_fructose',
        'contains_corn',
        'contains_yeast',
        // Allergen Details
        'allergen_details',
        // Contraindications
        'diabetic_friendly',
        'gluten_free',
        'lactose_free',
        'low_sodium',
        'low_sugar',
        'low_fat',
        'vegan',
        'vegetarian',
        'halal',
        'kosher',
        'organic',
        // Age Restrictions
        'min_age',
        'max_age',
        'pregnancy_warning',
        'breastfeeding_warning',
        // Metadata
        'nutrition_source',
        'nutrition_verified_at',
        'metadata',
    ];

    protected $casts = [
        // Nutritional casts
        'proteins_per_100g' => 'decimal:2',
        'fats_per_100g' => 'decimal:2',
        'carbs_per_100g' => 'decimal:2',
        'fiber_per_100g' => 'decimal:2',
        'sugar_per_100g' => 'decimal:2',
        'serving_size' => 'decimal:2',
        'saturated_fats_per_100g' => 'decimal:2',
        'trans_fats_per_100g' => 'decimal:2',
        'cholesterol_per_100g' => 'decimal:2',
        'iron_per_100g' => 'decimal:2',
        // Boolean casts
        'contains_gluten' => 'boolean',
        'contains_crustaceans' => 'boolean',
        'contains_eggs' => 'boolean',
        'contains_fish' => 'boolean',
        'contains_peanuts' => 'boolean',
        'contains_soy' => 'boolean',
        'contains_milk' => 'boolean',
        'contains_nuts' => 'boolean',
        'contains_celery' => 'boolean',
        'contains_mustard' => 'boolean',
        'contains_sesame' => 'boolean',
        'contains_sulfites' => 'boolean',
        'contains_lupin' => 'boolean',
        'contains_molluscs' => 'boolean',
        'contains_lactose' => 'boolean',
        'contains_fructose' => 'boolean',
        'contains_corn' => 'boolean',
        'contains_yeast' => 'boolean',
        'diabetic_friendly' => 'boolean',
        'gluten_free' => 'boolean',
        'lactose_free' => 'boolean',
        'low_sodium' => 'boolean',
        'low_sugar' => 'boolean',
        'low_fat' => 'boolean',
        'vegan' => 'boolean',
        'vegetarian' => 'boolean',
        'halal' => 'boolean',
        'kosher' => 'boolean',
        'organic' => 'boolean',
        'pregnancy_warning' => 'boolean',
        'breastfeeding_warning' => 'boolean',
        // JSON casts
        'ingredients_json' => 'json',
        'additives' => 'json',
        'preservatives' => 'json',
        'colorants' => 'json',
        'flavor_enhancers' => 'json',
        'allergen_details' => 'json',
        'metadata' => 'json',
        // DateTime casts
        'nutrition_verified_at' => 'datetime',
    ];

    protected $table = 'supermarket_product_nutrition';

    // ========================
    // SCOPES
    // ========================

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeContainsAllergen($query, string $allergen)
    {
        return $query->where("contains_{$allergen}", true);
    }

    public function scopeGlutenFree($query)
    {
        return $query->where('gluten_free', true);
    }

    public function scopeVegan($query)
    {
        return $query->where('vegan', true);
    }

    public function scopeVegetarian($query)
    {
        return $query->where('vegetarian', true);
    }

    public function scopeDiabeticFriendly($query)
    {
        return $query->where('diabetic_friendly', true);
    }

    public function scopeLowSodium($query)
    {
        return $query->where('low_sodium', true);
    }

    public function scopeLowSugar($query)
    {
        return $query->where('low_sugar', true);
    }

    public function scopeHalal($query)
    {
        return $query->where('halal', true);
    }

    public function scopeKosher($query)
    {
        return $query->where('kosher', true);
    }

    public function scopeOrganic($query)
    {
        return $query->where('organic', true);
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Получить список всех аллергенов в товаре
     */
    public function getAllergens(): array
    {
        $allergens = [];
        $mandatoryAllergens = [
            'gluten', 'crustaceans', 'eggs', 'fish', 'peanuts',
            'soy', 'milk', 'nuts', 'celery', 'mustard',
            'sesame', 'sulfites', 'lupin', 'molluscs'
        ];
        $additionalAllergens = ['lactose', 'fructose', 'corn', 'yeast'];

        foreach (array_merge($mandatoryAllergens, $additionalAllergens) as $allergen) {
            if ($this->{"contains_{$allergen}"}) {
                $allergens[] = $allergen;
            }
        }

        return $allergens;
    }

    /**
     * Проверить наличие аллергена
     */
    public function hasAllergen(string $allergen): bool
    {
        return $this->{"contains_{$allergen}"} ?? false;
    }

    /**
     * Проверить совместимость с диетическими ограничениями
     */
    public function isCompatibleWith(array $restrictions): bool
    {
        foreach ($restrictions as $restriction) {
            if ($restriction === 'gluten_free' && !$this->gluten_free) {
                return false;
            }
            if ($restriction === 'lactose_free' && !$this->lactose_free) {
                return false;
            }
            if ($restriction === 'vegan' && !$this->vegan) {
                return false;
            }
            if ($restriction === 'vegetarian' && !$this->vegetarian) {
                return false;
            }
            if ($restriction === 'diabetic' && !$this->diabetic_friendly) {
                return false;
            }
            if ($restriction === 'low_sodium' && !$this->low_sodium) {
                return false;
            }
            if ($restriction === 'halal' && !$this->halal) {
                return false;
            }
            if ($restriction === 'kosher' && !$this->kosher) {
                return false;
            }
        }

        return true;
    }

    /**
     * Рассчитать нутриенты для указанного веса
     */
    public function calculateNutrientsForWeight(float $weightGrams): array
    {
        $ratio = $weightGrams / 100;

        return [
            'calories' => (int) round($this->calories_per_100g * $ratio),
            'proteins' => round($this->proteins_per_100g * $ratio, 2),
            'fats' => round($this->fats_per_100g * $ratio, 2),
            'carbs' => round($this->carbs_per_100g * $ratio, 2),
            'fiber' => round($this->fiber_per_100g * $ratio, 2),
            'sugar' => round($this->sugar_per_100g * $ratio, 2),
            'sodium' => (int) round($this->sodium_per_100g * $ratio),
        ];
    }

    /**
     * Получить предупреждения об аллергенах для клиента
     */
    public function getAllergenWarningsForCustomer(array $customerAllergies): array
    {
        $warnings = [];
        $productAllergens = $this->getAllergens();

        foreach ($customerAllergies as $customerAllergen) {
            if (in_array($customerAllergen, $productAllergens)) {
                $warnings[] = [
                    'allergen' => $customerAllergen,
                    'severity' => 'high',
                    'message' => "Продукт содержит аллерген: {$customerAllergen}",
                ];
            }
        }

        // Проверяем следы аллергенов
        $details = $this->allergen_details ?? [];
        if (isset($details['traces_of'])) {
            foreach ($details['traces_of'] as $trace) {
                if (in_array($trace, $customerAllergies)) {
                    $warnings[] = [
                        'allergen' => $trace,
                        'severity' => 'medium',
                        'message' => "Продукт может содержать следы аллергена: {$trace}",
                    ];
                }
            }
        }

        return $warnings;
    }

    /**
     * Проверить возрастные ограничения
     */
    public function isAgeRestricted(int $age): bool
    {
        if ($this->min_age && $age < $this->min_age) {
            return true;
        }
        if ($this->max_age && $age > $this->max_age) {
            return true;
        }
        return false;
    }

    /**
     * Получить рекомендацию по возрасту
     */
    public function getAgeRecommendation(): ?string
    {
        if ($this->min_age && $this->max_age) {
            return "Рекомендуется для возраста {$this->min_age}-{$this->max_age} лет";
        }
        if ($this->min_age) {
            return "Рекомендуется для возраста от {$this->min_age} лет";
        }
        if ($this->max_age) {
            return "Рекомендуется для возраста до {$this->max_age} лет";
        }
        return null;
    }

    /**
     * Проверить халяль статус
     */
    public function isHalal(): bool
    {
        return $this->halal;
    }

    /**
     * Проверить кошерный статус
     */
    public function isKosher(): bool
    {
        return $this->kosher;
    }

    /**
     * Получить религиозные ограничения продукта
     */
    public function getReligiousRestrictions(): array
    {
        $restrictions = [];
        
        if ($this->halal) {
            $restrictions[] = 'halal';
        }
        if ($this->kosher) {
            $restrictions[] = 'kosher';
        }
        if (!$this->halal && !$this->kosher) {
            $restrictions[] = 'no_religious_certification';
        }

        return $restrictions;
    }

    /**
     * Проверить совместимость с религиозными ограничениями клиента
     */
    public function isReligiouslyCompatible(array $customerRestrictions): bool
    {
        foreach ($customerRestrictions as $restriction) {
            if ($restriction === 'halal' && !$this->halal) {
                return false;
            }
            if ($restriction === 'kosher' && !$this->kosher) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить информацию о сертификации
     */
    public function getCertificationInfo(): array
    {
        $certifications = [];
        $details = $this->metadata ?? [];

        if ($this->halal) {
            $certifications[] = [
                'type' => 'halal',
                'certified' => true,
                'authority' => $details['halal_authority'] ?? null,
                'certificate_number' => $details['halal_certificate_number'] ?? null,
                'valid_until' => $details['halal_valid_until'] ?? null,
            ];
        }

        if ($this->kosher) {
            $certifications[] = [
                'type' => 'kosher',
                'certified' => true,
                'authority' => $details['kosher_authority'] ?? null,
                'certificate_number' => $details['kosher_certificate_number'] ?? null,
                'valid_until' => $details['kosher_valid_until'] ?? null,
            ];
        }

        return $certifications;
    }
}
