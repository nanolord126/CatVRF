<div class="loyalty-reward-selector">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-600">Available Points</div>
                <div class="text-2xl font-bold text-blue-600">
                    {{ number_format($availablePoints, 2) }}
                </div>
            </div>
            <div class="text-xs text-gray-500">
                Select a reward to redeem
            </div>
        </div>
    </div>

    @if(empty($availableRewards))
        <div class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p>No rewards available at this time</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($availableRewards as $reward)
                <div class="reward-card border rounded-lg p-4 cursor-pointer transition-all
                     {{ $selectedRewardId === $reward['id'] ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' : 'border-gray-200 hover:border-blue-300 hover:shadow-md' }}"
                     wire:click="selectReward('{{ $reward['id'] }}')">

                    @if($reward['image_url'])
                        <img src="{{ $reward['image_url'] }}" alt="{{ $reward['name'] }}" class="w-full h-32 object-cover rounded mb-3">
                    @endif

                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-gray-900">{{ $reward['name'] }}</h3>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded">
                            {{ number_format($reward['points_cost'], 0) }} pts
                        </span>
                    </div>

                    @if($reward['description'])
                        <p class="text-sm text-gray-600 mb-2">{{ $reward['description'] }}</p>
                    @endif

                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span class="capitalize">{{ $reward['type'] }}</span>
                        @if($reward['value_amount'])
                            <span>
                                @if($reward['value_type'] === 'percentage')
                                    {{ $reward['value_amount'] * 100 }}% off
                                @else
                                    {{ number_format($reward['value_amount'], 2) }} ₽
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
