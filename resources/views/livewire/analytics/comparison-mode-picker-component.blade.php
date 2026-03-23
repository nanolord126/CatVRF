<div class="comparison-mode-picker p-6 bg-white dark:bg-slate-900 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Режим сравнения</h3>
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" wire:change="toggleComparison()" {{ $isComparison ? 'checked' : '' }}
                class="mr-2 w-4 h-4 rounded">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $isComparison ? 'Включено' : 'Отключено' }}
            </span>
        </label>
    </div>

    @if ($isComparison)
        <!-- Предустановки -->
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Быстрые настройки</h4>
            <div class="flex flex-wrap gap-2">
                @foreach ($presets as $key => $preset)
                    <button wire:click="applyPreset('{{ $key }}')"
                        class="px-3 py-2 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-200 rounded-lg text-sm hover:bg-purple-200 dark:hover:bg-purple-800 transition">
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Выбор дат -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 1 от</label>
                <input type="date" wire:model="period1From" wire:change="updateDates()"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 1 до</label>
                <input type="date" wire:model="period1To" wire:change="updateDates()"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 2 от</label>
                <input type="date" wire:model="period2From" wire:change="updateDates()"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Период 2 до</label>
                <input type="date" wire:model="period2To" wire:change="updateDates()"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
            </div>
        </div>

        <!-- Информация о периодах -->
        <div class="mt-4 p-3 bg-purple-50 dark:bg-purple-900 rounded-lg text-sm text-gray-700 dark:text-gray-300">
            <p><strong>Период 1:</strong> {{ $period1From }} до {{ $period1To }} 
                ({{ \Carbon\Carbon::parse($period1To)->diffInDays(\Carbon\Carbon::parse($period1From)) + 1 }} дней)</p>
            <p><strong>Период 2:</strong> {{ $period2From }} до {{ $period2To }} 
                ({{ \Carbon\Carbon::parse($period2To)->diffInDays(\Carbon\Carbon::parse($period2From)) + 1 }} дней)</p>
        </div>
    @endif
</div>
