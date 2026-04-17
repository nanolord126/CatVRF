{{-- livewire/shared/wallet-balance.blade.php --}}
<div
    wire:poll.60s="refreshBalance"
    class="flex items-center gap-2"
    aria-live="polite"
    aria-label="Баланс кошелька"
>
    <a
        href="{{ route('user.wallet') }}"
        class="flex items-center gap-1.5 px-3 py-2 rounded-marketplace text-carbon-300 hover:text-white hover:bg-white/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-marketplace-primary"
    >
        <svg class="w-4 h-4 shrink-0 text-organic-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <span class="text-xs font-semibold tabular-nums text-carbon-100">
            {{ number_format($balanceKop / 100, 0, '.', ' ') }}&nbsp;₽
        </span>
    </a>

    @if($bonusKop > 0)
        <span
            class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-500/15 text-amber-400 text-xs font-medium ring-1 ring-amber-500/20 tabular-nums"
            title="Бонусы"
        >
            ✦ {{ number_format($bonusKop / 100, 0, '.', ' ') }}&nbsp;₽
        </span>
    @endif

    @if($isB2B)
        <x-ui-badge variant="info">B2B</x-ui-badge>
    @endif
</div>
