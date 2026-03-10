<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold tracking-tight">AI Smart Supply: Tailored for Your Vertical</h2>
                <p class="text-sm text-gray-500">Machine learning optimization of your wholesale procurement.</p>
            </div>
            <div class="flex space-x-2">
                <x-filament::badge color="success">AI Optimized</x-filament::badge>
                <x-filament::badge color="info">2026 Canon</x-filament::badge>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($recommendations as $product)
                <x-filament::section class="bg-gray-50 dark:bg-gray-800 border-none shadow-sm">
                    <div class="flex flex-col h-full">
                        <div class="mb-2">
                            <span class="text-xs font-semibold uppercase text-primary-600">{{ $product->tags ?? 'General' }}</span>
                            <h3 class="font-bold text-md line-clamp-1">{{ $product->name }}</h3>
                        </div>
                        
                        <div class="mt-auto">
                            <div class="flex justify-between items-end mb-3">
                                <span class="text-xl font-black">${{ number_format($product->base_wholesale_price, 2) }}</span>
                                <span class="text-xs text-gray-400">Min: {{ $product->min_order_quantity }} units</span>
                            </div>

                            <x-filament::button 
                                wire:click="smartPurchase({{ $product->id }})"
                                icon="heroicon-m-bolt"
                                class="w-full shadow-lg shadow-primary-500/20"
                                color="primary"
                            >
                                Smart Purchase
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        @if(count($crossRecommendations) > 0)
            <div class="mt-8 border-t pt-6">
                <h3 class="text-md font-bold mb-4 flex items-center">
                    <x-filament::icon icon="heroicon-m-sparkles" class="w-5 h-5 mr-2 text-warning-500" />
                    Cross-Vertical Synergies
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    @foreach($crossRecommendations as $product)
                         <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-100 flex flex-col justify-between">
                            <span class="text-xs font-medium text-primary-700 truncate">{{ $product->name }}</span>
                            <span class="text-sm font-bold text-primary-900 mt-1">${{ number_format($product->base_wholesale_price, 2) }}</span>
                         </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
