{{--
    x-ui-card — карточка маркетплейса.
    Пропсы:
      variant : default|elevated|bordered  (default: default)
    Слоты:
      $header (опционально)
      $footer (опционально)
      $slot   — основной контент
--}}
@props([
    'variant' => 'default',
    'header'  => null,
    'footer'  => null,
])

@php
$variantClasses = match($variant) {
    'elevated' => 'shadow-modal',
    'bordered' => 'border border-white/10 shadow-none',
    default    => 'shadow-card',
};
@endphp

<div {{ $attributes->merge(['class' => "bg-black/30 dark:bg-black/40 backdrop-blur-sm rounded-marketplace $variantClasses overflow-hidden"]) }}>

    @if(isset($header))
        <div class="px-5 py-4 border-b border-white/5">
            {{ $header }}
        </div>
    @endif

    <div class="px-5 py-4">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-5 py-3 border-t border-white/5 bg-white/5">
            {{ $footer }}
        </div>
    @endif

</div>
