<div class="p-4 space-y-4">
    @forelse($history as $card)
        <div class="p-3 border rounded-lg bg-gray-50 dark:bg-gray-800">
            <div class="flex justify-between items-start mb-2">
                <span class="text-xs font-bold text-gray-400 capitalize">{{ $card->created_at->format('d.m.Y H:i') }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $card->status === 'open' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $card->status }}
                </span>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-500">Симптомы</p>
                    <p class="text-sm">{{ $card->symptoms }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-500">Диагноз</p>
                    <p class="text-sm font-semibold">{{ $card->diagnosis }}</p>
                </div>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="text-[10px] uppercase font-bold text-gray-500">Назначения</p>
                <p class="text-xs text-gray-600">{{ $card->prescription }}</p>
            </div>

            <div class="mt-2 text-right">
                <span class="text-[9px] italic text-gray-400">Врач: {{ $card->doctor->name }}</span>
            </div>
        </div>
    @empty
        <div class="text-center py-6 text-gray-500 italic">
            Медицинских записей пока нет
        </div>
    @endforelse
</div>
