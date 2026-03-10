<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Карта спроса (Heatmap)</x-slot>
        
        <div 
            x-data="{ points: {{ json_encode($this->getViewData()['demand_points']) }} }"
            class="h-96 w-full bg-gray-100 dark:bg-gray-900 rounded-lg flex items-center justify-center"
        >
            <div class="text-center p-4">
                <p class="text-sm text-gray-500">
                    [Интеграция Leaflet/Heatmap.js] <br>
                    Отображение {{ count($this->getViewData()['demand_points']) }} точек концентрации заказов.
                </p>
                <div class="mt-4 flex justify-center gap-2">
                    @foreach($this->getViewData()['demand_points'] as $point)
                         <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
