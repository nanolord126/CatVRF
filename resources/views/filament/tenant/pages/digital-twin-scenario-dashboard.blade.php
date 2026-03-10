<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Input Form & Simulation Controls -->
        {{ $this->form }}

        <!-- Graph for Visualization would go here in Filament 2026 -->
        <x-filament::section>
            <x-slot name="heading">Scenario Comparison 24h/7d/30d</x-slot>
            <x-slot name="description">Compare the "Digital Twin" projection with the current baseline to see if the proposed change is sustainable.</x-slot>
            
            <div class="p-8 text-center text-gray-500 opacity-50 font-mono">
                [AI Prediction Graph: {{ $this->vertical }} Scenario Progress Plot]
                <br>
                Baseline: $100,000 | Scen: ${{ number_format($this->simulationResult['predicted_monthly_revenue'] ?? 100000, 2) }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
