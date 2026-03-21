<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Input Form & Simulation Controls -->
        {{ $this->form }}

        <!-- Graph for Visualization would go here in Filament 2026 -->
        <x-filament::section>
            <x-slot name="heading">Сравнение сценариев 24ч/7д/30д</x-slot>
            <x-slot name="description">Сравните прогноз "Цифрового двойника" с текущим базовым уровнем, чтобы оценить устойчивость предложенных изменений.</x-slot>
            
            <div class="p-8 text-center text-gray-500 opacity-50 font-mono">
                [График ИИ-прогноза: {{ $this->vertical }} График развития сценария]
                <br>
                База: $100,000 | Сценарий: ${{ number_format($this->simulationResult['predicted_monthly_revenue'] ?? 100000, 2) }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
