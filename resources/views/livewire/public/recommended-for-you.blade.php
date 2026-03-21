<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" wire:poll.10s>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Рекомендовано для вас
            <span class="text-xs font-normal text-gray-400 ml-2">Работает на OpenAI</span>
        </h2>
        <div class="flex gap-2">
            <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-xs text-gray-500">Живой ИИ 2026</span>
        </div>
    </div>

    @if($loading)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
            @for($i=0; $i<3; $i++)
                <div class="h-48 bg-gray-100 rounded-lg"></div>
            @endfor
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($recommendations as $hit)
                @php $doc = $hit['document'] ?? $hit; @endphp
                <div 
                    wire:click="trackInteraction('{{ $doc['entity_type'] ?? 'Unknown' }}', {{ $doc['id'] }}, '{{ $doc['category'] }}')"
                    class="group relative bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md cursor-pointer border border-transparent hover:border-indigo-100"
                >
                    <!-- Similarity Badge -->
                    @if(isset($hit['vector_distance']))
                        <div class="absolute top-3 right-3 z-10">
                            <span class="bg-indigo-600 text-white text-[10px] px-2 py-1 rounded-full font-bold shadow-sm">
                                AI: {{ round((1 - $hit['vector_distance']) * 100) }}% Совпадение
                            </span>
                        </div>
                    @endif

                    <div class="aspect-video bg-white rounded-md mb-3 flex items-center justify-center overflow-hidden">
                        @if(isset($doc['image_url']))
                            <img src="{{ $doc['image_url'] }}" alt="{{ $doc['name'] }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform">
                        @else
                            <span class="text-gray-300">Изображение</span>
                        @endif
                    </div>

                    <div class="space-y-1">
                        <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider">
                            {{ $doc['category'] }}
                        </div>
                        <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 truncate">
                            {{ $doc['name'] }}
                        </h3>
                        <p class="text-xs text-gray-500 line-clamp-2">
                            {{ $doc['description'] ?? 'Описание недоступно' }}
                        </p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-1 text-[11px] text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ isset($doc['geo_distance_meters']) ? round($doc['geo_distance_meters'] / 1000, 1) . ' km' : 'Рядом' }}
                        </div>
                        <div class="text-sm font-black text-gray-900">
                            {{ number_format($doc['price'] ?? 0, 0, '.', ' ') }} ₽
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-10 text-center text-gray-400">
                    AI подбирает лучшие предложения для вас...
                </div>
            @endforelse
        </div>
    @endif
</div>
