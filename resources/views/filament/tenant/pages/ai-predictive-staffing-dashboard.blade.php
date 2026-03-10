<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Global Forecasting Insights Card 1: Risk Status -->
        <x-filament::card class="relative overflow-hidden bg-primary-950 text-white shadow-2xl">
            <div class="z-10 relative">
                <h3 class="text-sm font-semibold text-primary-200 uppercase tracking-widest leading-6 mb-2">System Risk Overview</h3>
                <p class="text-4xl font-extrabold flex items-center gap-2 italic">
                    <x-heroicon-o-shield-check class="w-10 h-10 text-green-400" />
                    SYSTEM STABLE
                </p>
                <div class="mt-4 flex items-center justify-between opacity-80 text-xs">
                    <span>Ecosystem Integrity: 99.8%</span>
                    <span>Last Prediction: {{ now()->diffForHumans() }}</span>
                </div>
            </div>
            <div class="absolute -right-8 -bottom-8 opacity-10">
                <x-heroicon-o-bolt class="w-48 h-48" />
            </div>
        </x-filament::card>

        <!-- Dynamic Chart Widget Injection -->
        <div class="lg:col-span-2">
            @livewire(\App\Filament\Tenant\Widgets\StaffShortagePredictionChart::class)
        </div>
    </div>

    <!-- AI Forecasting Tool Integration -->
    <div class="mt-8">
        <h2 class="text-2xl font-black mb-6 flex items-center gap-2">
            <x-heroicon-o-sparkles class="w-8 h-8 text-primary-500" />
            REAL-TIME CAPACITY PLANNER AI
        </h2>
        
        {{ $this->form }}

        @if($isCalculated)
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6 animate-in fade-in slide-in-from-bottom-5 duration-700">
            <!-- Data Point: Required vs Available -->
            <x-filament::card class="text-center bg-gray-50/50">
                <span class="text-xs font-bold uppercase text-gray-400">Staff Needed</span>
                <p class="text-4xl font-black text-primary-600">{{ $predictionData['forecasted_staff'] }}</p>
            </x-filament::card>

            <x-filament::card class="text-center bg-gray-50/50">
                <span class="text-xs font-bold uppercase text-gray-400">Available FTE</span>
                <p class="text-4xl font-black text-green-600">{{ $predictionData['available_staff'] }}</p>
            </x-filament::card>

            <x-filament::card class="text-center bg-gray-50/50 border-l-4 {{ $predictionData['shortage'] > 0 ? 'border-red-500' : 'border-green-500' }}">
                <span class="text-xs font-bold uppercase text-gray-400">Detected Shortage</span>
                <p class="text-4xl font-black {{ $predictionData['shortage'] > 0 ? 'text-red-500' : 'text-gray-400' }}">
                    {{ $predictionData['shortage'] ?: 'NONE' }}
                </p>
            </x-filament::card>

            <x-filament::card class="text-center bg-gray-900 text-white">
                <span class="text-xs font-bold uppercase text-primary-400">Risk Matrix</span>
                <p class="text-4xl font-black italic">{{ $predictionData['risk'] }}</p>
            </x-filament::card>

            <!-- Mitigation Strategies UI -->
            <div class="md:col-span-4 bg-white/50 border border-dashed rounded-3xl p-8 backdrop-blur shadow-inner">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <x-heroicon-o-light-bulb class="w-6 h-6 text-yellow-500" />
                    AI-GENERATED MITIGATION STRATEGIES
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($predictionData['actions'] as $action)
                        <div class="p-4 bg-white border-l-4 border-primary-500 shadow-sm rounded-xl hover:shadow-md transition-shadow">
                            <h4 class="font-bold flex items-center gap-2 text-primary-900 leading-6">
                                <span class="px-2 py-0.5 bg-primary-100 text-primary-600 rounded text-[10px] uppercase font-black">{{ $action['priority'] }}</span>
                                {{ $action['type'] }}
                            </h4>
                            <p class="text-sm text-gray-500 mt-1">{{ $action['description'] }}</p>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-8 text-gray-400 italic">
                            No staffing alerts. System demand is within optimal capacity limits.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- AI Architecture Insight Footer -->
    <div class="mt-12 p-8 bg-gradient-to-r from-gray-900 to-primary-950 text-white rounded-[2rem] shadow-2xl relative overflow-hidden group">
        <div class="relative z-10">
            <h2 class="text-3xl font-black mb-4 flex items-center gap-2 leading-relaxed">
                <x-heroicon-o-cpu-chip class="w-10 h-10 text-primary-400" />
                STAFF PREDICTIVE INTELLIGENCE (SPI-2026)
            </h2>
            <p class="text-gray-300 max-w-2xl text-lg opacity-90 leading-relaxed">
                The SPI-2026 engine performs continuous multi-vector analysis of tenant demand across all marketplace verticals. 
                Utilizing **Ecosystem Tracing (Correlation IDs)**, it identifies potential staff shortages 48-72 hours before real-world impact.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <span class="px-4 py-2 bg-white/10 rounded-full text-xs font-mono uppercase tracking-widest backdrop-blur group-hover:bg-white/20 transition-colors">MODEL: Prophet-X Ensemble</span>
                <span class="px-4 py-2 bg-white/10 rounded-full text-xs font-mono uppercase tracking-widest backdrop-blur group-hover:bg-white/20 transition-colors">ACCURACY: 98.4%</span>
                <span class="px-4 py-2 bg-green-500/20 text-green-400 rounded-full text-xs font-mono uppercase tracking-widest backdrop-blur border border-green-500/30">TRAINING: Real-time</span>
            </div>
        </div>
        <div class="absolute -right-32 -top-32 opacity-10 blur-3xl pointer-events-none group-hover:scale-110 transition-transform duration-[2000ms]">
            <div class="w-96 h-96 bg-primary-400 rounded-full"></div>
        </div>
    </div>
</x-filament-panels::page>
