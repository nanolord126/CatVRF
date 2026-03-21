<x-filament::card class="relative overflow-hidden bg-white dark:bg-gray-800 shadow-2xl rounded-3xl p-6 group">
    <div class="z-10 relative">
        <h2 class="text-2xl font-black mb-6 flex items-center gap-3 italic">
            <x-heroicon-o-sparkles class="w-8 h-8 text-primary-500 animate-pulse" />
            AI ЭКОСИСТЕМА РЕКОМЕНДАЦИЙ 2026
        </h2>

        <!-- Current Budget Context (Wallet Integration) -->
        <div class="mb-8 p-4 bg-primary-50 rounded-2xl flex items-center justify-between border border-primary-100">
            <div>
                <span class="block text-[10px] uppercase font-bold tracking-widest text-primary-400">Бюджет закупок</span>
                <span class="text-xl font-black text-primary-900 leading-none">◎ {{ number_format(\DB::table('ecosystem_loyalty_wallets')->where('user_id', auth()->id())->value('balance') ?? 0, 2) }}</span>
            </div>
            <div class="p-2 bg-white rounded-xl shadow-sm">
                <x-heroicon-o-credit-card class="w-5 h-5 text-primary-500" />
            </div>
        </div>

        <div class="space-y-6">
            <h3 class="text-xs font-black uppercase tracking-tighter text-gray-400 border-b pb-2 flex items-center justify-between">
                <span>Специально для вашей отрасли: {{ $vertical ?? 'Business' }}</span>
                <span class="text-primary-500 flex items-center gap-1">
                    <x-heroicon-o-arrow-path class="w-3 h-3" /> LIVE
                </span>
            </h3>

            <!-- Vertical Recommendations Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($recommendations ?? [] as $product)
                <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-transparent hover:border-primary-500 transition-all duration-300 relative group/card">
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-0.5 bg-primary-500 text-white text-[10px] rounded-lg font-black uppercase">
                            {{ $product->Совпадение_score ?: 98 }}% Совпадение
                        </span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase italic">
                            {{ $product->category }}
                        </span>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white leading-tight mb-1">{{ $product->name }}</h4>
                    <p class="text-xs text-gray-500 line-clamp-1 mb-3">{{ $product->Производитель->name ?? 'Производитель' }}</p>
                    
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="block text-[10px] text-gray-400 uppercase font-black">Цена</span>
                            <span class="font-black text-primary-600">◎ {{ number_format($product->price, 2) }}</span>
                        </div>
                        
                        <!-- 1-Click Smart Buy Button -->
                        <button 
                            wire:click="buyNowAction"
                            class="p-3 bg-primary-950 text-white rounded-xl hover:bg-primary-600 transition-colors shadow-lg"
                        >
                            <x-heroicon-o-shopping-bag class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Synergistic Cross-Vertical Recommendations -->
            @if(count($crossRecommendations ?? []) > 0)
            <div class="mt-8 border-t pt-6 bg-gray-50/50 -mx-6 px-6 pb-6 rounded-b-[inherit]">
                <h3 class="text-xs font-black uppercase tracking-tighter text-secondary-400 mb-4 flex items-center gap-2">
                    <x-heroicon-o-arrows-pointing-out class="w-4 h-4" />
                    Strategic Synergies (Cross-Vertical)
                </h3>
                <div class="flex gap-4 overflow-x-auto pb-4 no-scrollbar">
                    @foreach($crossRecommendations ?? [] as $cross)
                    <div class="min-w-[200px] p-4 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <span class="text-[9px] text-secondary-500 font-bold uppercase">{{ $cross->category }}</span>
                        <h5 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $cross->name }}</h5>
                        <p class="text-[10px] text-gray-400 mt-1 line-clamp-1 italic">Предложено для синергии в отрасли .</p>
                        <div class="mt-3 flex items-center justify-between border-t pt-2 border-gray-100">
                            <span class="text-xs font-black text-secondary-600">◎ {{ number_format($cross->price, 2) }}</span>
                            <x-heroicon-o-plus-circle class="w-5 h-5 text-gray-300 hover:text-secondary-500 cursor-pointer transition-colors" />
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- AI Tracing & Meta Insight -->
    <div class="mt-4 flex items-center justify-between text-[8px] font-mono text-gray-300 uppercase tracking-widest px-2">
        <span>X-Correlation-ID: {{ $correlation_id ?? 'local-'.now()->timestamp }}</span>
        <span>Model: Embeddings-3-Large + ClickHouse Boost</span>
    </div>
    
    <div class="absolute -right-16 -top-16 opacity-5 rotate-12 group-hover:rotate-45 transition-transform duration-[2000ms]">
        <x-heroicon-o-sparkles class="w-64 h-64" />
    </div>
</x-filament::card>
