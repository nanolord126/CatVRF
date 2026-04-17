{{-- livewire/shared/fraud-alert-banner.blade.php --}}
@if($visible)
@php
$colors = match($severity) {
    'critical' => ['bg' => 'bg-marketplace-danger/20 border-marketplace-danger/40', 'text' => 'text-red-300', 'icon' => 'text-red-400'],
    'high'     => ['bg' => 'bg-orange-500/15 border-orange-500/30', 'text' => 'text-orange-300', 'icon' => 'text-orange-400'],
    default    => ['bg' => 'bg-marketplace-warning/10 border-marketplace-warning/25', 'text' => 'text-amber-300', 'icon' => 'text-amber-400'],
};
$role = $severity === 'critical' ? 'alert' : 'status';
@endphp

<div
    class="relative flex items-start gap-3 px-4 py-3 rounded-marketplace border {{ $colors['bg'] }} w-full"
    role="{{ $role }}"
    aria-live="{{ $severity === 'critical' ? 'assertive' : 'polite' }}"
    aria-atomic="true"
>
    <svg class="w-4 h-4 mt-0.5 shrink-0 {{ $colors['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>

    <div class="flex-1 min-w-0">
        <p class="text-xs font-semibold {{ $colors['text'] }} uppercase tracking-wide mb-0.5">
            @if($severity === 'critical') Критическое предупреждение
            @elseif($severity === 'high') Важное предупреждение
            @else Предупреждение
            @endif
        </p>
        <p class="text-xs {{ $colors['text'] }} opacity-90">{{ $message }}</p>
    </div>

    @if($severity !== 'critical')
        <button
            wire:click="dismiss"
            class="shrink-0 p-1 rounded hover:bg-white/10 transition-colors focus:outline-none"
            aria-label="Закрыть предупреждение"
        >
            <svg class="w-3.5 h-3.5 text-carbon-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>
@endif
