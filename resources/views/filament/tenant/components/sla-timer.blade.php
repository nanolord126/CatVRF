@php
    $createdAt = $getRecord()->created_at;
    $dueTime = $createdAt->addMinutes(45); // Standard 45 min SLA
    $now = now();
    $diff = $now->diffInMinutes($dueTime, false);
    $percentage = max(0, min(100, (45 - abs($diff)) / 45 * 100));
    $isOverdue = $diff < 0;
@endphp

<div @class([
    'flex flex-col items-center justify-center p-6 rounded-3xl border shadow-2xl transition-all duration-300',
    'bg-red-500/10 border-red-500/30' => $isOverdue,
    'bg-purple-500/10 border-purple-500/30' => !$isOverdue,
])>
    <div class="text-4xl font-black tracking-tighter mb-2 font-mono">
        @if($isOverdue)
            <span class="text-red-500 animate-pulse">-{{ abs($diff) }} МИН</span>
        @else
            <span class="text-purple-400 font-mono">{{ $diff }} МИН</span>
        @endif
    </div>
    
    <div class="w-full bg-gray-800 h-1.5 rounded-full overflow-hidden mt-2">
        <div @class([
            'h-full transition-all duration-1000',
            'bg-red-500' => $isOverdue,
            'bg-purple-500 shadow-[0_0_10px_rgba(139,92,246,0.6)]' => !$isOverdue,
        ]) style="width: {{ $isOverdue ? 100 : $percentage }}%"></div>
    </div>
    
    <div @class([
        'text-[10px] font-bold uppercase tracking-widest mt-3',
        'text-red-500' => $isOverdue,
        'text-purple-400' => !$isOverdue,
    ])>
        {{ $isOverdue ? 'ПРОСРОЧЕНО (SLA)' : 'ОСТАЛОСЬ ДО ЦЕЛИ' }}
    </div>
</div>
