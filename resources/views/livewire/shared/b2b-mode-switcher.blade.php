{{-- livewire/shared/b2b-mode-switcher.blade.php --}}
@if($hasBusinessCard)
<div
    x-data="{ open: false }"
    class="relative"
    @click.away="open = false"
    @keydown.escape="open = false"
>
    <button
        @click="open = !open"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-marketplace border transition-all text-xs font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-marketplace-primary
            {{ $isB2B
                ? 'bg-neuro-indigo-600/20 border-neuro-indigo-500/40 text-neuro-indigo-300 hover:bg-neuro-indigo-600/30'
                : 'bg-white/5 border-white/10 text-carbon-300 hover:bg-white/10 hover:text-white' }}"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="Режим: {{ $isB2B ? 'B2B' : 'B2C' }}, переключить"
    >
        @if($isB2B)
            <span class="w-1.5 h-1.5 rounded-full bg-neuro-indigo-400 shrink-0" aria-hidden="true"></span>
            B2B
        @else
            <span class="w-1.5 h-1.5 rounded-full bg-carbon-500 shrink-0" aria-hidden="true"></span>
            B2C
        @endif
        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="absolute top-full left-0 mt-2 w-56 bg-carbon-950/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-modal p-1 z-50"
        role="menu"
        aria-label="Выбор режима"
    >
        {{-- B2C режим --}}
        <button
            wire:click="switchToB2C"
            @click="open = false"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-colors hover:bg-white/10 focus:outline-none
                {{ !$isB2B ? 'text-white font-semibold' : 'text-carbon-300' }}"
            role="menuitem"
        >
            <span class="w-5 h-5 rounded-full bg-carbon-700 flex items-center justify-center shrink-0 text-[9px] font-bold text-carbon-200" aria-hidden="true">B2C</span>
            <div class="text-left">
                <div class="font-medium">Частное лицо</div>
                <div class="text-[10px] text-carbon-500">Розничные цены</div>
            </div>
            @if(!$isB2B)
                <svg class="w-3.5 h-3.5 text-marketplace-success ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            @endif
        </button>

        <div class="h-px bg-white/5 my-1" role="separator"></div>

        {{-- B2B группы --}}
        @foreach($businessGroups as $group)
            <button
                wire:click="switchToB2B({{ $group['id'] }})"
                @click="open = false"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-colors hover:bg-white/10 focus:outline-none
                    {{ $isB2B && $activeGroupId === $group['id'] ? 'text-white font-semibold' : 'text-carbon-300' }}"
                role="menuitem"
            >
                <span class="w-5 h-5 rounded-full bg-neuro-indigo-700 flex items-center justify-center shrink-0 text-[9px] font-bold text-neuro-indigo-200" aria-hidden="true">B2B</span>
                <div class="text-left min-w-0">
                    <div class="font-medium truncate">{{ $group['name'] }}</div>
                    <div class="text-[10px] text-carbon-500">ИНН {{ $group['inn'] }}</div>
                </div>
                @if($isB2B && $activeGroupId === $group['id'])
                    <svg class="w-3.5 h-3.5 text-marketplace-success ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                @endif
            </button>
        @endforeach
    </div>
</div>
@endif
