<x-filament-panels::page>
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($stages as $stage)
            <div class="flex-shrink-0 w-80 bg-gray-50 dark:bg-gray-900 rounded-xl p-4 min-h-[600px] border border-gray-200 dark:border-gray-800">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-sm uppercase tracking-wider" style="color: {{ $stage->color }}">
                        {{ $stage->name }}
                    </h3>
                    <span class="text-xs bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded-full">
                        {{ $stage->deals->count() }}
                    </span>
                </div>

                <div class="space-y-3 kanban-container" data-stage-id="{{ $stage->id }}">
                    @foreach($stage->deals as $deal)
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 cursor-move transition-transform active:scale-95 glassmorphism">
                            <h4 class="font-semibold text-sm mb-2">{{ $deal->name }}</h4>
                            <div class="flex justify-between items-center text-xs text-gray-500">
                                <span>{{ number_format($deal->amount, 0, '.', ' ') }} ₽</span>
                                <span class="flex items-center gap-1">
                                    <x-heroicon-m-user class="w-3 h-3"/>
                                    {{ $deal->owner?->name }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .glassmorphism {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</x-filament-panels::page>
