<div x-data="{ 
    currentDate: '{{ $currentDate->format('Y-m-d') }}',
    viewMode: '{{ $viewMode }}',
    selectedMasterId: @entangle('selectedMasterId'),
    selectedStatus: @entangle('selectedStatus'),
    showCreateModal: @entangle('showCreateModal'),
    dragEvent: null
}" x-init="$wire.loadData()">
    
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <button wire:click="previousWeek" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    &larr; Предыдущая
                </button>
                <button wire:click="goToToday" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Сегодня
                </button>
                <button wire:click="nextWeek" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Следующая &rarr;
                </button>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ $currentDate->translatedFormat('F Y') }}
                </h2>
            </div>

            <div class="flex items-center gap-4">
                <!-- Filters -->
                <select wire:model.live="selectedMasterId" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Все мастера</option>
                    @foreach($masters as $master)
                        <option value="{{ $master['id'] }}">{{ $master['name'] }} ({{ $master['salon_name'] }})</option>
                    @endforeach
                </select>

                <select wire:model.live="selectedStatus" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Все статусы</option>
                    <option value="pending">Ожидает</option>
                    <option value="confirmed">Подтверждена</option>
                    <option value="in_progress">В процессе</option>
                    <option value="completed">Завершена</option>
                    <option value="cancelled">Отменена</option>
                    <option value="no_show">Неявка</option>
                </select>

                <button wire:click="showCreateModal = true" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                    + Новая запись
                </button>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-emerald-500"></div>
                <span class="text-gray-700 dark:text-gray-300">Подтверждена и оплачена</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-blue-500"></div>
                <span class="text-gray-700 dark:text-gray-300">Подтверждена, не оплачена</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-yellow-500"></div>
                <span class="text-gray-700 dark:text-gray-300">Ожидает подтверждения</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-orange-500"></div>
                <span class="text-gray-700 dark:text-gray-300">В процессе</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-red-500"></div>
                <span class="text-gray-700 dark:text-gray-300">Отменена / Неявка</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-gray-400"></div>
                <span class="text-gray-700 dark:text-gray-300">Заблокированный слот</span>
            </div>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300 w-32">
                            Мастер
                        </th>
                        @foreach($weekDays as $day)
                            <th class="px-4 py-3 text-center text-sm font-semibold {{ $day['is_today'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">
                                <div>{{ $day['day_name'] }}</div>
                                <div class="text-lg">{{ $day['day_number'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($masters as $master)
                        @if(!$selectedMasterId || $selectedMasterId == $master['id'])
                            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $master['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $master['salon_name'] }}
                                    </div>
                                </td>
                                
                                @foreach($weekDays as $day)
                                    <td class="px-2 py-3 min-w-[120px]">
                                        <div class="space-y-1">
                                            <!-- Appointments for this day and master -->
                                            @foreach($appointments as $appointment)
                                                @if($appointment['master_id'] == $master['id'] && Carbon::parse($appointment['start'])->format('Y-m-d') == $day['date'])
                                                    <div 
                                                        class="px-2 py-1 rounded text-xs cursor-pointer hover:opacity-80 transition"
                                                        style="background-color: {{ $appointment['color'] === 'success' ? '#10b981' : 
                                                                                   $appointment['color'] === 'info' ? '#3b82f6' : 
                                                                                   $appointment['color'] === 'warning' ? '#f59e0b' : 
                                                                                   $appointment['color'] === 'primary' ? '#f97316' : 
                                                                                   $appointment['color'] === 'danger' ? '#ef4444' : '#9ca3af' }}; color: white;"
                                                        draggable="true"
                                                        x-data
                                                        @dragstart="dragEvent = {{ json_encode($appointment) }}"
                                                        @dragend="dragEvent = null"
                                                    >
                                                        <div class="font-medium truncate">{{ $appointment['title'] }}</div>
                                                        <div class="opacity-75">{{ Carbon::parse($appointment['start'])->format('H:i') }} - {{ Carbon::parse($appointment['end'])->format('H:i') }}</div>
                                                    </div>
                                                @endif
                                            @endforeach

                                            <!-- Blocked slots for this day and master -->
                                            @foreach($blockedSlots as $slot)
                                                @if($slot['master_id'] == $master['id'] && Carbon::parse($slot['start'])->format('Y-m-d') == $day['date'])
                                                    <div 
                                                        class="px-2 py-1 rounded text-xs bg-gray-400 text-white"
                                                    >
                                                        <div class="font-medium truncate">{{ $slot['reason'] }}</div>
                                                        @if($slot['notes'])
                                                            <div class="opacity-75 truncate">{{ $slot['notes'] }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach

                                            <!-- Empty slot for dropping -->
                                            <div 
                                                class="h-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded hover:border-blue-500 transition"
                                                @dragover.prevent
                                                @drop="if (dragEvent) { $wire.call('moveAppointment', dragEvent.id, '{{ $master['id'] }}', '{{ $day['date'] }}'); dragEvent = null; }"
                                            ></div>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Appointment Modal -->
    <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                Создать запись
            </h3>
            
            <form wire:submit="createAppointment">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Мастер
                        </label>
                        <select wire:model="newAppointment.master_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Выберите мастера</option>
                            @foreach($masters as $master)
                                <option value="{{ $master['id'] }}">{{ $master['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Дата
                        </label>
                        <input type="date" wire:model="newAppointment.date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Время
                        </label>
                        <input type="time" wire:model="newAppointment.time" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" wire:click="showCreateModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Отмена
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        Создать
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
