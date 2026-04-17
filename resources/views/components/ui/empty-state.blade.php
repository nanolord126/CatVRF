{{--
    x-ui-empty-state — пустое состояние каталога / списка.
    Пропсы:
      title    : string
      subtitle : string|null
      ctaText  : string|null  — текст кнопки CTA
      ctaHref  : string|null  — ссылка CTA
      icon     : string|null  — SVG-иконка (Heroicon name)
--}}
@props([
    'title'    => 'Пока ничего нет',
    'subtitle' => null,
    'ctaText'  => null,
    'ctaHref'  => null,
    'icon'     => 'inbox',
])

@php
$icons = [
    'inbox'    => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
    'search'   => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'cart'     => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    'star'     => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
];
$iconPath = $icons[$icon] ?? $icons['inbox'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-16 px-6 text-center']) }}>
    <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center mb-4" aria-hidden="true">
        <svg class="w-8 h-8 text-carbon-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $iconPath }}"/>
        </svg>
    </div>

    <h3 class="text-base font-semibold text-carbon-200 mb-1">{{ $title }}</h3>

    @if($subtitle)
        <p class="text-sm text-carbon-400 max-w-xs">{{ $subtitle }}</p>
    @endif

    @if($ctaText)
        <div class="mt-5">
            <x-ui-button :href="$ctaHref" variant="primary" size="sm">
                {{ $ctaText }}
            </x-ui-button>
        </div>
    @endif

    {{ $slot }}
</div>
