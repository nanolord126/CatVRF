<?php

declare(strict_types=1);

namespace Modules\Contraindications\Infrastructure\Repositories;

use Modules\Contraindications\Domain\Entities\ProductComposition;
use Modules\Contraindications\Domain\Repositories\ProductCompositionRepositoryInterface;
use Modules\Contraindications\Infrastructure\Models\ProductCompositionModel;

final class ProductCompositionRepository implements ProductCompositionRepositoryInterface
{
    public function findByComposable(string $composableType, int $composableId): ?ProductComposition
    {
        $model = ProductCompositionModel::where('composable_type', $composableType)
            ->where('composable_id', $composableId)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(ProductComposition $composition): void
    {
        ProductCompositionModel::updateOrCreate(
            ['id' => $composition->id],
            [
                'tenant_id' => $composition->tenantId,
                'composable_type' => $composition->composableType,
                'composable_id' => $composition->composableId,
                'ingredients' => $composition->ingredientIds,
                'calories_per_100g' => $composition->caloriesPer100g,
                'proteins' => $composition->proteins,
                'fats' => $composition->fats,
                'carbs' => $composition->carbs,
                'allergens' => $composition->allergens,
            ]
        );
    }

    public function delete(int $id): void
    {
        ProductCompositionModel::findOrFail($id)->delete();
    }

    private function toEntity(ProductCompositionModel $model): ProductComposition
    {
        return new ProductComposition(
            id: $model->id,
            tenantId: $model->tenant_id,
            composableType: $model->composable_type,
            composableId: $model->composable_id,
            ingredientIds: $model->ingredients,
            caloriesPer100g: $model->calories_per_100g ? (float) $model->calories_per_100g : null,
            proteins: $model->proteins ? (float) $model->proteins : null,
            fats: $model->fats ? (float) $model->fats : null,
            carbs: $model->carbs ? (float) $model->carbs : null,
            allergens: $model->allergens,
        );
    }
}
