<x-filament-panels::page>
    <div class="space-y-6">
        <!-- AI Analytical Controls & Results (Filament Form) -->
        {{ $this->form }}

        <!-- Activity Feed (Filament Table) -->
        <x-filament::section>
            <x-slot name="heading">Recent Behavior Logs (Raw Data)</x-slot>
            <x-slot name="description">Real-time interaction stream used by the AI Model for training and prediction.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
