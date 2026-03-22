{{-- resources/views/livewire/channels/post-card.blade.php --}}
@if ($post)
<article class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-lg overflow-hidden
                transition-all hover:shadow-xl hover:border-white/30"
         aria-label="Пост «{{ $post->title ?: 'без заголовка' }}»">

    {{-- Шапка --}}
    <div class="flex items-center gap-3 p-4 pb-0">
        @if ($post->channel?->avatar_url)
            <a href="/channel/{{ $post->channel->slug }}">
                <img src="{{ $post->channel->avatar_url }}"
                     alt="{{ $post->channel->name }}"
                     class="w-10 h-10 rounded-full object-cover ring-1 ring-white/30 hover:ring-purple-400 transition">
            </a>
        @else
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500
                        flex items-center justify-center text-white font-bold text-sm shrink-0">
                {{ mb_substr($post->channel?->name ?? '?', 0, 1) }}
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <a href="/channel/{{ $post->channel?->slug }}"
               class="font-semibold text-white hover:text-purple-300 transition truncate block">
                {{ $post->channel?->name ?? 'Канал' }}
            </a>
            <div class="flex items-center gap-2 text-xs text-white/50">
                <span>{{ $post->published_at?->diffForHumans() }}</span>
                @if ($post->visibility !== 'all')
                    <span class="px-1.5 py-0.5 rounded bg-white/10 uppercase tracking-wide">
                        {{ $post->visibility }}
                    </span>
                @endif
                @if ($post->is_promo)
                    <span class="px-1.5 py-0.5 rounded bg-amber-500/30 text-amber-300 uppercase tracking-wide">
                        Промо
                    </span>
                @endif
            </div>
        </div>

        {{-- Меню автора --}}
        @if ($isOwner)
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="p-1.5 rounded-lg hover:bg-white/10 transition text-white/50 hover:text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false"
                     class="absolute right-0 top-8 bg-gray-900 border border-white/20 rounded-xl shadow-xl z-10 min-w-32 py-1">
                    <button wire:click="archivePost"
                            wire:confirm="Перенести пост в архив?"
                            class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-white/5 transition">
                        В архив
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Заголовок --}}
    @if ($post->title)
        <h3 class="px-4 pt-3 text-lg font-bold text-white">
            <a href="/channel/{{ $post->channel?->slug }}/posts/{{ $post->uuid }}"
               class="hover:text-purple-300 transition">
                {{ $post->title }}
            </a>
        </h3>
    @endif

    {{-- Текст --}}
    <div class="px-4 pt-2 pb-3 text-white/80 text-sm leading-relaxed prose prose-invert max-w-none prose-sm">
        {!! nl2br(e(mb_substr(strip_tags($post->content), 0, 500))) !!}
        @if (mb_strlen(strip_tags($post->content)) > 500)
            <a href="/channel/{{ $post->channel?->slug }}/posts/{{ $post->uuid }}"
               class="text-purple-400 hover:text-purple-300 transition ml-1">
                Читать далее →
            </a>
        @endif
    </div>

    {{-- Медиафайлы --}}
    @if ($post->media->isNotEmpty())
        <div class="px-4 pb-3">
            @if ($post->media->count() === 1 && $post->media->first()->isVideo())
                {{-- Одиночное видео --}}
                <video class="w-full rounded-xl bg-black max-h-80 object-contain"
                       controls
                       preload="metadata"
                       poster="{{ $post->media->first()->thumbnail_url }}">
                    <source src="{{ $post->media->first()->url }}" type="{{ $post->media->first()->mime_type }}">
                </video>
            @else
                {{-- Сетка фото --}}
                <div class="grid gap-1 rounded-xl overflow-hidden
                            {{ $post->media->count() === 1 ? 'grid-cols-1' : 'grid-cols-2' }}">
                    @foreach ($post->media->take(4) as $idx => $media)
                        <div class="relative {{ $idx === 0 && $post->media->count() > 2 ? 'col-span-2' : '' }}">
                            <img src="{{ $media->url }}"
                                 alt="{{ $media->alt_text ?? '' }}"
                                 class="w-full object-cover {{ $idx === 0 ? 'h-48' : 'h-28' }} rounded-sm">
                            @if ($idx === 3 && $post->media->count() > 4)
                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-sm">
                                    <span class="text-white text-2xl font-bold">+{{ $post->media->count() - 4 }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Опрос --}}
    @if ($post->poll)
        <div class="px-4 pb-3">
            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                <p class="text-white font-medium mb-3">{{ $post->poll['question'] ?? 'Опрос' }}</p>
                @foreach ($post->poll['options'] ?? [] as $option)
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-7 flex-1 bg-white/10 rounded-lg overflow-hidden">
                            <div class="h-full bg-purple-600/50 rounded-lg" style="width: 0%"></div>
                        </div>
                        <span class="text-sm text-white/70 w-24 truncate">{{ $option }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Футер: просмотры + реакции --}}
    <div class="flex items-center justify-between px-4 pb-4 pt-1 border-t border-white/10">
        {{-- Просмотры --}}
        <div class="flex items-center gap-1 text-xs text-white/40">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span>{{ number_format($post->views_count) }}</span>
        </div>

        {{-- Реакции --}}
        <livewire:channels.reaction-picker
            :post-id="$post->id"
            :post-uuid="$post->uuid"
            :key="'reactions-'.$post->id"
        />
    </div>

</article>
@endif
