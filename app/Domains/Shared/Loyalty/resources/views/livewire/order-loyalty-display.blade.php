<div class="loyalty-display bg-white rounded-lg shadow p-4">
    @if($tierName)
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-600">Current Tier</span>
            <span class="px-3 py-1 rounded-full text-sm font-semibold"
                  style="background-color: {{ $tierColor }}20; color: {{ $tierColor }}">
                {{ $tierName }}
            </span>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 mb-3">
        <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">Available Points</div>
            <div class="text-2xl font-bold text-gray-900">
                {{ number_format($availablePoints, 2) }}
            </div>
        </div>

        @if($earnedPoints !== null)
            <div class="bg-green-50 rounded-lg p-3">
                <div class="text-xs text-gray-500 mb-1">Points Earned</div>
                <div class="text-2xl font-bold text-green-600">
                    +{{ number_format($earnedPoints, 2) }}
                </div>
            </div>
        @endif
    </div>

    @if($pointsDescription)
        <div class="text-xs text-gray-500 bg-gray-50 rounded p-2">
            {{ $pointsDescription }}
        </div>
    @endif
</div>
