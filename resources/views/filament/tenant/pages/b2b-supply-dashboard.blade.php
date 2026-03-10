<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-6 bg-gradient-to-br from-primary-600 to-indigo-700 rounded-2xl shadow-lg text-white">
            <h1 class="text-3xl font-black mb-2 uppercase tracking-tighter">AI-Driven Procurement Engine</h1>
            <p class="text-primary-100 max-w-2xl text-sm leading-relaxed opacity-90">
                Optimized supply chain through predictive analysis. Real-time matching of manufacturer stock with business demand.
            </p>
        </div>

        @livewire('b2b.interactive-procurement')

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        {{-- Карточка 1: Баланс кошелька B2B --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest leading-none">B2B Текущий Баланс</span>
                <span class="p-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-lg">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </span>
            </div>
            <h3 class="mt-4 text-3xl font-black text-gray-900 dark:text-gray-100">${{ number_format($this->getStats()['wallet_balance'], 2) }}</h3>
            <p class="mt-1 text-xs text-gray-400 font-medium">Готов к моментальным закупкам ✅</p>
        </div>

        {{-- Карточка 2: Отложенная задолженность --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest leading-none">Неоплаченные Закупки</span>
                <span class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-lg text-lg">🚚</span>
            </div>
            <h3 class="mt-4 text-3xl font-black text-gray-900 dark:text-gray-100">${{ number_format($this->getStats()['outstanding_debt'], 2) }}</h3>
            <p class="mt-1 text-xs text-red-500 font-bold animate-pulse inline-flex items-center">
                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                Требуется сверка B2B
            </p>
        </div>

        {{-- Карточка 3: Поставщики --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest leading-none">Активные Контрагенты</span>
                <span class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-lg">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </span>
            </div>
            <h3 class="mt-4 text-3xl font-black text-gray-900 dark:text-gray-100">{{ $this->getStats()['total_suppliers'] }}</h3>
            <p class="mt-1 text-xs text-blue-500 font-bold">Сеть поставок стабильна</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden p-6">
        {{ $this->table }}
    </div>

    <div class="mt-6 flex items-center justify-between bg-primary-900 rounded-2xl p-6 text-white shadow-lg overflow-hidden relative">
        <div class="relative z-10">
            <h4 class="text-xl font-bold">Интеллект Поставок 🤖</h4>
            <p class="mt-2 text-sm text-primary-100 max-w-xl">
                Модуль AI Procurement использует ваши кошельки для моментальной оплаты товаров, 
                если чеки согласованы. Кредитные лимиты поставщиков позволяют 
                заказывать товары в долг с автоматическим погашением при пополнении баланса организации.
            </p>
        </div>
        <div class="absolute right-0 top-0 opacity-10 -mr-10 -mt-10">
             <svg class="h-64 w-64" fill="currentColor" viewBox="0 0 24 24"><path d="M11 2H13V4H11V2M15 4.6L16.4 3.2L17.8 4.6L16.4 6L15 4.6M11 22H13V24H11V22M19.4 15L20.8 16.4L19.4 17.8L18 16.4L19.4 15M4.6 15L6 16.4L4.6 17.8L3.2 16.4L4.6 15M20 11V13H22V11H20M2 11V13H4V11H2M17.8 19.4L16.4 20.8L15 19.4L16.4 18L17.8 19.4M6.4 4.6L7.8 6L6.4 7.4L5 6L6.4 4.6M12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7Z"/></svg>
        </div>
    </div>
</x-filament-panels::page>
