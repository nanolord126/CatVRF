<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Collection;
use Modules\Restaurant\Domain\Entities\Ingredient;
use Modules\Restaurant\Domain\Entities\Recipe;
use Modules\Restaurant\Domain\ValueObjects\Money;
use Modules\Restaurant\Infrastructure\Models\IngredientModel;
use Modules\Restaurant\Infrastructure\Models\RecipeModel;

final class InventoryService
{
    public function __construct(
        private readonly string $tenantId,
    ) {}

    // Ингредиенты

    public function createIngredient(
        string $name,
        string $unit,
        Money $costPerUnit,
        string $sku = '',
        float $minStock = 10.0,
        float $maxStock = 100.0,
    ): Ingredient {
        $ingredient = Ingredient::create(
            tenantId: (int) $this->tenantId,
            name: $name,
            unit: $unit,
            costPerUnit: $costPerUnit,
            sku: $sku,
            minStock: $minStock,
            maxStock: $maxStock,
        );

        $model = IngredientModel::create([
            'tenant_id' => $ingredient->tenantId,
            'name' => $ingredient->name,
            'sku' => $ingredient->sku,
            'unit' => $ingredient->unit,
            'current_stock' => $ingredient->currentStock,
            'min_stock' => $ingredient->minStock,
            'max_stock' => $ingredient->maxStock,
            'cost_per_unit_kopecks' => $ingredient->costPerUnit->toKopecks(),
            'is_active' => $ingredient->isActive,
        ]);

        return new Ingredient(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            sku: $model->sku,
            unit: $model->unit,
            currentStock: (float) $model->current_stock,
            minStock: (float) $model->min_stock,
            maxStock: (float) $model->max_stock,
            costPerUnit: Money::fromKopecks($model->cost_per_unit_kopecks),
            isActive: $model->is_active,
            supplierId: $model->supplier_id,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function addStock(int $ingredientId, float $quantity): Ingredient
    {
        $model = IngredientModel::where('tenant_id', $this->tenantId)
            ->where('id', $ingredientId)
            ->firstOrFail();

        $model->current_stock += $quantity;
        $model->save();

        return new Ingredient(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            sku: $model->sku,
            unit: $model->unit,
            currentStock: (float) $model->current_stock,
            minStock: (float) $model->min_stock,
            maxStock: (float) $model->max_stock,
            costPerUnit: Money::fromKopecks($model->cost_per_unit_kopecks),
            isActive: $model->is_active,
            supplierId: $model->supplier_id,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function removeStock(int $ingredientId, float $quantity): Ingredient
    {
        $model = IngredientModel::where('tenant_id', $this->tenantId)
            ->where('id', $ingredientId)
            ->firstOrFail();

        if ($model->current_stock < $quantity) {
            throw new \RuntimeException('Insufficient stock');
        }

        $model->current_stock -= $quantity;
        $model->save();

        return new Ingredient(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            sku: $model->sku,
            unit: $model->unit,
            currentStock: (float) $model->current_stock,
            minStock: (float) $model->min_stock,
            maxStock: (float) $model->max_stock,
            costPerUnit: Money::fromKopecks($model->cost_per_unit_kopecks),
            isActive: $model->is_active,
            supplierId: $model->supplier_id,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function getIngredient(int $ingredientId): ?Ingredient
    {
        return IngredientModel::where('tenant_id', $this->tenantId)
            ->where('id', $ingredientId)
            ->first()
            ?->transform(fn ($model) => new Ingredient(
                id: $model->id,
                tenantId: $model->tenant_id,
                name: $model->name,
                description: $model->description,
                sku: $model->sku,
                unit: $model->unit,
                currentStock: (float) $model->current_stock,
                minStock: (float) $model->min_stock,
                maxStock: (float) $model->max_stock,
                costPerUnit: Money::fromKopecks($model->cost_per_unit_kopecks),
                isActive: $model->is_active,
                supplierId: $model->supplier_id,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getAllIngredients(): Collection
    {
        return IngredientModel::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($model) => new Ingredient(
                id: $model->id,
                tenantId: $model->tenant_id,
                name: $model->name,
                description: $model->description,
                sku: $model->sku,
                unit: $model->unit,
                currentStock: (float) $model->current_stock,
                minStock: (float) $model->min_stock,
                maxStock: (float) $model->max_stock,
                costPerUnit: Money::fromKopecks($model->cost_per_unit_kopecks),
                isActive: $model->is_active,
                supplierId: $model->supplier_id,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getLowStockIngredients(): Collection
    {
        return IngredientModel::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderBy('current_stock')
            ->get()
            ->map(fn ($model) => new Ingredient(
                id: $model->id,
                tenantId: $model->tenant_id,
                name: $model->name,
                description: $model->description,
                sku: $model->sku,
                unit: $model->unit,
                currentStock: (float) $model->current_stock,
                minStock: (float) $model->min_stock,
                maxStock: (float) $model->max_stock,
                costPerUnit: Money::fromKopecks($model->cost_per_unit_kopecks),
                isActive: $model->is_active,
                supplierId: $model->supplier_id,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getTotalInventoryValue(): Money
    {
        return IngredientModel::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->get()
            ->reduce(function (Money $total, $model) {
                $stockValue = Money::fromKopecks($model->cost_per_unit_kopecks)
                    ->multiply((float) $model->current_stock);
                return $total->add($stockValue);
            }, Money::zero());
    }

    // Рецептуры

    public function createRecipe(
        int $menuItemId,
        int $ingredientId,
        float $quantity,
        string $unit,
    ): Recipe {
        $recipe = Recipe::create(
            tenantId: (int) $this->tenantId,
            menuItemId: $menuItemId,
            ingredientId: $ingredientId,
            quantity: $quantity,
            unit: $unit,
        );

        $model = RecipeModel::create([
            'tenant_id' => $recipe->tenantId,
            'menu_item_id' => $recipe->menuItemId,
            'ingredient_id' => $recipe->ingredientId,
            'quantity' => $recipe->quantity,
            'unit' => $recipe->unit,
            'is_active' => $recipe->isActive,
        ]);

        return new Recipe(
            id: $model->id,
            tenantId: $model->tenant_id,
            menuItemId: $model->menu_item_id,
            ingredientId: $model->ingredient_id,
            quantity: (float) $model->quantity,
            unit: $model->unit,
            isActive: $model->is_active,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function getRecipeForMenuItem(int $menuItemId): Collection
    {
        return RecipeModel::where('tenant_id', $this->tenantId)
            ->where('menu_item_id', $menuItemId)
            ->where('is_active', true)
            ->with('ingredient')
            ->get()
            ->map(fn ($model) => new Recipe(
                id: $model->id,
                tenantId: $model->tenant_id,
                menuItemId: $model->menu_item_id,
                ingredientId: $model->ingredient_id,
                quantity: (float) $model->quantity,
                unit: $model->unit,
                isActive: $model->is_active,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function deductIngredientsForOrder(int $menuItemId, int $quantity): void
    {
        $recipes = $this->getRecipeForMenuItem($menuItemId);

        foreach ($recipes as $recipe) {
            $requiredQuantity = $recipe->quantity * $quantity;
            $this->removeStock($recipe->ingredientId, $requiredQuantity);
        }
    }

    public function checkIngredientAvailability(int $menuItemId, int $quantity): bool
    {
        $recipes = $this->getRecipeForMenuItem($menuItemId);

        foreach ($recipes as $recipe) {
            $requiredQuantity = $recipe->quantity * $quantity;
            $ingredient = $this->getIngredient($recipe->ingredientId);

            if ($ingredient === null || $ingredient->currentStock < $requiredQuantity) {
                return false;
            }
        }

        return true;
    }
}
