<div>
    <div class="space-y-4">
        <!-- Size Recommendation Section -->
        @if($showRecommendation && !empty($recommendedSize))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">Рекомендуемый размер</h3>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="font-medium">EU:</span> {{ $recommendedSize['size_eu'] ?? 'N/A' }}</div>
                <div><span class="font-medium">US:</span> {{ $recommendedSize['size_us'] ?? 'N/A' }}</div>
                <div><span class="font-medium">UK:</span> {{ $recommendedSize['size_uk'] ?? 'N/A' }}</div>
                <div><span class="font-medium">Длина стопы:</span> {{ $recommendedSize['foot_length_fit'] ?? 'N/A' }}</div>
                <div><span class="font-medium">Ширина:</span> {{ $recommendedSize['recommended_width'] ?? 'N/A' }}</div>
                <div><span class="font-medium">Точность:</span> {{ round(($recommendedSize['confidence'] ?? 0) * 100) }}%</div>
            </div>
        </div>
        @endif

        <!-- Size Grid with Width -->
        <div class="space-y-3">
            @foreach(collect($availableVariants)->groupBy('size_eu') as $sizeEu => $variants)
            <div class="border rounded-lg p-3">
                <div class="font-bold text-center mb-2">EU {{ $sizeEu }}</div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($variants as $variant)
                    <button
                        wire:click="selectVariant({{ $variant['id'] }})"
                        class="text-sm py-2 px-3 rounded border
                            @if($selectedSizeEu === $variant['size_eu'] && $selectedWidth === $variant['width'] && $selectedColor === $variant['color'])
                            bg-blue-500 text-white border-blue-500
                            @else
                            bg-white hover:bg-gray-50
                            @endif"
                    >
                        <div class="font-medium">{{ $variant['width'] }}</div>
                        <div class="text-xs text-gray-500">{{ $variant['color'] }}</div>
                        <div class="text-xs text-gray-400">{{ $variant['size_cm'] }} см</div>
                    </button>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <!-- Selected Variant Info -->
        @if($selectedSizeEu && $selectedWidth && $selectedColor)
        <div class="bg-gray-50 border rounded-lg p-3">
            <div class="text-sm">
                <strong>Выбрано:</strong> {{ $selectedColor }}, EU {{ $selectedSizeEu }} ({{ $selectedWidth }})
            </div>
        </div>
        @endif

        <!-- Measurement Input for Recommendation -->
        <div class="border rounded-lg p-4">
            <h3 class="font-semibold mb-3">Узнать свой размер</h3>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Длина стопы (см)</label>
                    <input
                        type="number"
                        step="0.1"
                        wire:model.debounce.500ms="footLengthCm"
                        class="w-full border rounded px-3 py-2"
                        placeholder="Например: 26.5"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ширина стопы (см)</label>
                    <input
                        type="number"
                        step="0.1"
                        wire:model.debounce.500ms="footWidthCm"
                        class="w-full border rounded px-3 py-2"
                        placeholder="Например: 10.0"
                    >
                </div>
            </div>
            <button
                wire:click="getSizeRecommendation"
                class="mt-3 w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700"
            >
                Рассчитать размер
            </button>
        </div>
    </div>
</div>
