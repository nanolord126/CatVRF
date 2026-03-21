<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Main Content: Widgets are rendered automatically if defined in the Page class, 
             but we can also layout custom blocks here --}}
        
        <div class="md:col-span-2 space-y-6">
            {{-- AI Recommendations naturally go here via getHeaderWidgets --}}
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Quick Actions Block -->
                <div class="p-6 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-indigo-500/20 rounded-xl group-hover:bg-indigo-500/30 transition-colors">
                            <x-heroicon-o-shopping-cart class="w-6 h-6 text-indigo-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-white">Новый заказ</h3>
                            <p class="text-sm text-gray-400">Создать транзакцию B2B/B2C</p>
                        </div>
                    </div>
                    <a href="#" class="mt-4 block text-center py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-semibold transition-all">
                        Запустить
                    </a>
                </div>

                <div class="p-6 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-emerald-500/20 rounded-xl group-hover:bg-emerald-500/30 transition-colors">
                            <x-heroicon-o-banknotes class="w-6 h-6 text-emerald-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-white">Выплата</h3>
                            <p class="text-sm text-gray-400">Payroll & Wallet Transfer</p>
                        </div>
                    </div>
                    <a href="#" class="mt-4 block text-center py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-semibold transition-all">
                        Перевести
                    </a>
                </div>

                <div class="p-6 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-amber-500/20 rounded-xl group-hover:bg-amber-500/30 transition-colors">
                            <x-heroicon-o-megaphone class="w-6 h-6 text-amber-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-white">Реклама</h3>
                            <p class="text-sm text-gray-400">AI Ads & Marketplace Boost</p>
                        </div>
                    </div>
                    <a href="#" class="mt-4 block text-center py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-sm font-semibold transition-all">
                        Настроить
                    </a>
                </div>

                <div class="p-6 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-purple-500/20 rounded-xl group-hover:bg-purple-500/30 transition-colors">
                            <x-heroicon-o-cpu-chip class="w-6 h-6 text-purple-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-white">Аналитика AI</h3>
                            <p class="text-sm text-gray-400">Прогноз спроса 2026</p>
                        </div>
                    </div>
                    <a href="#" class="mt-4 block text-center py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg text-sm font-semibold transition-all">
                        Анализ
                    </a>
                </div>
            </div>

            {{-- Geo Heatmap --}}
            <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl overflow-hidden shadow-2xl">
                <div class="p-4 border-b border-white/10 bg-white/5">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <x-heroicon-o-map class="w-5 h-5 mr-2 text-indigo-400" />
                        Логистическая активность
                    </h2>
                </div>
                <div class="p-2">
                    @livewire(\App\Filament\Tenant\Widgets\GeoHeatmapWidget::class)
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Sidebar / Stats -->
            <div class="p-6 rounded-2xl border border-white/10 bg-gradient-to-br from-indigo-600/20 to-purple-600/20 backdrop-blur-xl shadow-2xl">
                <h3 class="text-white font-bold mb-4">Состояние экосистемы</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-300 text-sm">Состояние системы</span>
                        <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-xs rounded-full border border-emerald-500/30">Стабильно</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-300 text-sm">Баланс кошелька</span>
                        <span class="text-white font-medium">842,500 ₽</span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-indigo-500 h-full w-[85%] shadow-[0_0_10px_rgba(99,102,241,0.5)]"></div>
                    </div>
                </div>
            </div>

            <!-- AI Insight Mini Widget -->
            <div class="p-6 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl shadow-2xl">
                <div class="flex items-center text-indigo-400 mb-4 text-sm font-bold uppercase tracking-wider">
                    <x-heroicon-m-sparkles class="w-5 h-5 mr-2" />
                    Инсайт дня
                </div>
                <p class="text-gray-300 italic text-sm leading-relaxed">
                    "Спрос на кастомные букеты в радиусе 5км вырастет на 40% к вечеру субботы. Рекомендуем пополнить склад роз."
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
