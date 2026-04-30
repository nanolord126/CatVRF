<x-filament-panels::page>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    Дашборд магазина
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ auth()->user()->tenant->shop_name ?? 'Ваш магазин' }}
                </p>
            </div>
            
            <div class="flex items-center gap-4">
                <x-filament::select
                    wire:model.live="period"
                    :options="$this->getPeriodOptions()"
                    label="Период"
                />
                
                <x-filament::button
                    icon="heroicon-o-arrow-down-tray"
                    color="gray"
                >
                    Экспорт
                </x-filament::button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- AI Insights Section --}}
        @if(!empty($this->getInsights()))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    🤖 AI Insights
                </h3>
                <span class="text-xs text-gray-500">
                    Обновлено: {{ now()->format('H:i') }}
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->getInsights() as $insight)
                <div class="p-4 rounded-lg border-2 {{ $insight['impact_color'] === 'red' ? 'border-red-200 bg-red-50' : ($insight['impact_color'] === 'yellow' ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200') }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-semibold uppercase tracking-wider px-2 py-1 rounded-full bg-{{ $insight['impact_color'] }}-100 text-{{ $insight['impact_color'] }}-700">
                                    {{ $insight['impact_label'] }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $insight['type_label'] }}
                                </span>
                            </div>
                            
                            <h4 class="font-semibold text-gray-900 mb-1">
                                {{ $insight['title'] }}
                            </h4>
                            
                            <p class="text-sm text-gray-600">
                                {{ $insight['description'] }}
                            </p>
                            
                            @if($insight['value'])
                            <div class="mt-2 text-sm font-medium text-gray-700">
                                {{ number_format($insight['value'], 2) }}
                            </div>
                            @endif
                        </div>
                        
                        @if($insight['actionable'])
                        <x-filament::button
                            size="sm"
                            color="primary"
                        >
                            Выполнить
                        </x-filament::button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Main Widgets --}}
        <div>
            {{ $this->getHeaderWidgets() }}
        </div>

        {{-- Charts and Breakdowns --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Топ товаров
                </h3>
                {{ $this->getFooterWidgets()[0] ?? '' }}
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Разбивка по категориям
                </h3>
                {{ $this->getFooterWidgets()[1] ?? '' }}
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Быстрые действия
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-filament::button
                    icon="heroicon-o-plus"
                    url="{{ route('filament.admin.resources.products.create') }}"
                >
                    Добавить товар
                </x-filament::button>
                
                <x-filament::button
                    icon="heroicon-o-tag"
                    url="{{ route('filament.admin.resources.promotions.create') }}"
                >
                    Создать акцию
                </x-filament::button>
                
                <x-filament::button
                    icon="heroicon-o-clock"
                    url="{{ route('filament.admin.resources.subscriptions.index') }}"
                >
                    Подписки
                </x-filament::button>
                
                <x-filament::button
                    icon="heroicon-o-banknotes"
                    url="{{ route('filament.admin.resources.payments.index') }}"
                >
                    Выплаты
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
