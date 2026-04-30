<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Livewire;

use App\Domains\Footwear\Models\FootwearVariant;
use App\Domains\Footwear\Services\FootwearSizeService;
use Livewire\Component;

/**
 * FootwearSizeSelector — Livewire component for footwear size selection.
 *
 * Features:
 * - Size recommendation based on foot measurements (length + width)
 * - Width selection (narrow, regular, wide, extra wide)
 * - Size conversion between EU/US/UK/CM
 * - Stock availability checking
 *
 * @version 2026.1
 */
final class FootwearSizeSelector extends Component
{
    public int $productId;

    public string $gender = 'unisex';

    public ?string $brand = null;

    public ?string $selectedSizeEu = null;

    public ?string $selectedWidth = null;

    public ?string $selectedColor = null;

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
        $this->availableVariants = FootwearVariant::where('footwear_product_id', $this->productId)
            ->active()
            ->inStock()
            ->get()
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'size_eu' => $variant->size_eu,
                'size_us' => $variant->size_us,
                'size_uk' => $variant->size_uk,
                'size_cm' => $variant->size_cm,
                'width' => $variant->width,
                'color' => $variant->color,
                'color_code' => $variant->color_code,
                'price_adjustment' => $variant->price_adjustment,
                'available_stock' => $variant->available_stock,
                'is_default' => $variant->is_default,
            ])
            ->toArray();
    }

    public function getSizeRecommendation(float $footLengthCm, float $footWidthCm): void
    {
        $service = app(FootwearSizeService::class);
        $recommendation = $service->recommendSize($footLengthCm, $footWidthCm, $this->gender, $this->brand);

        if ($recommendation) {
            $this->recommendedSize = $recommendation;
            $this->showRecommendation = true;
        }
    }

    public function selectVariant(int $variantId): void
    {
        $variant = FootwearVariant::find($variantId);

        if ($variant) {
            $this->selectedSizeEu = $variant->size_eu;
            $this->selectedWidth = $variant->width;
            $this->selectedColor = $variant->color;

            $this->dispatch('variant-selected', variantId: $variantId);
        }
    }

    public function getAvailableWidths(string $sizeEu): array
    {
        $service = app(FootwearSizeService::class);
        return $service->getAvailableWidths($this->productId, $sizeEu);
    }

    public function render()
    {
        return view('footwear.livewire.size-selector');
    }
}
