<div class="space-y-4 animate-pulse" wire:loading.remove wire:target="loadChartData">
    <!-- Skeleton chart area -->
    <div class="bg-white dark:bg-slate-800 rounded-lg p-6">
        <div class="h-80 bg-gradient-to-r from-gray-200 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-lg"></div>
    </div>

    <!-- Skeleton metadata grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach ($displayedLines as $line)
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-24 mb-2"></div>
                <div class="h-6 bg-gray-200 dark:bg-slate-700 rounded w-32"></div>
            </div>
        @endforeach
    </div>
</div>

<!-- Hidden content - shows when loading done -->
<div wire:loading.remove wire:target="loadChartData">
    <!-- Actual content goes here -->
    <slot />
</div>

<style>
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }

    .animate-shimmer {
        background: linear-gradient(
            90deg,
            #f0f0f0 25%,
            #e0e0e0 50%,
            #f0f0f0 75%
        );
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
    }

    .dark .animate-shimmer {
        background: linear-gradient(
            90deg,
            #475569 25%,
            #64748b 50%,
            #475569 75%
        );
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
    }
</style>
