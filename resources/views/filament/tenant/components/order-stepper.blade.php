<div class="py-4">
    <div class="flex items-center justify-between relative">
        <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-700 -translate-y-1/2 z-0"></div>
        
        @php
            $statuses = [
                'pending' => ['label' => 'Принят', 'icon' => 'heroicon-o-clock'],
                'in_progress' => ['label' => 'В пути', 'icon' => 'heroicon-o-truck'],
                'delivered' => ['label' => 'Доставлен', 'icon' => 'heroicon-o-check-circle'],
            ];
            $currentStatus = $getRecord()->status;
            $order = array_keys($statuses);
            $currentIndex = array_search($currentStatus, $order);
        @endphp

        @foreach($statuses as $key => $info)
            @php
                $index = array_search($key, $order);
                $isCompleted = $index < $currentIndex;
                $isActive = $index === $currentIndex;
            @endphp
            
            <div class="relative z-10 flex flex-col items-center group">
                <div @class([
                    'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-500 shadow-lg',
                    'bg-green-500 text-white' => $isCompleted || ($isActive && $key === 'delivered'),
                    'bg-purple-600 text-white ring-4 ring-purple-500/30 animate-pulse' => $isActive && $key !== 'delivered',
                    'bg-gray-800 text-gray-500' => !$isActive && !$isCompleted,
                ])>
                    <x-filament::icon :icon="$info['icon']" class="w-6 h-6" />
                </div>
                <span @class([
                    'mt-2 text-xs font-bold uppercase tracking-tighter',
                    'text-green-500' => $isCompleted,
                    'text-purple-400' => $isActive,
                    'text-gray-500' => !$isActive && !$isCompleted,
                ])>
                    {{ $info['label'] }}
                </span>
            </div>
        @endforeach
    </div>
</div>
