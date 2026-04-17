<?php declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

/**
 * PriceLabel — компонент цены с учётом B2C/B2B тира и наличия.
 * Обновляется при событии price-updated (dispatched из PricingService).
 *
 * @see resources/views/livewire/shared/price-label.blade.php
 */
final class PriceLabel extends Component
{
    public int    $amountKop    = 0;  // цена в копейках B2C
    public ?int   $b2bAmountKop = null; // цена в копейках B2B (null = нет B2B-цены)
    public bool   $inStock      = true;
    public bool   $isB2B        = false;
    public string $currency     = '₽';
    public string $size         = 'md'; // sm|md|lg
    public int    $productId    = 0;

    public function __construct(
        private readonly AuthManager $auth,
        private readonly Request     $request,
    ) {}

    public function mount(
        int    $amountKop    = 0,
        ?int   $b2bAmountKop = null,
        bool   $inStock      = true,
        string $size         = 'md',
        int    $productId    = 0,
    ): void {
        $this->amountKop    = $amountKop;
        $this->b2bAmountKop = $b2bAmountKop;
        $this->inStock      = $inStock;
        $this->size         = $size;
        $this->productId    = $productId;

        // Определение B2B по канону
        $this->isB2B = $this->request->has('inn') && $this->request->has('business_card_id');
    }

    /**
     * Отображаемая цена в рублях (с учётом B2B).
     */
    public function getDisplayAmountProperty(): float
    {
        $kop = ($this->isB2B && $this->b2bAmountKop !== null)
            ? $this->b2bAmountKop
            : $this->amountKop;

        return round($kop / 100, 2);
    }

    /**
     * B2C-цена для отображения зачёркнутой "старой" цены в B2B-режиме.
     */
    public function getB2cAmountProperty(): float
    {
        return round($this->amountKop / 100, 2);
    }

    public function render(): View
    {
        return view('livewire.shared.price-label');
    }
}
