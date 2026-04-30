<div>
    <div class="space-y-4">
        <!-- Size Recommendation Section -->
        @if($showRecommendation && !empty($recommendedSize))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">Рекомендуемый размер</h3>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="font-medium">EU:</span> {{ $recommendedSize['size_mappings']['eu'] ?? 'N/A' }}</div>
                <div><span class="font-medium">US:</span> {{ $recommendedSize['size_mappings']['us'] ?? 'N/A' }}</div>
                <div><span class="font-medium">UK:</span> {{ $recommendedSize['size_mappings']['uk'] ?? 'N/A' }}</div>
                <div><span class="font-medium">Точность:</span> {{ round(($recommendedSize['confidence'] ?? 0) * 100) }}%</div>
            </div>
        </div>
        @endif

        <!-- Size Grid -->
        <div class="grid grid-cols-4 gap-2">
            @foreach(collect($availableVariants)->groupBy('size_international') as $size => $variants)
            <div class="border rounded-lg p-2 @if($selectedSize === $size) border-blue-500 bg-blue-50 @endif">
                <div class="font-bold text-center">{{ $size }}</div>
                <div class="text-xs text-gray-500 text-center">
                    {{ $variants->first()['size_eu'] }} EU
                </div>
                @foreach($variants as $variant)
                <button
                    wire:click="selectVariant({{ $variant['id'] }})"
                    class="mt-1 w-full text-xs py-1 px-2 rounded
                        @if($selectedColor === $variant['color'] && $selectedSize === $variant['size_international'])
                        bg-blue-500 text-white
                        @else
                        bg-gray-100 hover:bg-gray-200
                        @endif"
                    style="background-color: {{ $variant['color_code'] }}20;"
                >
                    {{ $variant['color'] }}
                </button>
                @endforeach
            </div>
            @endforeach
        </div>

        <!-- Selected Variant Info -->
        @if($selectedSize && $selectedColor)
        <div class="bg-gray-50 border rounded-lg p-3">
            <div class="text-sm">
                <strong>Выбрано:</strong> {{ $selectedColor }}, размер {{ $selectedSize }}
                @if($selectedFit)
                ({{ $selectedFit }})
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
