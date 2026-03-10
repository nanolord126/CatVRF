<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Input Form & Simulation Controls -->
        {{ $this->form }}

        <!-- Historical Log of AI Calculations -->
        <x-filament::section>
            <x-slot name="heading">AI Dynamic Pricing Calculation History</x-slot>
            <x-slot name="description">Audit trail of all recent AI-driven price adjustments across the ecosystem.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
