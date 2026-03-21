<x-filament::widget>
    <x-filament::card class="p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold">CatVRF 2026 AI Рекомендации</h2>
            <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-semibold">
                AI Pipeline: Онлайн
            </span>
        </div>

        <div class="space-y-3">
            @forelse($getRecommendations() as $rec)
                @php $doc = $rec['document']; @endphp
                <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition duration-150 border border-gray-100 italic">
                    <div class="flex-grow">
                        <p class="text-sm font-semibold truncate">{{ $doc['name'] }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-tight">{{ $doc['category'] }} • Сходство: {{ round((1 - ($rec['vector_distance'] ?? 0.5)) * 100, 1) }}%</p>
                    </div>
                    <div class="px-2">
                         <a href="#" class="text-xs font-bold text-primary-600 hover:underline">Детали</a>
                    </div>
                </div>
            @empty
                <div class="p-4 text-center text-gray-400">
                    Нет персонализированных рекомендаций для контекста этого аккаунта.
                </div>
            @endforelse
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100">
            <p class="text-[10px] text-primary-400 font-mono tracking-tighter">
                Трассировка Экосистемы Активна • Correlation: {{ Context::get('correlation_id') ?? 'NO_TRACE' }}
            </p>
        </div>
    </x-filament::card>
</x-filament::widget>
