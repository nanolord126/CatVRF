<div x-data="{
    showModal: @entangle('showModal'),
    selectedTooth: @entangle('selectedTooth'),
    selectedStatus: @entangle('selectedStatus'),
    notes: @entangle('notes'),
    description: @entangle('description'),
}" class="dental-chart">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                {{ $numberingSystem === 'fdi' ? 'FDI Нумерация' : 'Universal Нумерация' }}
            </h2>
            <div class="flex gap-2">
                <button wire:click="loadChart" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Обновить
                </button>
            </div>
        </div>

        <!-- Upper Jaw -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Верхняя челюсть</h3>
            <div class="flex flex-col gap-2">
                @foreach($this->upperJaw as $row)
                    <div class="flex gap-2 justify-center">
                        @foreach($row as $toothNumber)
                            @php
                                $tooth = $this->teeth[(string)$toothNumber] ?? null;
                                $color = $tooth['color'] ?? '#22c55e';
                                $requiresAttention = $tooth['requiresAttention'] ?? false;
                            @endphp
                            <div 
                                wire:click="selectTooth('{{ $toothNumber }}')"
                                class="w-12 h-16 rounded-lg border-2 cursor-pointer flex items-center justify-center font-bold text-white transition-all hover:scale-110 hover:shadow-md"
                                :style="{
                                    backgroundColor: '{{ $color }}',
                                    borderColor: '{{ $requiresAttention ? '#dc2626' : '#e5e7eb' }}',
                                    borderWidth: '{{ $requiresAttention ? '3px' : '2px' }}'
                                }"
                                title="{{ $tooth['label'] ?? 'Здоровый' }}"
                            >
                                {{ $toothNumber }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Lower Jaw -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Нижняя челюсть</h3>
            <div class="flex flex-col gap-2">
                @foreach($this->lowerJaw as $row)
                    <div class="flex gap-2 justify-center">
                        @foreach($row as $toothNumber)
                            @php
                                $tooth = $this->teeth[(string)$toothNumber] ?? null;
                                $color = $tooth['color'] ?? '#22c55e';
                                $requiresAttention = $tooth['requiresAttention'] ?? false;
                            @endphp
                            <div 
                                wire:click="selectTooth('{{ $toothNumber }}')"
                                class="w-12 h-16 rounded-lg border-2 cursor-pointer flex items-center justify-center font-bold text-white transition-all hover:scale-110 hover:shadow-md"
                                :style="{
                                    backgroundColor: '{{ $color }}',
                                    borderColor: '{{ $requiresAttention ? '#dc2626' : '#e5e7eb' }}',
                                    borderWidth: '{{ $requiresAttention ? '3px' : '2px' }}'
                                }"
                                title="{{ $tooth['label'] ?? 'Здоровый' }}"
                            >
                                {{ $toothNumber }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Legend -->
        <div class="border-t pt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Легенда статусов</h4>
            <div class="flex flex-wrap gap-3">
                @foreach($this->toothStatuses as $status => $info)
                    <div class="flex items-center gap-2">
                        <div 
                            class="w-4 h-4 rounded"
                            style="background-color: {{ $info['color'] }}"
                        ></div>
                        <span class="text-sm text-gray-600">{{ $info['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal for editing tooth status -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display: none;">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                Редактирование зуба {{ $selectedTooth }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                    <select wire:model.live="selectedStatus" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($this->toothStatuses as $status => $info)
                            <option value="{{ $status }}">{{ $info['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                    <input 
                        type="text" 
                        wire:model.live="description"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Описание состояния"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Заметки</label>
                    <textarea 
                        wire:model.live="notes"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        rows="3"
                        placeholder="Дополнительные заметки"
                    ></textarea>
                </div>

                <div class="flex gap-3 justify-end mt-6">
                    <button 
                        wire:click="closeModal"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                    >
                        Отмена
                    </button>
                    <button 
                        wire:click="saveToothStatus"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                        Сохранить
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
