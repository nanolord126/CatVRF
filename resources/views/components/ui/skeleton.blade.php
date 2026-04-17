{{--
    x-ui-skeleton — placeholder-заглушка для загружаемого контента.
    Пропсы:
      variant : line|circle|rect|card  (default: line)
      lines   : int  — количество строк при variant=line  (default: 3)
      width   : Tailwind w-* класс  (default: w-full)
      height  : Tailwind h-* класс  (default: h-4 для line)
--}}
@props([
    'variant' => 'line',
    'lines'   => 3,
    'width'   => 'w-full',
    'height'  => null,
])

@php
$pulse = 'animate-pulse bg-white/10 rounded';

$defaultHeight = match($variant) {
    'circle' => 'h-10',
    'rect'   => 'h-32',
    'card'   => 'h-48',
    default  => 'h-4',
};
$heightClass = $height ?? $defaultHeight;
@endphp

@if($variant === 'line')
    <div {{ $attributes->merge(['class' => 'space-y-2']) }} aria-hidden="true" aria-label="Загрузка...">
        @for($i = 0; $i < $lines; $i++)
            <div class="{{ $pulse }} {{ $heightClass }} {{ $i === $lines - 1 ? 'w-3/4' : $width }}"></div>
        @endfor
    </div>

@elseif($variant === 'circle')
    <div
        {{ $attributes->merge(['class' => "$pulse rounded-full $heightClass $width"]) }}
        aria-hidden="true"
    ></div>

@elseif($variant === 'card')
    <div {{ $attributes->merge(['class' => "$pulse rounded-marketplace $heightClass $width"]) }} aria-hidden="true">
    </div>

@else
    <div
        {{ $attributes->merge(['class' => "$pulse $heightClass $width"]) }}
        aria-hidden="true"
    ></div>
@endif
