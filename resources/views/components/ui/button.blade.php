{{--
    x-ui-button — кнопка маркетплейса.
    Пропсы:
      variant  : primary|secondary|danger|ghost|link  (default: primary)
      size     : sm|md|lg                              (default: md)
      type     : submit|button|reset                   (default: button)
      loading  : bool                                  (default: false)
      disabled : bool                                  (default: false)
      href     : string|null  (если передан — рендерит <a>)
      ariaLabel: string|null
--}}
@props([
    'variant'   => 'primary',
    'size'      => 'md',
    'type'      => 'button',
    'loading'   => false,
    'disabled'  => false,
    'href'      => null,
    'ariaLabel' => null,
])

@php
$sizeClasses = match($size) {
    'sm'  => 'px-3 py-1.5 text-xs rounded-lg min-h-[32px]',
    'lg'  => 'px-6 py-3.5 text-base rounded-xl min-h-[52px]',
    default => 'px-4 py-2.5 text-sm rounded-marketplace min-h-[44px]',
};

$variantClasses = match($variant) {
    'secondary' => 'bg-marketplace-secondary hover:bg-marketplace-secondary-hover text-white font-semibold shadow-card',
    'danger'    => 'bg-marketplace-danger hover:bg-red-600 text-white font-semibold shadow-card',
    'ghost'     => 'bg-white/10 hover:bg-white/20 text-white font-medium border border-white/10',
    'link'      => 'bg-transparent text-marketplace-primary hover:underline font-medium p-0 min-h-0 shadow-none',
    default     => 'bg-marketplace-primary hover:bg-marketplace-primary-hover text-white font-semibold shadow-card',
};

$baseClasses = 'inline-flex items-center justify-center gap-2 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-marketplace-primary focus-visible:ring-offset-2 focus-visible:ring-offset-transparent select-none';
$disabledClasses = ($disabled || $loading) ? 'opacity-60 cursor-not-allowed pointer-events-none' : 'cursor-pointer';

$allClasses = implode(' ', [$baseClasses, $sizeClasses, $variantClasses, $disabledClasses]);
@endphp

@if($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $allClasses]) }}
        @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        @if($disabled || $loading) aria-disabled="true" tabindex="-1" @endif
    >
        @if($loading)
            <svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        @endif
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $allClasses]) }}
        @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        @if($disabled || $loading) disabled aria-disabled="true" @endif
    >
        @if($loading)
            <svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
