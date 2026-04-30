@props([
    'status' => '',
    'label' => null,
    'color' => 'secondary',
    'icon' => null,
    'animate' => true,
])

@php
    $animationClass = match($color) {
        'success', 'emerald' => 'animate-success-pulse',
        'warning', 'amber' => 'animate-gentle-shake',
        'info' => 'animate-wave',
        'danger' => 'animate-danger-flash',
        default => '',
    };

    $colorClasses = match($color) {
        'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'secondary' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    };
@endphp

<span 
    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-500 {{ $colorClasses }} {{ $animate ? $animationClass : '' }}"
    x-data="{ 
        status: '{{ $status }}', 
        previousStatus: localStorage.getItem('status-{{ $status }}-{{ $label ?? $status }}') || null,
        init() {
            const currentStatus = this.status;
            const key = 'status-{{ $status }}-{{ $label ?? $status }}';
            const previous = localStorage.getItem(key);
            
            if (previous !== currentStatus) {
                setTimeout(() => {
                    this.$el.classList.add('animate-status-change');
                    setTimeout(() => {
                        this.$el.classList.remove('animate-status-change');
                    }, 600);
                }, 50);
            }
            
            localStorage.setItem(key, currentStatus);
        }
    }"
    x-init="init()">
    
    @if($icon)
        @php
            $iconComponent = match($icon) {
                'document' => 'heroicon-o-document',
                'clock' => 'heroicon-o-clock',
                'credit-card' => 'heroicon-o-credit-card',
                'check-circle' => 'heroicon-o-check-circle',
                'fire' => 'heroicon-o-fire',
                'hand-raised' => 'heroicon-o-hand-raised',
                'check-badge' => 'heroicon-o-check-badge',
                'x-circle' => 'heroicon-o-x-circle',
                'calendar' => 'heroicon-o-calendar',
                'user' => 'heroicon-o-user',
                'users' => 'heroicon-o-users',
                default => $icon,
            };
        @endphp
        <x-dynamic-component :component="$iconComponent" class="w-4 h-4" />
    @endif
    
    {{ $label ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
