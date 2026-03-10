<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white uppercase">B2B Demand Heatmap</h2>
            <select class="text-xs bg-transparent border-none focus:ring-0 text-gray-500">
                <option>Last 24 Hours</option>
                <option>Last 7 Days</option>
            </select>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($this->getHeatmapData() as $item)
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between items-end mb-1">
                        <span class="text-xs font-bold text-gray-500 uppercase">{{ $item['category'] }}</span>
                        <span class="text-xs font-bold text-green-500">{{ $item['growth'] }}</span>
                    </div>
                    <div class="w-full h-24 bg-gray-100 dark:bg-white/5 rounded-lg overflow-hidden relative border border-gray-100 dark:border-white/10">
                        <div 
                            class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-primary-500/50 to-primary-400/10 transition-all duration-1000"
                            style="height: {{ $item['intensity'] * 100 }}%"
                        ></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-black text-gray-900 dark:text-white opacity-40">{{ round($item['intensity'] * 100) }}%</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-6 p-4 bg-blue-50/50 rounded-xl border border-blue-100 dark:bg-blue-900/10 dark:border-blue-800">
             <p class="text-[11px] text-blue-800 dark:text-blue-300">
                <strong>AI Insight:</strong> High demand detected in "Pharma" category across Central regions. Recommend increasing stock by 15% for optimal margins.
             </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
