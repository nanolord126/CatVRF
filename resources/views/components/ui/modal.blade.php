{{--
    x-ui-modal — модальное окно.
    Требует Alpine.js. Управляется через x-data / x-show на родителе.
    Пропсы:
      id      : string  — уникальный ID модалки (для aria-labelledby)
      title   : string
      maxWidth: sm|md|lg|xl|2xl  (default: md)
    Слот:
      $trigger — кнопка/триггер (опционально, внутри x-data)
      $slot    — содержимое
      $footer  — кнопки действий (опционально)

    Использование:
      <div x-data="{ open: false }">
          <x-ui-button @click="open = true">Открыть</x-ui-button>
          <x-ui-modal title="Заголовок" id="my-modal" x-show="open" @close="open = false">
              Содержимое
          </x-ui-modal>
      </div>
--}}
@props([
    'id'       => 'modal-' . uniqid(),
    'title'    => '',
    'maxWidth' => 'md',
    'footer'   => null,
])

@php
$widthClasses = match($maxWidth) {
    'sm'  => 'max-w-sm',
    'lg'  => 'max-w-lg',
    'xl'  => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    default => 'max-w-md',
};
@endphp

<div
    {{ $attributes }}
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    x-cloak
    x-trap="$el.style.display !== 'none'"
    @keydown.escape.window="$dispatch('close')"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    {{-- Overlay --}}
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        @click="$dispatch('close')"
        aria-hidden="true"
    ></div>

    {{-- Dialog panel --}}
    <div
        class="relative w-full {{ $widthClasses }} bg-carbon-950/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-modal overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
            <h2 id="{{ $id }}-title" class="text-base font-semibold text-carbon-50">
                {{ $title }}
            </h2>
            <button
                @click="$dispatch('close')"
                class="p-1.5 rounded-lg text-carbon-400 hover:text-carbon-50 hover:bg-white/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-marketplace-primary"
                aria-label="Закрыть модальное окно"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-4 text-carbon-200 text-sm">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @if(isset($footer))
            <div class="px-6 py-4 border-t border-white/5 flex items-center justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
