<div class="space-y-3">
    @if($subVerticalStats->isEmpty())
    <div class="text-center text-gray-500 py-8">
        Нет данных за выбранный период
    </div>
    @else
    @foreach($subVerticalStats as $stat)
    @php
    $percentage = $totalRevenue > 0 ? ($stat->revenue / $totalRevenue * 100) : 0;
    $colors = [
        'bg-blue-500',
        'bg-green-500',
        'bg-yellow-500',
        'bg-red-500',
        'bg-purple-500',
        'bg-pink-500',
    ];
    $color = $colors[array_rand($colors)];
    @endphp
    <div class="space-y-1">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-gray-900">
                {{ $stat->sub_vertical ?? 'Другое' }}
            </span>
            <span class="text-gray-500">
                {{ number_format($stat->revenue, 0, ',', ' ') }} ₽ ({{ number_format($percentage, 1) }}%)
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="{{ $color }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
        </div>
    </div>
    @endforeach
    @endif
</div>
