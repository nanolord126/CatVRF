<!-- Error Boundary Component -->
@if ($hasError)
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg" role="alert">
        <div class="flex items-start justify-between">
            <div class="flex items-start">
                <!-- Error Icon -->
                <svg class="h-6 w-6 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <!-- Error Details -->
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ $errorCode ?: 'Ошибка загрузки' }}
                    </h3>
                    <p class="mt-2 text-sm text-red-700 dark:text-red-300">
                        {{ $errorMessage }}
                    </p>

                    <!-- Correlation ID for Support -->
                    @if ($correlationId)
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                            Код для поддержки: <code class="font-mono">{{ $correlationId }}</code>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Close Button -->
            <button wire:click="clearError()" class="ml-4 text-red-400 hover:text-red-600 dark:hover:text-red-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Retry Button -->
        <div class="mt-4">
            <button wire:click="retry()" 
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Повторить попытку
            </button>
        </div>
    </div>
@else
    <!-- No error - render slot -->
    <slot />
@endif
