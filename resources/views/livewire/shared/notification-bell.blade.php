{{-- livewire/shared/notification-bell.blade.php --}}
<div
    x-data="{ open: $wire.entangle('isOpen') }"
    class="relative"
    @click.away="open = false"
    @keydown.escape="open = false"
>
    {{-- Кнопка-колокол --}}
    <button
        @click="open = !open"
        class="relative p-2 rounded-marketplace text-carbon-300 hover:text-white hover:bg-white/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-marketplace-primary"
        aria-label="Уведомления{{ $unreadCount > 0 ? ', непрочитанных: ' . $unreadCount : '' }}"
        :aria-expanded="open"
        aria-haspopup="true"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        @if($unreadCount > 0)
            <span
                class="absolute top-0.5 right-0.5 min-w-[0.9rem] h-[0.9rem] bg-marketplace-danger text-white text-[9px] font-bold rounded-full flex items-center justify-center px-1"
                aria-hidden="true"
            >{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    {{-- Дропдаун --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-full right-0 mt-2 w-80 bg-carbon-950/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-modal overflow-hidden z-50"
        role="menu"
        aria-label="Список уведомлений"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/5">
            <span class="text-sm font-semibold text-carbon-100">Уведомления</span>
            @if($unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    class="text-xs text-neuro-indigo-400 hover:text-neuro-indigo-300 transition-colors focus:outline-none"
                >
                    Прочитать все
                </button>
            @endif
        </div>

        <ul class="divide-y divide-white/5 max-h-80 overflow-y-auto">
            @forelse($notifications as $notif)
                <li
                    class="flex gap-3 px-4 py-3 hover:bg-white/5 transition-colors {{ $notif['read'] ? 'opacity-60' : '' }}"
                    role="menuitem"
                >
                    <div class="mt-0.5 shrink-0">
                        @if(!$notif['read'])
                            <span class="block w-1.5 h-1.5 mt-1.5 rounded-full bg-marketplace-primary" aria-hidden="true"></span>
                        @else
                            <span class="block w-1.5 h-1.5 mt-1.5"></span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-carbon-200 leading-relaxed">{{ $notif['message'] }}</p>
                        <p class="text-[10px] text-carbon-500 mt-0.5">{{ $notif['created'] }}</p>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-carbon-500">
                    Нет уведомлений
                </li>
            @endforelse
        </ul>

        <div class="px-4 py-2 border-t border-white/5">
            <a
                href="{{ route('user.notifications') }}"
                class="text-xs text-neuro-indigo-400 hover:text-neuro-indigo-300 transition-colors"
                @click="open = false"
            >
                Все уведомления →
            </a>
        </div>
    </div>
</div>
