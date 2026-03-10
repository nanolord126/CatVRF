<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- V-Coins Total Earning Card -->
        <x-filament::card>
            <div class="flex items-center space-x-4">
                <div class="bg-primary-500/10 p-3 rounded-xl">
                    <x-heroicon-o-currency-dollar class="w-8 h-8 text-primary-500" />
                </div>
                <div>
                    <h3 class="text-lg font-medium">Global V-Coins Issued</h3>
                    <p class="text-3xl font-bold">◎ {{ number_format(\DB::table('loyalty_transactions')->where('amount', '>', 0)->sum('amount'), 2) }}</p>
                </div>
            </div>
        </x-filament::card>

        <!-- Vertical Earning Distribution Table -->
        <x-filament::card class="col-span-1 lg:col-span-2">
            <h3 class="text-lg font-medium mb-4">Vertical Earning Activity</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Vertical</th>
                            <th class="py-2 text-right">Earning Rate</th>
                            <th class="py-2 text-right">Redemption Limit</th>
                            <th class="py-2 text-right">Total Transacted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['Taxi', 'Food', 'Clinics', 'Education', 'Sports', 'Events'] as $vertical)
                        <tr class="border-b last:border-0 hover:bg-gray-50/50">
                            <td class="py-3 font-semibold">{{ $vertical }}</td>
                            <td class="py-3 text-right">{{ 10 }}%</td>
                            <td class="py-3 text-right">{{ 30 }}%</td>
                            <td class="py-3 text-right">◎ {{ number_format(\DB::table('loyalty_transactions')->where('vertical', $vertical)->sum('amount'), 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>

    <!-- Active User Lookup Section (Admin Only) -->
    <div class="mt-8">
        {{ $this->form }}
    </div>

    <!-- Transaction History (Ledger) -->
    <div class="mt-8 border-t pt-8">
        <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
            <x-heroicon-o-list-bullet class="w-6 h-6 text-gray-500" />
            Ecosystem Loyalty Ledger
        </h3>
        {{ $this->table }}
    </div>

    <div class="mt-8 p-6 bg-primary-900 text-white rounded-2xl shadow-xl overflow-hidden relative">
        <div class="relative z-10">
            <h2 class="text-2xl font-black mb-2 italic">CROSS-VERTICAL REWARD ENGINE 2026</h2>
            <p class="text-primary-100 max-w-2xl">
                The Ecosystem Rewards System dynamically adjusts earn rates based on inter-modular loyalty rules. 
                Users earning in **Education** receive additional multipliers for **Taxi** rides to ensure ecosystem density.
            </p>
            <div class="mt-6 flex gap-4">
                <div class="px-4 py-2 bg-white/10 rounded-lg backdrop-blur">
                    <span class="block text-xs uppercase opacity-70">Anti-Fraud Status</span>
                    <span class="font-bold flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span> ACTIVE
                    </span>
                </div>
                <div class="px-4 py-2 bg-white/10 rounded-lg backdrop-blur">
                    <span class="block text-xs uppercase opacity-70">Model Tier</span>
                    <span class="font-bold">RFM-Optimized</span>
                </div>
            </div>
        </div>
        <div class="absolute -right-16 -top-16 opacity-10">
            <x-heroicon-o-gift class="w-64 h-64" />
        </div>
    </div>
</x-filament-panels::page>
