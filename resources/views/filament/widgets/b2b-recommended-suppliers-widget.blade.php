<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white uppercase">AI Recommendations & Leads</h2>
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                <span class="text-xs font-medium text-gray-500">AI Live Stream</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->getRecommendations() as $rec)
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-white/5 dark:border-white/10 hover:border-primary-500 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-0.5 text-[10px] bg-primary-100 text-primary-700 font-bold rounded uppercase dark:bg-primary-900/40 dark:text-primary-300">
                            {{ $rec['type'] }}
                        </span>
                        <div class="flex items-center gap-1">
                            <svg class="w-3 h-3 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <span class="text-xs font-bold">{{ $rec['score'] }}% Match</span>
                        </div>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-1">{{ $rec['name'] }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ $rec['insights'] }}</p>
                    <div class="flex items-center justify-between mt-auto">
                        <span class="text-[10px] text-gray-400 font-medium">Est. Distance: {{ $rec['distance'] }}</span>
                        <button class="text-xs font-bold text-primary-600 hover:text-primary-500 dark:text-primary-400">View Profile</button>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
