<div class="px-2 pb-2">
    @php
        $history = \App\Models\MedicalCard::where('patient_type', 'HUMAN')
            ->where('patient_id', $getRecord()->id)
            ->with('doctor')
            ->orderBy('created_at', 'desc')
            ->get();
    @endphp

    @forelse($history as $card)
        <div class="mb-3 p-3 border rounded-lg bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-2">
                <span class="text-[10px] font-bold text-gray-400 capitalize">{{ $card->created_at->format('d.m.Y H:i') }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $card->status === 'open' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $card->status }}
                </span>
            </div>
            
            <div class="space-y-2">
                <div>
                    <span class="text-[9px] uppercase font-bold text-gray-500 block">Диагноз</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $card->diagnosis }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <span class="text-[9px] uppercase font-bold text-gray-500 block">Симптомы</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($card->symptoms, 50) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] uppercase font-bold text-gray-500 block">Врач</span>
                        <span class="text-xs italic text-gray-600 dark:text-gray-400">{{ $card->doctor->name }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-gray-500 italic text-sm border-2 border-dashed rounded-lg">
            Личных обращений в клиники не зафиксировано
        </div>
    @endforelse
</div>
