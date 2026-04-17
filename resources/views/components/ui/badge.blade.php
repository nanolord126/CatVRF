{{--
    x-ui-badge
    variant: success|warning|danger|info|neutral  (default: neutral)
--}}
@props(['variant' => 'neutral'])

@php
$variantClasses = match($variant) {
    'success' => 'bg-marketplace-success/15 text-emerald-400 ring-1 ring-emerald-500/20',
    'warning' => 'bg-marketplace-warning/15 text-amber-400 ring-1 ring-amber-500/20',
    'danger'  => 'bg-marketplace-danger/15 text-red-400 ring-1 ring-red-500/20',
    'info'    => 'bg-marketplace-primary/15 text-neuro-indigo-300 ring-1 ring-neuro-indigo-400/20',
    default   => 'bg-white/10 text-carbon-300 ring-1 ring-white/10',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium $variantClasses"]) }}>
    {{ $slot }}
</span>
