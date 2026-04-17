{{--
    x-ui-textarea — многострочное поле.
--}}
@props([
    'label' => null,
    'id'    => null,
    'error' => null,
    'hint'  => null,
    'rows'  => 4,
])

@php
$textareaId = $id ?? 'textarea-' . uniqid();
$errorId    = $textareaId . '-error';
$hasError   = !empty($error);
$textareaClasses = implode(' ', [
    'block w-full bg-white/5 border rounded-marketplace text-carbon-50 text-sm placeholder-carbon-500',
    'px-3 py-2.5 focus:outline-none focus:ring-2 focus:border-transparent transition-colors resize-y',
    $hasError
        ? 'border-marketplace-danger focus:ring-marketplace-danger'
        : 'border-white/10 focus:ring-marketplace-primary',
]);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
    @if($label)
        <label for="{{ $textareaId }}" class="block text-xs font-medium text-carbon-300">
            {{ $label }}
        </label>
    @endif

    <textarea
        id="{{ $textareaId }}"
        rows="{{ $rows }}"
        {{ $attributes->except(['class', 'id', 'rows']) }}
        class="{{ $textareaClasses }}"
        @if($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
    >{{ $slot }}</textarea>

    @if($hasError)
        <p id="{{ $errorId }}" class="text-xs text-marketplace-danger" role="alert">{{ $error }}</p>
    @elseif(!empty($hint))
        <p class="text-xs text-carbon-400">{{ $hint }}</p>
    @endif
</div>
