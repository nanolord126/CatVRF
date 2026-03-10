<div class="space-y-6">
    <div class="flex items-center justify-between gap-4 p-4 bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex-1">
            <input 
                wire:model.live.debounce.300ms="search" 
                type="text" 
                placeholder="Search products or suppliers (AI enhanced)..." 
                class="w-full px-4 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
            >
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-primary-50 rounded-lg dark:bg-primary-900/20">
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Budget Limit:</span>
            <span class="text-lg font-bold text-primary-600 dark:text-primary-400">₽{{ number_format($budget, 0, '.', ' ') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($recommendations as $product)
            <div class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300 dark:bg-gray-800 dark:border-gray-700">
                @if($product->is_ai_featured)
                    <div class="absolute top-3 right-3 z-10">
                        <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full shadow-sm">AI Top Match</span>
                    </div>
                @endif

                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">{{ $product->manufacturer->category ?? 'General' }}</p>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight group-hover:text-primary-600 transition-colors">{{ $product->name }}</h3>
                        </div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Match Score</span>
                            <span class="font-bold text-green-600">{{ $product->ai_match_score ?? '94' }}%</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Price</span>
                            <span class="font-bold text-gray-900 dark:text-white">₽{{ number_format($product->base_wholesale_price, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Supplier</span>
                            <span class="text-primary-600 font-medium">{{ $product->manufacturer->name }}</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button 
                            wire:click="smartPurchase({{ $product->id }})"
                            wire:loading.attr="disabled"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all dark:bg-primary-600 dark:hover:bg-primary-500"
                        >
                            <span wire:loading.remove wire:target="smartPurchase({{ $product->id }})">Smart Purchase</span>
                            <span wire:loading wire:target="smartPurchase({{ $product->id }})">Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(count($recommendations) === 0)
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 dark:bg-gray-700">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">No matches found</h3>
            <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters or search terms.</p>
        </div>
    @endif
</div>
