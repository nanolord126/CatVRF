{{--
    x-ui-select — select-поле.
    Пропсы: label, id, error, hint (аналогично x-ui-input)
--}}
@props([
    'label' => null,
    'id'    => null,
    'error' => null,
    'hint'  => null,
])

@php
$selectId  = $id ?? 'select-' . uniqid();
$errorId   = $selectId . '-error';
$hintId    = $selectId . '-hint';
$hasError  = !empty($error);
$ariaDesc  = implode(' ', array_filter([$hasError ? $errorId : null, !empty($hint) ? $hintId : null]));

$selectClasses = implode(' ', [
    'block w-full min-h-[44px] bg-white/5 border rounded-marketplace text-carbon-50 text-sm',
    'px-3 py-2.5 pr-8',
    'focus:outline-none focus:ring-2 focus:border-transparent transition-colors duration-150',
    'appearance-none bg-[url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'%3E%3Cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 9l-7 7-7-7\'/%3E%3C/svg%3E")] bg-no-repeat bg-[right_0.5rem_center] bg-[length:1.25rem]',
    $hasError
        ? 'border-marketplace-danger focus:ring-marketplace-danger'
        : 'border-white/10 focus:ring-marketplace-primary',
]);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
    @if($label)
        <label for="{{ $selectId }}" class="block text-xs font-medium text-carbon-300">
            {{ $label }}
        </label>
    @endif

    <select
        id="{{ $selectId }}"
        {{ $attributes->except(['class', 'id']) }}
        class="{{ $selectClasses }}"
        @if($ariaDesc) aria-describedby="{{ $ariaDesc }}" @endif
        @if($hasError) aria-invalid="true" @endif
    >
        {{ $slot }}
    </select>

    @if($hasError)
        <p id="{{ $errorId }}" class="text-xs text-marketplace-danger" role="alert">{{ $error }}</p>
    @elseif($hint)
        <p id="{{ $hintId }}" class="text-xs text-carbon-400">{{ $hint }}</p>
    @endif
</div>
