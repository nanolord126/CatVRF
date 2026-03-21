<div class="max-w-2xl mx-auto p-6 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20">

    {{-- Заголовок --}}
    <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
        <svg class="w-6 h-6 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Записаться на услугу
    </h2>

    {{-- Успех --}}
    @if ($booked)
        <div class="p-6 bg-green-50 border border-green-200 rounded-xl text-center">
            <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-green-700 font-semibold text-lg">Запись создана!</p>
            <p class="text-green-600 text-sm mt-1">Напоминание придёт за 24 часа и за 2 часа до визита.</p>
            <a href="/beauty/appointments/{{ $createdAppointmentId }}"
               class="mt-4 inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                Посмотреть запись
            </a>
        </div>
    @else

    {{-- Ошибки --}}
    @if ($errorMessage)
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
            {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit="bookAppointment" class="space-y-5">

        {{-- Мастер --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Мастер</label>
            <select
                wire:model.live="masterId"
                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pink-400 focus:border-transparent bg-white text-gray-800 text-sm"
            >
                <option value="">— Выберите мастера —</option>
                @foreach ($masters as $master)
                    <option value="{{ $master['id'] }}">{{ $master['full_name'] }}</option>
                @endforeach
            </select>
            @error('masterId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- Услуга --}}
        @if ($masterId && count($services))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Услуга</label>
            <select
                wire:model="serviceId"
                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pink-400 focus:border-transparent bg-white text-gray-800 text-sm"
            >
                <option value="">— Выберите услугу —</option>
                @foreach ($services as $service)
                    <option value="{{ $service['id'] }}">
                        {{ $service['name'] }}
                        ({{ $service['duration_minutes'] }} мин · {{ number_format($service['price'] / 100, 0) }} ₽)
                    </option>
                @endforeach
            </select>
            @error('serviceId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
        @endif

        {{-- Дата --}}
        @if ($serviceId)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Дата</label>
            <input
                type="date"
                wire:model="selectedDate"
                wire:change="loadAvailableSlots($event.target.value)"
                min="{{ now()->format('Y-m-d') }}"
                max="{{ now()->addMonths(2)->format('Y-m-d') }}"
                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pink-400 focus:border-transparent text-sm"
            />
            @error('selectedDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
        @endif

        {{-- Слоты --}}
        @if ($selectedDate && count($availableSlots))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Время</label>
            <div class="grid grid-cols-4 gap-2">
                @foreach ($availableSlots as $slot)
                    <label class="cursor-pointer text-center py-2 px-1 border rounded-xl text-sm transition
                                  {{ $selectedTime === $slot
                                     ? 'bg-pink-500 text-white border-pink-500 font-semibold shadow-md'
                                     : 'border-gray-200 text-gray-700 hover:bg-pink-50 hover:border-pink-300' }}">
                        <input type="radio" wire:model="selectedTime" value="{{ $slot }}" class="sr-only"/>
                        {{ $slot }}
                    </label>
                @endforeach
            </div>
            @error('selectedTime') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
        @elseif ($selectedDate && !count($availableSlots))
        <p class="text-sm text-amber-600 bg-amber-50 px-4 py-3 rounded-xl border border-amber-100">
            На выбранную дату свободных слотов нет. Попробуйте другой день.
        </p>
        @endif

        {{-- Комментарий --}}
        @if ($selectedTime)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Комментарий (необязательно)</label>
            <textarea
                wire:model="comment"
                rows="2"
                maxlength="500"
                placeholder="Пожелания к мастеру..."
                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pink-400 focus:border-transparent text-sm resize-none"
            ></textarea>
        </div>
        @endif

        {{-- Кнопка --}}
        <button
            type="submit"
            wire:loading.attr="disabled"
            @disabled(!$selectedTime)
            class="w-full py-3 rounded-xl font-semibold text-white transition
                   {{ $selectedTime
                      ? 'bg-pink-500 hover:bg-pink-600 shadow-lg shadow-pink-200'
                      : 'bg-gray-200 cursor-not-allowed text-gray-400' }}"
        >
            <span wire:loading.remove wire:target="bookAppointment">🗓 Записаться</span>
            <span wire:loading wire:target="bookAppointment" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Создаём запись...
            </span>
        </button>

    </form>
    @endif

</div>
