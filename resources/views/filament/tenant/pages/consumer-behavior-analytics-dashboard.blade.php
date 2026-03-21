<x-filament-panels::page>
    <div class="space-y-6">
        <!-- AI Analytical Controls & Results (Filament Form) -->
        {{ $this->form }}

        <!-- Activity Feed (Filament Table) -->
        <x-filament::section>
            <x-slot name="heading">Недавние логи поведения (Сырые данные)</x-slot>
            <x-slot name="description">Поток взаимодействий в реальном времени, используемый моделью ИИ для обучения и прогнозирования.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
