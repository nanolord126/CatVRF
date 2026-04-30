<div>
    <div class="space-y-4">
        <!-- Safety Check Result -->
        @if(!empty($safetyCheck))
        @if($isSafe)
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-green-800 font-medium">Безопасно для ваших аллергий</span>
            </div>
        </div>
        @else
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <span class="text-red-800 font-medium">Обнаружены аллергены</span>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach($safetyCheck['conflicts'] as $conflict)
                        <li>
                            <strong>{{ $conflict['material'] }}</strong>: {{ $conflict['description'] }}
                            <span class="ml-2 px-2 py-0.5 rounded text-xs
                                @if($conflict['severity'] === 'mild') bg-yellow-100 text-yellow-800
                                @elseif($conflict['severity'] === 'moderate') bg-orange-100 text-orange-800
                                @elseif($conflict['severity'] === 'severe') bg-red-100 text-red-800
                                @elseif($conflict['severity'] === 'life_threatening') bg-red-200 text-red-900
                                @endif">
                                {{ $conflict['severity'] }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif
        @endif

        <!-- Input Form -->
        <div class="border rounded-lg p-4">
            <h3 class="font-semibold mb-3">Проверить аллергии</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Материалы продукта</label>
                    <input
                        type="text"
                        wire:model.live="materials"
                        class="w-full border rounded px-3 py-2"
                        placeholder="Например: хлопок, шерсть, полиэстер"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ваши аллергии</label>
                    <select
                        wire:model.live="userAllergies"
                        multiple
                        class="w-full border rounded px-3 py-2 h-32"
                    >
                        <option value="contact_dermatitis">Контактный дерматит</option>
                        <option value="textile">Текстиль</option>
                        <option value="metal">Металлы</option>
                        <option value="rubber">Резина</option>
                        <option value="latex">Латекс</option>
                        <option value="chemical">Химические вещества</option>
                    </select>
                </div>
                <button
                    wire:click="checkSafety"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700"
                >
                    Проверить
                </button>
            </div>
        </div>
    </div>
</div>
