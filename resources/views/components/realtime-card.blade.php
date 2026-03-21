@props(['title', 'value', 'icon', 'color' => 'blue', 'trend' => null])

<div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-{{ $color }}-500">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-gray-600 text-sm font-medium">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $value }}</p>
            
            @if ($trend)
                <div class="flex items-center mt-2">
                    <span class="text-{{ $trend['positive'] ? 'green' : 'red' }}-600 text-sm font-semibold">
                        {{ $trend['positive'] ? '↑' : '↓' }} {{ $trend['percent'] }}%
                    </span>
                    <span class="text-gray-500 text-xs ml-1">{{ $trend['period'] }}</span>
                </div>
            @endif
        </div>
        
        @if ($icon)
            <div class="text-{{ $color }}-500 text-3xl">{{ $icon }}</div>
        @endif
    </div>
    
    <!-- Real-time indicator -->
    <div class="mt-4 flex items-center gap-2">
        <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
        <span class="text-gray-500 text-xs">Live updates</span>
    </div>
</div>

<style>
    .border-blue-500 { border-left-color: #3b82f6; }
    .border-green-500 { border-left-color: #10b981; }
    .border-red-500 { border-left-color: #ef4444; }
    .border-purple-500 { border-left-color: #a855f7; }
</style>
