{{-- livewire/shared/ai-constructor-button.blade.php --}}
<button
    wire:click="open"
    wire:loading.attr="disabled"
    wire:loading.class="opacity-60 cursor-wait"
    @class([
        'inline-flex items-center gap-2 font-semibold rounded-marketplace transition-all',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-marketplace-secondary',
        'min-h-[44px] px-4',
        // size
        'text-xs px-3 py-2' => $size === 'sm',
        'text-sm px-4 py-2.5' => $size === 'md',
        'text-base px-5 py-3' => $size === 'lg',
        // active state
        'bg-gradient-to-r from-neuro-indigo-600 to-organic-teal-500 text-white hover:opacity-90 shadow-[0_0_20px_rgba(99,102,241,0.3)]' => $canUse,
        // disabled state
        'bg-white/5 border border-white/10 text-carbon-500 cursor-not-allowed' => !$canUse,
    ])
    @disabled(!$canUse)
    aria-label="{{ $label }} AI-конструктор"
>
    {{-- Sparkles icon --}}
    <span wire:loading.remove wire:target="open" aria-hidden="true">
        <svg class="{{ match($size) { 'sm' => 'w-3.5 h-3.5', 'lg' => 'w-5 h-5', default => 'w-4 h-4' } }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
    </span>

    {{-- Loading spinner --}}
    <span wire:loading wire:target="open" aria-hidden="true">
        <svg class="{{ match($size) { 'sm' => 'w-3.5 h-3.5', 'lg' => 'w-5 h-5', default => 'w-4 h-4' } }} animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
    </span>

    <span>{{ $label }}</span>
</button>
