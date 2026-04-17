{{--
    x-ui-price — цена с учётом B2C/B2B режима и наличия.
    Пропсы:
      amount       : int|float  — цена в рублях
      b2bAmount    : int|float|null  — оптовая цена (null = только B2C)
      isB2B        : bool  — текущий режим пользователя
      inStock      : bool  — наличие товара
      currency     : string  (default: ₽)
      size         : sm|md|lg  (default: md)
      showOldPrice : float|null  — зачёркнутая старая цена
--}}
@props([
    'amount'       => 0,
    'b2bAmount'    => null,
    'isB2B'        => false,
    'inStock'      => true,
    'currency'     => '₽',
    'size'         => 'md',
    'showOldPrice' => null,
])

@php
$displayAmount = ($isB2B && $b2bAmount !== null) ? $b2bAmount : $amount;
$formatted     = number_format($displayAmount, 0, '.', ' ');
$sizeClass     = match($size) {
    'sm'  => 'text-sm',
    'lg'  => 'text-2xl font-bold',
    default => 'text-lg font-semibold',
};
$colorClass = $inStock ? 'text-carbon-50' : 'text-carbon-500';
@endphp

<span
    class="inline-flex items-baseline gap-1.5"
    aria-live="polite"
    aria-label="Цена: {{ $formatted }} {{ $currency }}{{ !$inStock ? ' (нет в наличии)' : '' }}"
>
    @if($showOldPrice)
        <span class="line-through text-carbon-500 {{ $size === 'lg' ? 'text-base' : 'text-xs' }}">
            {{ number_format($showOldPrice, 0, '.', ' ') }}&nbsp;{{ $currency }}
        </span>
    @endif

    <span class="{{ $sizeClass }} {{ $colorClass }} tabular-nums">
        {{ $formatted }}&nbsp;<span class="text-carbon-400">{{ $currency }}</span>
    </span>

    @if($isB2B && $b2bAmount !== null)
        <x-ui-badge variant="info" class="ml-1">B2B</x-ui-badge>
    @endif
</span>
