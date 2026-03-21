<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Input Form & Simulation Controls -->
        {{ $this->form }}

        <!-- Historical Log of AI Calculations -->
        <x-filament::section>
            <x-slot name="heading">История расчетов динамического ценообразования ИИ</x-slot>
            <x-slot name="description">Журнал аудита всех недавних корректировок цен, выполненных ИИ в экосистеме.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
