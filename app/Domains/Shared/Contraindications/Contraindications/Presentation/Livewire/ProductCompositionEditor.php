<?php

declare(strict_types=1);

namespace Modules\Contraindications\Presentation\Livewire;

use Livewire\Component;
use Modules\Contraindications\Infrastructure\Models\ProductCompositionModel;

final class ProductCompositionEditor extends Component
{
    public string $composableType;
    public int $composableId;

    public array $ingredients = [];
    public ?float $caloriesPer100g = null;
    public ?float $proteins = null;
    public ?float $fats = null;
    public ?float $carbs = null;
    public array $allergens = [];

    public bool $isEditing = false;
    public ?int $editingId = null;

    protected $rules = [
        'ingredients' => 'array',
        'caloriesPer100g' => 'nullable|numeric|min:0',
        'proteins' => 'nullable|numeric|min:0',
        'fats' => 'nullable|numeric|min:0',
        'carbs' => 'nullable|numeric|min:0',
        'allergens' => 'array',
    ];

    public function mount(string $composableType, int $composableId): void
    {
        $this->composableType = $composableType;
        $this->composableId = $composableId;

        $composition = ProductCompositionModel::where('composable_type', $composableType)
            ->where('composable_id', $composableId)
            ->first();

        if ($composition) {
            $this->editingId = $composition->id;
            $this->ingredients = $composition->ingredients;
            $this->caloriesPer100g = $composition->calories_per_100g ? (float) $composition->calories_per_100g : null;
            $this->proteins = $composition->proteins ? (float) $composition->proteins : null;
            $this->fats = $composition->fats ? (float) $composition->fats : null;
            $this->carbs = $composition->carbs ? (float) $composition->carbs : null;
            $this->allergens = $composition->allergens;
            $this->isEditing = true;
        }
    }

    public function save(): void
    {
        $this->validate();

        ProductCompositionModel::updateOrCreate(
            ['id' => $this->editingId],
            [
                'tenant_id' => tenant()->id,
                'composable_type' => $this->composableType,
                'composable_id' => $this->composableId,
                'ingredients' => $this->ingredients,
                'calories_per_100g' => $this->caloriesPer100g,
                'proteins' => $this->proteins,
                'fats' => $this->fats,
                'carbs' => $this->carbs,
                'allergens' => $this->allergens,
            ]
        );

        $this->dispatch('composition-saved');
    }

    public function render()
    {
        return view('contraindications::livewire.product-composition-editor');
    }
}
