{{-- resources/views/livewire/channels/reaction-picker.blade.php --}}
<div class="flex items-center gap-1" x-data="{ showPicker: @entangle('showPicker') }">

    {{-- Показанные реакции --}}
    @foreach ($reactions as $reaction)
        <button
            wire:click="react('{{ $reaction['emoji'] }}')"
            class="flex items-center gap-1 px-2 py-0.5 rounded-full text-sm transition-all
                   {{ ($myReactions[$reaction['emoji']] ?? false)
                        ? 'bg-purple-600/60 text-white ring-1 ring-purple-400'
                        : 'bg-white/10 text-white/70 hover:bg-white/20' }}"
            title="{{ $reaction['name'] }}"
            aria-label="{{ $reaction['name'] }}: {{ $reaction['count'] }}">
            <span>{{ $reaction['emoji'] }}</span>
            <span class="text-xs font-medium">{{ $reaction['count'] }}</span>
        </button>
    @endforeach

    {{-- Кнопка открыть пикер --}}
    <div class="relative">
        <button
            @click="showPicker = !showPicker"
            class="flex items-center justify-center w-7 h-7 rounded-full bg-white/10
                   hover:bg-white/20 text-white/60 hover:text-white transition"
            title="Добавить реакцию"
            aria-label="Добавить реакцию">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>

        {{-- Панель со всеми разрешёнными emoji --}}
        <div
            x-show="showPicker"
            @click.away="showPicker = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute bottom-9 right-0 bg-gray-900/95 backdrop-blur-sm
                   border border-white/20 rounded-2xl shadow-2xl p-2 z-20
                   flex flex-wrap gap-1 w-48">
            @foreach ($allowed as $emoji => $name)
                <button
                    wire:click="react('{{ $emoji }}')"
                    @click="showPicker = false"
                    class="flex flex-col items-center p-1.5 rounded-xl hover:bg-white/10 transition
                           text-center group w-14"
                    title="{{ $name }}">
                    <span class="text-2xl group-hover:scale-125 transition-transform">{{ $emoji }}</span>
                    <span class="text-xs text-white/40 truncate w-full text-center">{{ $name }}</span>
                </button>
            @endforeach
        </div>
    </div>

</div>
