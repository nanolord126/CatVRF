<x-filament-widgets::widget>
    <div class="space-y-4">
        <x-filament::section>
            <x-slot name="heading">
                Notification Recommendations
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Channel Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <h3 class="text-lg font-semibold mb-3">Recommended Channels</h3>
                    <div class="space-y-2">
                        @foreach($recommendations['channels'] as $channel)
                            <div class="flex items-center justify-between">
                                <span class="capitalize">{{ $channel['channel'] }}</span>
                                <div class="flex items-center gap-2">
                                    <x-filament::progress 
                                        :value="$channel['score']" 
                                        class="w-32"
                                    />
                                    <span class="text-sm">{{ $channel['score'] }}%</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">{{ $channel['reason'] }}</p>
                        @endforeach
                    </div>
                </div>

                <!-- Type Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <h3 class="text-lg font-semibold mb-3">Top Notification Types</h3>
                    <div class="space-y-2">
                        @foreach($recommendations['types'] as $type)
                            <div class="flex items-center justify-between">
                                <span class="text-sm">{{ $type['type'] }}</span>
                                <span class="text-xs text-gray-500">{{ $type['score }} pts</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Optimal Send Time -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <h3 class="text-lg font-semibold mb-3">Optimal Send Time</h3>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">
                            {{ $recommendations['optimal_time']['hour'] }}:00
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            Confidence: {{ $recommendations['optimal_time']['confidence'] * 100 }}%
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $recommendations['optimal_time']['reason'] === 'high_confidence' ? 'Based on your reaction history' : 'Limited data available' }}
                        </p>
                    </div>
                </div>

                <!-- Personalization -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <h3 class="text-lg font-semibold mb-3">Personalization Insights</h3>
                    <div class="space-y-2 text-sm">
                        @if(isset($recommendations['personalization']['optimal_send_hour']))
                            <div class="flex justify-between">
                                <span>Optimal Hour:</span>
                                <span class="font-semibold">{{ $recommendations['personalization']['optimal_send_hour'] }}:00</span>
                            </div>
                        @endif
                        @if(isset($recommendations['personalization']['preferred_channel']))
                            <div class="flex justify-between">
                                <span>Preferred Channel:</span>
                                <span class="font-semibold capitalize">{{ $recommendations['personalization']['preferred_channel'] }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span>Engagement Level:</span>
                            <span class="font-semibold capitalize">{{ $recommendations['personalization']['engagement_level'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Frequency:</span>
                            <span class="font-semibold">{{ str_replace('_', ' ', $recommendations['personalization']['frequency_suggestion']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
