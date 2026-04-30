<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Мониторинг очередей Supermarket</h2>
            <x-filament::button wire:click="refresh">
                Обновить
            </x-filament::button>
        </div>

        @foreach($queueMetrics as $queueName => $data)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">{{ $queueName }}</h3>
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $data['health'] === 'healthy' ? 'bg-green-100 text-green-800' : ($data['health'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ $data['health'] }}
                    </span>
                </div>

                @if(!empty($data['issues']))
                    <div class="mb-4">
                        <p class="text-sm font-medium text-red-600">Проблемы:</p>
                        <ul class="list-disc list-inside text-sm text-gray-600">
                            @foreach($data['issues'] as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Ожидающих</p>
                        <p class="text-2xl font-bold">{{ $data['metrics']['pending'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">В обработке</p>
                        <p class="text-2xl font-bold">{{ $data['metrics']['processing'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Завершено (час)</p>
                        <p class="text-2xl font-bold">{{ $data['metrics']['completed_last_hour'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Ошибок</p>
                        <p class="text-2xl font-bold text-red-600">{{ $data['metrics']['failed'] }}</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-600">
                        Среднее время обработки: {{ number_format($data['metrics']['avg_processing_time_ms'], 0, ',', ' ') }} ms
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Обновлено: {{ \Carbon\Carbon::parse($data['metrics']['timestamp'])->format('d.m.Y H:i:s') }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
