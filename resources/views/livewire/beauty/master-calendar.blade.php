<div x-data="{ viewMode: '{{ $viewMode }}' }" class="min-h-screen bg-gray-100 dark:bg-gray-900">
    @if(!$master)
        <div class="p-8 text-center">
            <p class="text-gray-600 dark:text-gray-400">Мастер не найден</p>
        </div>
    @else
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 shadow-md p-4 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                        Мой календарь
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $master->full_name }}</p>
                </div>

                <div class="flex items-center gap-2 md:gap-4">
                    <button wire:click="previous" class="px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-lg">
                        &larr;
                    </button>
                    <button wire:click="goToToday" class="px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">
                        Сегодня
                    </button>
                    <button wire:click="next" class="px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-lg">
                        &rarr;
                    </button>
                    <button wire:click="toggleViewMode" class="px-4 py-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition font-medium ml-2">
                        {{ $viewMode === 'day' ? 'Неделя' : 'День' }}
                    </button>
                </div>
            </div>

            <div class="mt-4 text-xl md:text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $currentDate->translatedFormat('l, j F Y') }}
            </div>
        </div>

        <!-- Current Appointment Alert -->
        @if($currentAppointmentId)
            @php
                $currentApp = collect($appointments)->firstWhere('id', $currentAppointmentId);
            @endphp
            @if($currentApp)
                <div class="bg-orange-100 dark:bg-orange-900 border-l-4 border-orange-500 p-4 mx-4 mt-4 rounded-r-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold text-orange-800 dark:text-orange-200 text-lg">
                                Текущая услуга: {{ $currentApp['service_name'] }}
                            </p>
                            <p class="text-orange-700 dark:text-orange-300">
                                Клиент: {{ $currentApp['client_name'] }}
                            </p>
                            @if($currentApp['time_remaining'] !== null)
                                <p class="text-orange-600 dark:text-orange-400 font-medium">
                                    Осталось: {{ $currentApp['time_remaining'] }} мин
                                </p>
                            @endif
                        </div>
                        <button wire:click="completeAppointment({{ $currentApp['id'] }})" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium text-lg">
                            Завершить
                        </button>
                    </div>
                </div>
            @endif
        @endif

        <!-- Appointments List -->
        <div class="p-4 space-y-4">
            @forelse($appointments as $appointment)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 md:p-6 border-l-4 {{ 
                    $appointment['status'] === 'completed' ? 'border-green-500' : 
                    $appointment['status'] === 'in_progress' ? 'border-orange-500' : 
                    $appointment['status'] === 'cancelled' ? 'border-red-500' : 
                    $appointment['status'] === 'no_show' ? 'border-gray-500' : 
                    'border-blue-500' 
                }}">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $appointment['service_name'] }}
                                </h3>
                                <x-status-badge 
                                    :status="$appointment['status']"
                                    :label="match($appointment['status']) {
                                        'pending' => 'Ожидает',
                                        'confirmed' => 'Подтверждена',
                                        'in_progress' => 'В процессе',
                                        'completed' => 'Завершена',
                                        'cancelled' => 'Отменена',
                                        'no_show' => 'Неявка',
                                        default => ucfirst($appointment['status']),
                                    }"
                                    :color="match($appointment['status']) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'in_progress' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'no_show' => 'secondary',
                                        default => 'secondary',
                                    }"
                                    :icon="match($appointment['status']) {
                                        'pending' => 'clock',
                                        'confirmed' => 'calendar',
                                        'in_progress' => 'fire',
                                        'completed' => 'check-badge',
                                        'cancelled' => 'x-circle',
                                        'no_show' => 'users',
                                        default => null,
                                    }"
                                />
                            </div>

                            <div class="space-y-1 text-gray-700 dark:text-gray-300">
                                <p class="text-lg">
                                    <span class="font-medium">Клиент:</span> {{ $appointment['client_name'] }}
                                </p>
                                @if($appointment['client_phone'])
                                    <p class="text-lg">
                                        <span class="font-medium">Телефон:</span> 
                                        <a href="tel:{{ $appointment['client_phone'] }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $appointment['client_phone'] }}
                                        </a>
                                    </p>
                                @endif
                                <p class="text-lg">
                                    <span class="font-medium">Время:</span> 
                                    {{ \Carbon\Carbon::parse($appointment['start'])->format('H:i') }} - 
                                    {{ \Carbon\Carbon::parse($appointment['end'])->format('H:i') }}
                                    <span class="text-gray-500 dark:text-gray-400">({{ $appointment['duration_minutes'] }} мин)</span>
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        @if($appointment['status'] === 'confirmed')
                            <div class="flex flex-col gap-2">
                                <button wire:click="startAppointment({{ $appointment['id'] }})" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium text-lg">
                                    Начать услугу
                                </button>
                                <button wire:click="markNoShow({{ $appointment['id'] }})" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-medium">
                                    Не пришёл
                                </button>
                            </div>
                        @elseif($appointment['status'] === 'in_progress')
                            <button wire:click="completeAppointment({{ $appointment['id'] }})" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium text-lg">
                                Завершить
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center">
                    <p class="text-xl text-gray-600 dark:text-gray-400">
                        На {{ $currentDate->translatedFormat('l') }} нет записей
                    </p>
                </div>
            @endforelse
        </div>
    @endif
</div>
