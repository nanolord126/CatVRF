{{-- livewire/shared/price-label.blade.php --}}
<x-ui-price
    :amount="$this->getDisplayAmountProperty()"
    :b2b-amount="$b2bAmountKop !== null ? $b2bAmountKop / 100 : null"
    :is-b2b="$isB2B"
    :in-stock="$inStock"
    :size="$size"
    :show-old-price="$showOldPrice"
/>
