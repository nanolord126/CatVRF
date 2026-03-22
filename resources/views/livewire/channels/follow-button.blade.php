{{-- resources/views/livewire/channels/follow-button.blade.php --}}
<div>
    @if ($isSubscribed)
        <button
            wire:click="toggle"
            wire:loading.attr="disabled"
            class="group inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium
                   bg-white/15 text-white border border-white/30
                   hover:bg-red-500/30 hover:border-red-400/50 hover:text-red-300
                   transition-all duration-200 disabled:opacity-60">
            <span wire:loading.remove>
                <span class="group-hover:hidden">✓ Подписан</span>
                <span class="hidden group-hover:inline">Отписаться</span>
            </span>
            <span wire:loading class="flex items-center gap-1">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Загрузка...
            </span>
        </button>
    @else
        <button
            wire:click="toggle"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium
                   bg-purple-600 text-white border border-purple-500
                   hover:bg-purple-700 hover:border-purple-600
                   transition-all duration-200 disabled:opacity-60">
            <span wire:loading.remove>
                + Подписаться
            </span>
            <span wire:loading class="flex items-center gap-1">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Загрузка...
            </span>
        </button>
    @endif
</div>
