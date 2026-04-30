<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Livewire;

use App\Domains\Fashion\Models\ClothingVariant;
use App\Shared\Application\Services\SizeRecommendationService;
use Livewire\Component;

/**
 * SizeSelector — Livewire component for size selection with ML recommendations.
 *
 * Features:
 * - Size recommendation based on measurements
 * - Size conversion between systems
 * - Stock availability checking
 * - Visual size grid
 *
 * @version 2026.1
 */
final class SizeSelector extends Component
{
    public int $productId;

    public string $gender = 'unisex';

    public ?string $brand = null;

    public ?string $selectedSize = null;

    public ?string $selectedColor = null;

    public ?string $selectedFit = null;

    public array $availableVariants = [];

    public array $recommendedSize = [];

    public bool $showRecommendation = false;

    public function mount(int $productId, string $gender = 'unisex', ?string $brand = null): void
    {
        $this->productId = $productId;
        $this->gender = $gender;
        $this->brand = $brand;

        $this->loadAvailableVariants();
    }

    public function loadAvailableVariants(): void
    {
        $this->availableVariants = ClothingVariant::where('clothing_product_id', $this->productId)
            ->active()
            ->inStock()
            ->get()
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'size_eu' => $variant->size_eu,
                'size_us' => $variant->size_us,
                'size_uk' => $variant->size_uk,
                'size_international' => $variant->size_international,
                'color' => $variant->color,
                'color_code' => $variant->color_code,
                'fit_type' => $variant->fit_type,
                'price_adjustment' => $variant->price_adjustment,
                'available_stock' => $variant->available_stock,
                'is_default' => $variant->is_default,
            ])
            ->toArray();
    }

    public function getSizeRecommendation(array $measurements): void
    {
        $service = app(SizeRecommendationService::class);
        $recommendation = $service->recommendFashionSize($measurements, $this->gender, $this->brand);

        if ($recommendation) {
            $this->recommendedSize = $recommendation;
            $this->showRecommendation = true;
        }
    }

    public function selectVariant(int $variantId): void
    {
        $variant = ClothingVariant::find($variantId);

        if ($variant) {
            $this->selectedSize = $variant->size_international;
            $this->selectedColor = $variant->color;
            $this->selectedFit = $variant->fit_type;

            $this->dispatch('variant-selected', variantId: $variantId);
        }
    }

    public function render()
    {
        return view('fashion.livewire.size-selector');
    }
}
