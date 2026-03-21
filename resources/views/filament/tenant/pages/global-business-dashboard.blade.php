<x-filament-panels::page>
    <div class="space-y-6">
        <header class="flex items-center justify-between border-b pb-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="p-2 bg-primary-600 text-white rounded-lg mr-3">🌐</span>
                    Глобальная экосистема 2026 Admin
                </h1>
                <p class="text-sm text-gray-500">Единое управление всеми вертикалями бизнеса: Такси, Еда, Клиники, B2B, HR.</p>
            </div>
            <div class="flex items-center space-x-2">
                 <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">
                    Точность ИИ-прогноза: {{ $this->forecast['confidence_score'] * 100 }}%
                </span>
            </div>
        </header>

        {{-- Секция 1: Прогнозы AI по вертикалям --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->forecast['ПРОГНОЗ_revenue'] as $сектор => $amount)
                @if($сектор !== 'total')
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 transition-all hover:scale-105">
                        <div class="flex items-center justify-between mb-2">
                             <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ $сектор }} сектор</span>
                             <span class="p-1 px-2 bg-green-50 text-green-700 rounded-lg text-[10px] font-black">ПРОГНОЗ</span>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-gray-100">
                            ${{ number_format($amount, 0) }}
                        </h3>
                        <div class="mt-4 h-1 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600" style="width: {{ rand(40, 95) }}%"></div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Итого --}}
            <div class="bg-primary-900 p-5 rounded-3xl shadow-xl border border-primary-800 text-white relative overflow-hidden group">
                 <div class="relative z-10">
                    <span class="text-xs font-bold text-primary-300 uppercase tracking-widest">Total Forecast</span>
                    <h3 class="mt-2 text-3xl font-black">${{ number_format($this->forecast['ПРОГНОЗ_revenue']['total'], 0) }}</h3>
                    <p class="text-[10px] font-bold text-green-400 mt-1 uppercase">Surpassing Q1 targets by +12%</p>
                 </div>
                 <div class="absolute -right-4 -bottom-4 opacity-10 transform group-hover:scale-150 transition-transform duration-700">
                      <svg class="h-32 w-32" fill="currentColor" viewBox="0 0 24 24"><path d="M11 2H13V4H11V2M15 4.6L16.4 3.2L17.8 4.6L16.4 6L15 4.6M11 22H13V24H11V22M19.4 15L20.8 16.4L19.4 17.8L18 16.4L19.4 15M4.6 15L6 16.4L4.6 17.8L3.2 16.4L4.6 15M20 11V13H22V11H20M2 11V13H4V11H2M17.8 19.4L16.4 20.8L15 19.4L16.4 18L17.8 19.4M6.4 4.6L7.8 6L6.4 7.4L5 6L6.4 4.6M12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7Z"/></svg>
                 </div>
            </div>
        </div>

        {{-- Секция 2: Рекомендации AI AI-Агента --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                 <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h4 class="text-lg font-bold flex items-center text-gray-900 dark:text-gray-100 mb-6">
                        <span class="mr-2">🦾</span> AI Business Recommendations
                    </h4>
                    <div class="space-y-4">
                        @foreach($this->forecast['recommendations'] as $rec)
                            <div class="flex items-start bg-gray-50 dark:bg-gray-900 p-4 rounded-2xl border-l-4 border-primary-500">
                                <div class="p-2 bg-white dark:bg-gray-800 rounded-full shadow-sm text-primary-500 mr-4">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 leading-tight">{{ $rec }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-widest">Confidence: 94%</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                 </div>

                 <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 min-h-[400px]">
                      <h4 class="text-lg font-bold flex items-center text-gray-900 dark:text-gray-100 mb-6">
                        <span class="mr-2">🗺️</span> Dynamic Heatmap & Demand Forecast
                      </h4>
                      <div class="flex items-center justify-center p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl">
                           <div>
                                <div class="animate-pulse bg-primary-100 p-4 rounded-full inline-block mb-4 text-primary-600">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <h5 class="text-gray-400 font-bold uppercase tracking-widest text-xs">Loading Live Spatial Data...</h5>
                                <p class="text-[10px] text-gray-500 mt-1">Connecting to Global Logistics 2026 Grid</p>
                           </div>
                      </div>
                 </div>
            </div>

            <div class="space-y-6">
                {{-- Hotspots Card --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <h4 class="text-lg font-bold flex items-center text-gray-900 dark:text-gray-100 mb-6">
                        <span class="mr-2">🔥</span> Market Hotspots
                    </h4>
                    <div class="space-y-6">
                        <div>
                             <p class="text-[10px] font-black uppercase tracking-widest text-red-500">Peak Demand</p>
                             <p class="text-sm font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $this->forecast['hotspots']['demand_increase'] }}</p>
                             <div class="mt-2 text-[10px] bg-red-50 text-red-600 p-2 rounded-lg font-bold uppercase tracking-tight">AI Warning: Short on staff!</div>
                        </div>
                        <div>
                             <p class="text-[10px] font-black uppercase tracking-widest text-orange-500">Business Risk</p>
                             <p class="text-sm font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $this->forecast['hotspots']['risk'] }}</p>
                             <div class="mt-2 text-[10px] bg-orange-50 text-orange-600 p-2 rounded-lg font-bold uppercase tracking-tight">Action needed in B2B Panel</div>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions 2026 --}}
                <div class="bg-gray-950 p-6 rounded-3xl shadow-2xl text-white relative overflow-hidden">
                    <h4 class="text-lg font-bold flex items-center mb-6">
                        <span class="mr-2">⚡</span> Fast Lane Management
                    </h4>
                    <div class="space-y-3 relative z-10">
                        <button class="w-full text-left bg-gray-900 hover:bg-primary-600 transition-all p-4 rounded-2xl flex items-center justify-between group">
                            <span class="text-xs font-bold uppercase tracking-widest">Execute AI Procurement</span>
                            <span class="text-xl group-hover:scale-125 transition-transform">🤖</span>
                        </button>
                        <button class="w-full text-left bg-gray-900 hover:bg-green-600 transition-all p-4 rounded-2xl flex items-center justify-between group">
                            <span class="text-xs font-bold uppercase tracking-widest">Open HR Exchange Board</span>
                            <span class="text-xl group-hover:scale-125 transition-transform">🤝</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
