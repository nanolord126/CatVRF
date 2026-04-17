{{--
    x-ui-avatar — аватар пользователя / сущности.
    Пропсы:
      src      : string|null   — URL изображения
      name     : string        — имя для fallback-инициалов и alt-текста
      size     : xs|sm|md|lg|xl  (default: md)
      offline  : bool          — grayscale-режим (для оффлайн-состояния)
--}}
@props([
    'src'     => null,
    'name'    => '',
    'size'    => 'md',
    'offline' => false,
])

@php
$sizeClasses = match($size) {
    'xs'  => 'w-6 h-6 text-xs',
    'sm'  => 'w-8 h-8 text-xs',
    'lg'  => 'w-12 h-12 text-base',
    'xl'  => 'w-16 h-16 text-lg',
    default => 'w-10 h-10 text-sm',
};

// Первые две буквы имени как fallback
$initials = mb_strtoupper(
    collect(explode(' ', trim($name)))
        ->take(2)
        ->map(fn($part) => mb_substr($part, 0, 1))
        ->implode('')
) ?: '?';

// Детерминированный цвет фона по имени
$colorIndex = array_sum(array_map('ord', str_split($name ?: 'X'))) % 6;
$bgColors   = ['bg-neuro-indigo-600', 'bg-organic-teal-600', 'bg-purple-600', 'bg-rose-600', 'bg-amber-600', 'bg-blue-600'];
$bgColor    = $bgColors[$colorIndex];

$filterClass = $offline ? 'grayscale opacity-60' : '';
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center shrink-0 rounded-full overflow-hidden $sizeClasses $filterClass"]) }}
    role="img"
    aria-label="{{ $name ?: 'Аватар' }}"
>
    @if($src)
        <img
            src="{{ $src }}"
            alt="{{ $name }}"
            class="w-full h-full object-cover"
            loading="lazy"
        />
    @else
        <span class="flex items-center justify-center w-full h-full {{ $bgColor }} font-semibold text-white leading-none select-none">
            {{ $initials }}
        </span>
    @endif
</span>
