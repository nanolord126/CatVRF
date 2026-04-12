<x-filament-widgets::widget>
    @php
        $grouped = $this->getGroupedInteractions();
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
    @endphp

    <x-filament::section
        heading="Таймлайн активности"
        icon="heroicon-o-clock"
        collapsible
    >
        @if (empty($grouped))
            <div class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                Нет взаимодействий с клиентом.
            </div>
        @else
            <div class="space-y-6">
                @foreach ($grouped as $date => $interactions)
                    {{-- Дата-разделитель --}}
                    <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        <span>
                            @if ($date === $today)
                                Сегодня
                            @elseif ($date === $yesterday)
                                Вчера
                            @else
                                {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
                            @endif
                        </span>
                        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                    </div>

                    {{-- Элементы таймлайна --}}
                    <div class="relative pl-8 space-y-4">
                        {{-- Вертикальная линия --}}
                        <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                        @foreach ($interactions as $interaction)
                            @php
                                $type = $interaction->type ?? 'note';
                                $icon = \App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmActivityTimelineWidget::getIconForType($type);
                                $color = \App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmActivityTimelineWidget::getColorForType($type);
                                $label = \App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmActivityTimelineWidget::getLabelForType($type);
                                $directionLabel = $interaction->direction
                                    ? \App\Filament\Tenant\Resources\CrmClientResource\Widgets\CrmActivityTimelineWidget::getLabelForDirection($interaction->direction)
                                    : null;
                            @endphp

                            <div class="relative flex items-start gap-3">
                                {{-- Иконка-маркер --}}
                                <div class="absolute -left-5 mt-0.5 flex items-center justify-center w-6 h-6 rounded-full
                                    @switch($color)
                                        @case('success') bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400 @break
                                        @case('danger') bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400 @break
                                        @case('warning') bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400 @break
                                        @case('info') bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400 @break
                                        @case('primary') bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-400 @break
                                        @default bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                    ring-2 ring-white dark:ring-gray-900">
                                    <x-filament::icon :icon="$icon" class="w-3.5 h-3.5" />
                                </div>

                                {{-- Контент --}}
                                <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $label }}
                                            </span>
                                            @if ($interaction->channel)
                                                <x-filament::badge size="sm" color="gray">
                                                    {{ $interaction->channel }}
                                                </x-filament::badge>
                                            @endif
                                            @if ($directionLabel)
                                                <x-filament::badge size="sm" color="info">
                                                    {{ $directionLabel }}
                                                </x-filament::badge>
                                            @endif
                                            @if ($interaction->is_resolved)
                                                <x-filament::badge size="sm" color="success">
                                                    ✓ Решено
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-400">
                                            {{ $interaction->created_at->format('H:i') }}
                                        </span>
                                    </div>

                                    @if ($interaction->content)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                            {{ \Illuminate\Support\Str::limit($interaction->content, 300) }}
                                        </p>
                                    @endif

                                    @if ($interaction->assigned_to)
                                        <div class="mt-2 text-xs text-gray-400">
                                            Ответственный: {{ $interaction->assigned_to }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
