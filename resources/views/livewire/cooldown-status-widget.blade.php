@if(count($activeCooldowns) > 0)
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r shadow-sm" role="alert">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 w-full">
                <h3 class="text-sm font-medium text-amber-800">
                    Активные периоды охлаждения
                </h3>
                <div class="mt-2 space-y-2">
                    @foreach($activeCooldowns as $cooldown)
                        <div class="bg-white rounded-md p-3 shadow-sm border border-amber-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $cooldown['action_label'] }}
                                    </p>
                                    <p class="text-xs text-gray-600 mt-1">
                                        {{ $cooldown['reason'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        @if($cooldown['is_tenant_level'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                Tenant-level
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                User-level
                                            </span>
                                        @endif
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-amber-600">
                                        {{ $cooldown['remaining_time'] }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        до {{ \Carbon\Carbon::parse($cooldown['expires_at'])->format('d.m.Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-amber-700 mt-2">
                    Финансовые операции временно ограничены. Если это ошибка, обратитесь в поддержку.
                </p>
            </div>
        </div>
    </div>
@endif
