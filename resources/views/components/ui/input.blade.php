{{--
    x-ui-input — текстовое поле.
    Пропсы:
      label       : string       — видимый label
      id          : string       — id поля (auto-linked к label)
      error       : string|null  — текст ошибки
      hint        : string|null  — подсказка под полем
      leadingIcon : bool         — место под иконку слева
      type        : text|email|password|tel|number|...  (default: text)
--}}
@props([
    'label'       => null,
    'id'          => null,
    'error'       => null,
    'hint'        => null,
    'leadingIcon' => false,
    'type'        => 'text',
])

@php
$inputId   = $id ?? 'input-' . uniqid();
$errorId   = $inputId . '-error';
$hintId    = $inputId . '-hint';
$hasError  = !empty($error);
$ariaDesc  = implode(' ', array_filter([$hasError ? $errorId : null, !empty($hint) ? $hintId : null]));

$inputClasses = implode(' ', [
    'block w-full min-h-[44px] bg-white/5 border rounded-marketplace text-carbon-50 text-sm placeholder-carbon-500',
    'focus:outline-none focus:ring-2 focus:border-transparent transition-colors duration-150',
    $leadingIcon ? 'pl-10 pr-3 py-2.5' : 'px-3 py-2.5',
    $hasError
        ? 'border-marketplace-danger focus:ring-marketplace-danger'
        : 'border-white/10 focus:ring-marketplace-primary',
]);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
    @if($label)
        <label for="{{ $inputId }}" class="block text-xs font-medium text-carbon-300">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if($leadingIcon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-carbon-400" aria-hidden="true">
                {{ $leadingIcon }}
            </div>
        @endif

        <input
            id="{{ $inputId }}"
            type="{{ $type }}"
            {{ $attributes->except(['class', 'id', 'type']) }}
            class="{{ $inputClasses }}"
            @if($ariaDesc) aria-describedby="{{ $ariaDesc }}" @endif
            @if($hasError) aria-invalid="true" @endif
        />
    </div>

    @if($hasError)
        <p id="{{ $errorId }}" class="text-xs text-marketplace-danger" role="alert">
            {{ $error }}
        </p>
    @elseif($hint)
        <p id="{{ $hintId }}" class="text-xs text-carbon-400">{{ $hint }}</p>
    @endif
</div>
