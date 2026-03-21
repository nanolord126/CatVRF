<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- AI Security KPI -->
        <x-filament::section>
            <x-slot name="heading">ИИ-Репутация и Обнаружение Фрода (Поток данных)</x-slot>
            <x-slot name="description">Мониторинг подозрительной активности, выявленной ИИ по всем вертикалям.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>

        <!-- API Gateway (Partner Access) -->
        <x-filament::section>
            <x-slot name="heading">API-шлюз внешних партнеров 2026</x-slot>
            <x-slot name="description">Управление внешним доступом и интеграциями сторонних систем (Такси, Агрегаторы еды).</x-slot>

            <div class="space-y-4">
                @foreach($this->getViewData()['partners'] as $partner)
                    <div class="p-4 border rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-lg text-primary-600">{{ $partner->partner_name }}</span>
                            <span class="badge {{ $partner->is_Активен ? 'badge-success' : 'badge-danger' }}">
                                {{ $partner->is_Активен ? 'Активен' : 'Отключен' }}
                            </span>
                        </div>
                        <div class="text-sm font-mono opacity-70">Ключ: {{ substr($partner->api_key, 0, 8) }}...</div>
                        <div class="mt-2 text-xs opacity-75">Разрешенные доступы: {{ implode(', ', json_decode($partner->allowed_scopes, true) ?? []) }}</div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-8">
                <h4 class="font-semibold mb-2">Endpoint Performance Metrics (Avg Latency)</h4>
                <div class="space-y-2">
                    @foreach($this->getViewData()['apiUsage'] as $usage)
                        <div class="flex justify-between text-xs p-2 border-b dark:border-gray-700">
                            <span>{{ $usage->endpoint }}</span>
                            <span>{{ number_format($usage->avg_latency, 2) }}ms</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
