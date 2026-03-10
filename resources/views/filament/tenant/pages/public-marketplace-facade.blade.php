<x-filament-panels::page>
    <div class="space-y-8 pb-12">
        {{-- Hero: Умный поиск 2026 --}}
        <section class="relative bg-gray-900 rounded-3xl p-12 overflow-hidden shadow-2xl border border-gray-800">
            <div class="relative z-10 max-w-2xl mx-auto text-center">
                <h1 class="text-4xl font-black text-white leading-tight">
                    Единый Фасад Маркетплейса <span class="text-primary-500">2026</span>
                </h1>
                <p class="mt-4 text-gray-400 text-lg">
                    Умный гибридный поиск по всем вертикалям: Цветы 🌸, Еда 🍔, Клиники 🏥, Такси 🚖, Недвижимость 🏠, Вода 💧.
                </p>
                
                <div class="mt-10 relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Попробуйте: 'Аренда офиса в Москва-сити' или 'Заказать 19л воды домой'"
                        class="w-full bg-white/10 border-white/20 text-white rounded-2xl py-5 px-6 pr-16 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all placeholder:text-gray-500 backdrop-blur-md"
                    >
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 p-2 bg-primary-600 rounded-xl text-white shadow-lg">
                         🤖 AI
                    </div>
                </div>
            </div>

            {{-- Фоновые абстрактные круги --}}
            <div class="absolute -top-24 -left-24 w-64 h-64 bg-primary-900 opacity-20 blur-3xl rounded-full animate-pulse"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-purple-900 opacity-20 blur-3xl rounded-full"></div>
        </section>

        {{-- KPI Сетка фасада --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @foreach($this->getMarketStats() as $key => $val)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                     <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest">{{ str_replace('_', ' ', $key) }}</p>
                     <h3 class="mt-2 text-2xl font-black text-gray-900 dark:text-gray-100">
                         {{ $val }}{{ $key === 'conversion' ? '%' : '' }}
                     </h3>
                </div>
            @endforeach
        </div>

        {{-- Результаты поиска (Сетка по вертикалям) --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-xl font-bold flex items-center text-gray-900 dark:text-gray-100">
                    <span class="mr-2 p-1 bg-green-500 text-white rounded">🔍</span> Результаты AI-выдачи
                </h4>
                <div class="flex space-x-2 text-xs font-bold text-gray-400 uppercase">
                    <span>Сортировка: Ближайшее и Лучшее</span>
                </div>
            </div>

            @if(count($results) === 0)
                <div class="p-20 text-center border-2 border-dashed border-gray-100 dark:border-gray-700 rounded-3xl text-gray-400 font-bold italic">
                   По вашему запросу ничего не найдено. Начните вводить текст...
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($results as $item)
                        <div class="group bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:scale-105 transition-all outline outline-transparent hover:outline-primary-500">
                             <div class="flex items-start justify-between">
                                  <span class="text-[10px] font-black p-1 px-3 bg-gray-50 dark:bg-gray-900 text-gray-400 rounded-full tracking-widest uppercase">
                                      {{ class_basename($item['searchable_type']) }}
                                  </span>
                                  <div class="flex items-center space-x-1">
                                       <span class="text-yellow-500">⭐</span>
                                       <span class="text-xs font-bold">{{ $item['rating'] }}</span>
                                  </div>
                             </div>

                             <h5 class="mt-4 text-lg font-black text-gray-900 dark:text-gray-100 group-hover:text-primary-600">
                                 {{ $item['title'] }}
                             </h5>
                             <p class="mt-2 text-sm text-gray-500 line-clamp-2">
                                 {{ $item['content'] }}
                             </p>

                             <div class="mt-6 flex items-center justify-between pt-4 border-t border-gray-50 dark:border-gray-700">
                                  <div class="flex items-center space-x-1 text-xs font-bold text-primary-500">
                                      <span>📍 1.2 км от вас</span>
                                  </div>
                                  <button class="bg-primary-600 text-white p-2 px-4 rounded-xl text-xs font-black shadow-lg shadow-primary-500/20 active:translate-y-1 transition-transform">
                                      ЗАКАЗАТЬ
                                  </button>
                             </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- AI Insights Footer --}}
        <div class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-3xl border-2 border-primary-500/10 relative overflow-hidden">
             <div class="relative z-10 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                  <div class="max-w-xl">
                       <h5 class="text-sm font-black text-gray-900 dark:text-gray-100">Используется технология Vector Semantic Search (VSS)</h5>
                       <p class="text-xs text-gray-500 mt-1">
                           В 2026 году поиск работает не только по словам, но и по смыслу. Мы понимаем контекст запроса, гео-положение тенанта и доступность его ресурсов (курьеров, столов, врачей) в реальном времени.
                       </p>
                  </div>
                  <div class="flex -space-x-4">
                       <div class="h-10 w-10 bg-primary-500 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-white text-[10px] font-black" title="Цветы">🌸</div>
                       <div class="h-10 w-10 bg-green-500 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-white text-[10px] font-black" title="Клиники">🏥</div>
                       <div class="h-10 w-10 bg-yellow-500 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-white text-[10px] font-black" title="Такси">🚖</div>
                       <div class="h-10 w-10 bg-blue-500 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-white text-[10px] font-black" title="Еда">🍔</div>
                       <div class="h-10 w-10 bg-indigo-500 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-white text-[10px] font-black" title="Недвижимость">🏠</div>
                       <div class="h-10 w-10 bg-cyan-500 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-white text-[10px] font-black" title="Вода">💧</div>
                  </div>
             </div>
        </div>
    </div>
</x-filament-panels::page>
