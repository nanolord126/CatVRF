<div class="kds-loyalty-display bg-gray-900 text-white rounded-lg p-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-xs text-gray-400">Loyalty Points</div>
            <div class="text-3xl font-bold text-yellow-400">
                {{ number_format($availablePoints, 0) }}
            </div>
        </div>

        @if($tierName)
            <div class="text-right">
                <div class="text-xs text-gray-400">Tier</div>
                <div class="text-lg font-semibold"
                     style="color: {{ $tierColor }}">
                    {{ $tierName }}
                </div>
            </div>
        @endif
    </div>

    @if($tierProgress !== null && $nextTierName)
        <div class="mt-4">
            <div class="flex justify-between text-xs text-gray-400 mb-2">
                <span>Progress to {{ $nextTierName }}</span>
                <span>{{ number_format($tierProgress, 0) }}%</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2">
                <div class="h-2 rounded-full transition-all duration-300"
                     style="width: {{ $tierProgress }}%; background-color: {{ $tierColor }}">
                </div>
            </div>
            @if($nextTierPoints)
                <div class="text-xs text-gray-400 mt-1">
                    {{ number_format($nextTierPoints, 0) }} points needed
                </div>
            @endif
        </div>
    @endif
</div>
