<!-- Breadcrumb Navigation -->
<nav class="mb-6" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2 text-sm">
        @foreach ($breadcrumbs as $index => $breadcrumb)
            <li class="flex items-center">
                <!-- Link (if has route) -->
                @if (isset($breadcrumb['route']))
                    <a href="{{ route($breadcrumb['route']) }}" 
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium transition">
                        {{ $breadcrumb['label'] }}
                    </a>
                @else
                    <!-- Current page (no link) -->
                    <span class="text-gray-700 dark:text-gray-300 font-medium">
                        {{ $breadcrumb['label'] }}
                    </span>
                @endif

                <!-- Separator (if not last) -->
                @if ($index < count($breadcrumbs) - 1)
                    <svg class="h-4 w-4 mx-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
