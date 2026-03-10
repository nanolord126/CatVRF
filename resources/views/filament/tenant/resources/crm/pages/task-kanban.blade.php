<x-filament-panels::page>
    <div class="flex gap-4 overflow-x-auto pb-4">
        @php
            $tasks = \App\Models\CRM\Task::all()->groupBy('status');
            $statuses = [
                'new' => 'Новая',
                'in_progress' => 'В работе',
                'completed' => 'Завершена',
            ];
        @endphp

        @foreach($statuses as $statusKey => $statusLabel)
            <div class="flex-shrink-0 w-80 bg-gray-50 dark:bg-gray-900 rounded-xl p-4 min-h-[600px] border border-gray-200 dark:border-gray-800">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-sm uppercase tracking-wider text-gray-600 dark:text-gray-400">
                        {{ $statusLabel }}
                    </h3>
                    <span class="text-xs bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded-full">
                        {{ ($tasks[$statusKey] ?? collect())->count() }}
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach($tasks[$statusKey] ?? [] as $task)
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                            <h4 class="font-semibold text-sm mb-2 text-gray-900 dark:text-white">{{ $task->title }}</h4>
                            <div class="flex justify-between items-center text-xs text-gray-500">
                                <span>{{ $task->due_at?->format('d.m.Y') ?? 'Нет срока' }}</span>
                                <span class="flex items-center gap-1">
                                    <x-heroicon-m-user class="w-3 h-3"/>
                                    {{ $task->responsible?->name }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
