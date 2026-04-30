<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
            Протокол безопасности: {{ $species }}
        </h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
            {{ $isComplete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
            {{ $isComplete ? 'Выполнен' : 'В процессе' }}
        </span>
    </div>

    @if(!$protocol)
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        {{ $validationMessage }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <!-- Risk Factors -->
        @if(count($riskFactors) > 0)
            <div class="mb-4 bg-red-50 border border-red-200 rounded-md p-4">
                <h4 class="text-sm font-medium text-red-800 mb-2">Факторы риска:</h4>
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($riskFactors as $risk)
                        <li>{{ $risk }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Required Equipment -->
        @if(count($requiredEquipment) > 0)
            <div class="mb-4 bg-blue-50 border border-blue-200 rounded-md p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Необходимое оборудование:</h4>
                <ul class="text-sm text-blue-700 list-disc list-inside">
                    @foreach($requiredEquipment as $equipment)
                        <li>{{ $equipment }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Checklist Items -->
        <div class="space-y-3">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Обязательные пункты:</h4>
            
            @foreach($protocolItems as $key => $label)
                <label class="flex items-start space-x-3 p-3 rounded-lg border 
                    {{ $checklist[$key] ?? false ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }}
                    hover:bg-gray-100 transition-colors cursor-pointer">
                    <div class="flex items-center h-5">
                        <input 
                            type="checkbox" 
                            wire:model="checklist.{{ $key }}"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex-1">
                        <span class="text-sm text-gray-900">{{ $label }}</span>
                    </div>
                    @if($checklist[$key] ?? false)
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </label>
            @endforeach
        </div>

        <!-- Validation Message -->
        <div class="mt-4 p-3 rounded-md 
            {{ $isComplete ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
            <p class="text-sm {{ $isComplete ? 'text-green-800' : 'text-yellow-800' }}">
                {{ $validationMessage }}
            </p>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="flex justify-between text-xs text-gray-600 mb-1">
                <span>Прогресс</span>
                <span>{{ count(array_filter($checklist)) }} / {{ count($protocolItems) }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                    class="h-2 rounded-full transition-all duration-300
                    {{ $isComplete ? 'bg-green-500' : 'bg-indigo-500' }}"
                    style="width: {{ (count(array_filter($checklist)) / max(count($protocolItems), 1)) * 100 }}%"
                ></div>
            </div>
        </div>
    @endif
</div>
