{{-- resources/views/livewire/beauty/appointment-calendar.blade.php --}}
{{-- Месячный календарь записей. КАНОН 2026: glassmorphism, dark mode, mobile-first. --}}
<div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl shadow-xl p-4 sm:p-6 text-white">

    {{-- Шапка: навигация + фильтр мастера --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">

        {{-- Название месяца --}}
        <div class="flex items-center gap-3">
            <button wire:click="previousMonth"
                    class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors"
                    title="Предыдущий месяц">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <h2 class="text-lg font-semibold capitalize min-w-[160px] text-center">{{ $monthName }}</h2>

            <button wire:click="nextMonth"
                    class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors"
                    title="Следующий месяц">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <button wire:click="goToToday"
                    class="text-xs px-3 py-1.5 rounded-lg bg-pink-500/30 hover:bg-pink-500/50 text-pink-200 transition-colors">
                Сегодня
            </button>
        </div>

        {{-- Фильтр мастера --}}
        @if (count($masters) > 1)
            <select wire:model.live="masterId"
                    class="bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400">
                <option value="">Все мастера</option>
                @foreach ($masters as $master)
                    <option value="{{ $master['id'] }}">{{ $master['full_name'] }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- Статистика за месяц --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mb-5">
        @php
            $statItems = [
                ['label' => 'Всего',     'value' => $stats['total'],     'color' => 'white'],
                ['label' => 'Ожидают',   'value' => $stats['pending'],   'color' => 'yellow'],
                ['label' => 'Подтв.',    'value' => $stats['confirmed'], 'color' => 'blue'],
                ['label' => 'Завершены', 'value' => $stats['completed'], 'color' => 'green'],
                ['label' => 'Отменены',  'value' => $stats['cancelled'], 'color' => 'red'],
            ];
        @endphp
        @foreach ($statItems as $item)
            <div class="bg-white/10 rounded-xl p-3 text-center">
                <div class="text-xl font-bold text-{{ $item['color'] }}-300">{{ $item['value'] }}</div>
                <div class="text-xs text-white/60 mt-0.5">{{ $item['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Сетка дней недели --}}
    <div class="grid grid-cols-7 mb-1">
        @foreach ($dayNames as $name)
            <div class="text-center text-xs font-medium text-white/50 py-1.5">{{ $name }}</div>
        @endforeach
    </div>

    {{-- Матрица дней --}}
    <div class="border border-white/10 rounded-xl overflow-hidden">
        @foreach ($weeks as $weekIndex => $week)
            <div class="grid grid-cols-7 {{ $weekIndex < count($weeks) - 1 ? 'border-b border-white/10' : '' }}">
                @foreach ($week as $dayIndex => $day)
                    @php
                        $isSelected = $selectedDate === $day['date'];
                        $hasAppointments = $day['appointments'] > 0;
                        $cellBg = match(true) {
                            $isSelected          => 'bg-pink-500/30 ring-2 ring-inset ring-pink-400',
                            $day['isToday']      => 'bg-indigo-500/20',
                            $day['isPast']       => 'bg-white/3',
                            !$day['isCurrentMonth'] => 'bg-white/2',
                            default              => 'bg-white/5 hover:bg-white/10',
                        };
                    @endphp
                    <button
                        wire:click="selectDay('{{ $day['date'] }}')"
                        class="relative min-h-[64px] p-1.5 text-left transition-colors {{ $cellBg }}
                               {{ $dayIndex < 6 ? 'border-r border-white/10' : '' }}
                               {{ !$day['isCurrentMonth'] ? 'opacity-40' : '' }}"
                        title="{{ $day['date'] }}: {{ $day['appointments'] }} записей"
                    >
                        {{-- Номер дня --}}
                        <span class="text-sm font-semibold {{ $day['isToday'] ? 'text-indigo-300' : ($day['isPast'] && $day['isCurrentMonth'] ? 'text-white/50' : 'text-white') }}">
                            {{ $day['day'] }}
                        </span>

                        {{-- Индикаторы записей --}}
                        @if ($hasAppointments)
                            <div class="mt-1 flex flex-wrap gap-0.5">
                                @if ($day['statusBreakdown']['confirmed'] > 0)
                                    <span class="inline-block w-2 h-2 rounded-full bg-blue-400" title="Подтв.: {{ $day['statusBreakdown']['confirmed'] }}"></span>
                                @endif
                                @if ($day['statusBreakdown']['pending'] > 0)
                                    <span class="inline-block w-2 h-2 rounded-full bg-yellow-400" title="Ожидает: {{ $day['statusBreakdown']['pending'] }}"></span>
                                @endif
                                @if ($day['statusBreakdown']['completed'] > 0)
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-400" title="Завершено: {{ $day['statusBreakdown']['completed'] }}"></span>
                                @endif
                                @if ($day['statusBreakdown']['cancelled'] > 0)
                                    <span class="inline-block w-2 h-2 rounded-full bg-red-400" title="Отменено: {{ $day['statusBreakdown']['cancelled'] }}"></span>
                                @endif
                                <span class="text-[10px] text-white/60 leading-none ml-0.5">{{ $day['appointments'] }}</span>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Панель дня (expandable popover) --}}
    @if ($selectedDate && count($selectedDayAppointments) > 0)
        <div class="mt-4 bg-white/10 border border-white/20 rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                <h3 class="font-medium text-white">
                    Записи на {{ \Carbon\Carbon::parse($selectedDate)->locale('ru')->isoFormat('D MMMM') }}
                </h3>
                <button wire:click="selectDay('{{ $selectedDate }}')"
                        class="text-white/50 hover:text-white text-lg leading-none">&times;</button>
            </div>

            <div class="divide-y divide-white/10 max-h-72 overflow-y-auto">
                @foreach ($selectedDayAppointments as $apt)
                    @php
                        $statusLabel = match ($apt['status']) {
                            'pending'   => ['Ожидает', 'bg-yellow-500/20 text-yellow-300'],
                            'confirmed' => ['Подтверждена', 'bg-blue-500/20 text-blue-300'],
                            'completed' => ['Завершена', 'bg-green-500/20 text-green-300'],
                            'cancelled' => ['Отменена', 'bg-red-500/20 text-red-300'],
                            default     => [$apt['status'], 'bg-white/10 text-white/60'],
                        };
                    @endphp
                    <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-white/5 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-sm font-mono font-semibold text-pink-300 shrink-0">{{ $apt['time'] }}</span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ $apt['client'] }}</p>
                                <p class="text-xs text-white/50 truncate">{{ $apt['service'] }} · {{ $apt['master'] }} · {{ $apt['duration'] }} мин</p>
                                @if ($apt['phone'])
                                    <p class="text-xs text-white/40">{{ $apt['phone'] }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                            @if ($apt['price'])
                                <span class="text-xs font-medium text-white/70">{{ number_format($apt['price'] / 100, 0, '.', ' ') }} ₽</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif ($selectedDate && count($selectedDayAppointments) === 0)
        <div class="mt-4 text-center text-sm text-white/40 py-4">
            На {{ \Carbon\Carbon::parse($selectedDate)->locale('ru')->isoFormat('D MMMM') }} записей нет
        </div>
    @endif

    {{-- Легенда --}}
    <div class="mt-4 flex flex-wrap gap-3 text-xs text-white/50">
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400"></span> Подтверждена</span>
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-400"></span> Ожидает</span>
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400"></span> Завершена</span>
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-400"></span> Отменена</span>
    </div>

    {{-- Loading overlay --}}
    <div wire:loading class="absolute inset-0 bg-black/30 rounded-2xl flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-pink-400 border-t-transparent rounded-full animate-spin"></div>
    </div>
</div>
