{{-- resources/views/livewire/channels/business-news-feed.blade.php --}}
<div class="space-y-4" x-data>

    {{-- Заголовок канала (если показываем конкретный канал) --}}
    @if ($channel)
        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 shadow-lg">
            <div class="flex items-center gap-4">
                @if ($channel->avatar_url)
                    <img src="{{ $channel->avatar_url }}"
                         alt="{{ $channel->name }}"
                         class="w-16 h-16 rounded-full object-cover ring-2 ring-white/30">
                @else
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-500
                                flex items-center justify-center text-white text-2xl font-bold">
                        {{ mb_substr($channel->name, 0, 1) }}
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-white truncate">{{ $channel->name }}</h2>
                    @if ($channel->description)
                        <p class="text-white/60 text-sm mt-1 line-clamp-2">{{ $channel->description }}</p>
                    @endif
                </div>

                @auth
                    <livewire:channels.follow-button
                        :channel-slug="$channel->slug"
                        :channel-name="$channel->name"
                        :key="'follow-'.$channel->slug"
                    />
                @endauth
            </div>

            {{-- Обложка --}}
            @if ($channel->cover_url)
                <img src="{{ $channel->cover_url }}"
                     alt="Обложка {{ $channel->name }}"
                     class="w-full h-40 object-cover rounded-xl mt-4">
            @endif
        </div>
    @endif

    {{-- Фильтр видимости --}}
    <div class="flex gap-2">
        @foreach (['all' => 'Все', 'b2c' => 'Для клиентов', 'b2b' => 'Для бизнеса'] as $key => $label)
            <button
                wire:click="setAudience('{{ $key }}')"
                class="px-4 py-1.5 rounded-full text-sm font-medium transition-all
                       {{ $audience === $key
                            ? 'bg-purple-600 text-white shadow-md'
                            : 'bg-white/10 text-white/70 hover:bg-white/20' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Список постов --}}
    @forelse ($posts as $post)
        <livewire:channels.post-card
            :post-id="$post->id"
            :key="'post-'.$post->id"
        />
    @empty
        <div class="bg-white/5 rounded-2xl p-12 text-center">
            <div class="text-5xl mb-4">📭</div>
            <p class="text-white/50 text-lg">Пока нет опубликованных постов</p>
            @if ($channel && auth()->check() && auth()->user()->current_tenant_id === $channel->tenant_id)
                <a href="{{ route('filament.tenant.resources.channels.posts.create') }}"
                   class="mt-4 inline-block px-6 py-2 bg-purple-600 text-white rounded-full text-sm hover:bg-purple-700 transition">
                    Написать первый пост
                </a>
            @endif
        </div>
    @endforelse

    {{-- Пагинация --}}
    @if ($posts->hasPages())
        <div class="mt-4">
            {{ $posts->links() }}
        </div>
    @endif

    {{-- Loading indicator --}}
    <div wire:loading.delay class="fixed bottom-4 right-4 bg-purple-600 text-white px-4 py-2 rounded-full shadow-lg text-sm">
        Загрузка...
    </div>
</div>
