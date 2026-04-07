<div class="min-h-screen bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">📍 Мои адреса</h1>
                <p class="text-gray-500 text-sm mt-1">Сохранено {{ count($addresses) }} из {{ \App\Livewire\User\Addresses::MAX_ADDRESSES }}</p>
            </div>
            @if(count($addresses) < \App\Livewire\User\Addresses::MAX_ADDRESSES)
                <button wire:click="toggleForm"
                        class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white
                               px-4 py-2 rounded-xl text-sm font-medium transition">
                    {{ $showForm ? '✕ Отмена' : '+ Добавить' }}
                </button>
            @else
                <span class="text-xs text-gray-400 italic">Максимум {{ \App\Livewire\User\Addresses::MAX_ADDRESSES }} адресов</span>
            @endif
        </div>

        {{-- Ошибка --}}
        @if($errorMessage)
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm mb-4">
                {{ $errorMessage }}
            </div>
        @endif

        {{-- Форма добавления адреса --}}
        @if($showForm)
            <div class="bg-white rounded-2xl border border-purple-200 shadow-sm p-6 mb-4"
                 x-data="{ lat: @entangle('newLat'), lon: @entangle('newLon') }">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Новый адрес</h2>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500">Тип адреса</label>
                        <div class="flex gap-2 mt-1">
                            @foreach(['home' => '🏠 Дом', 'work' => '💼 Работа', 'other' => '📍 Другое'] as $type => $label)
                                <button wire:click="$set('newType', '{{ $type }}')"
                                        class="flex-1 py-2 text-sm rounded-lg border transition
                                            {{ $newType === $type
                                                ? 'border-purple-500 bg-purple-50 text-purple-700 font-medium'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-500">Адрес</label>
                        <input wire:model="newAddress" type="text"
                               placeholder="ул. Пушкина, д. 10, кв. 5"
                               class="w-full mt-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm
                                      focus:outline-none focus:border-purple-400 transition">
                        @error('newAddress')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-500">Широта (опционально)</label>
                            <input wire:model="newLat" type="number" step="0.000001" placeholder="55.751244"
                                   class="w-full mt-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm
                                          focus:outline-none focus:border-purple-400 transition">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Долгота (опционально)</label>
                            <input wire:model="newLon" type="number" step="0.000001" placeholder="37.618423"
                                   class="w-full mt-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm
                                          focus:outline-none focus:border-purple-400 transition">
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button wire:click="save" wire:loading.attr="disabled"
                            class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:opacity-50
                                   text-white font-medium py-2.5 rounded-xl text-sm transition">
                        <span wire:loading.remove wire:target="save">Сохранить</span>
                        <span wire:loading wire:target="save">Сохраняю...</span>
                    </button>
                    <button wire:click="toggleForm"
                            class="px-4 py-2.5 rounded-xl text-sm text-gray-600 border border-gray-200 hover:bg-gray-50 transition">
                        Отмена
                    </button>
                </div>
            </div>
        @endif

        {{-- Список адресов --}}
        <div class="space-y-3">
            @forelse($addresses as $address)
                <div class="bg-white rounded-2xl border
                    {{ ($defaultAddress && $address['id'] == $defaultAddress['id']) ? 'border-purple-300' : 'border-gray-100' }}
                    shadow-sm p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">
                                {{ match($address['type'] ?? 'other') {
                                    'home' => '🏠', 'work' => '💼', default => '📍'
                                } }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $address['address'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ match($address['type'] ?? 'other') {
                                        'home' => 'Дом', 'work' => 'Работа', default => 'Другое'
                                    } }}
                                    @if(!empty($address['lat']) && !empty($address['lon']))
                                        · {{ round($address['lat'], 4) }}, {{ round($address['lon'], 4) }}
                                    @endif
                                </p>
                                @if($defaultAddress && $address['id'] == $defaultAddress['id'])
                                    <span class="inline-flex items-center gap-1 text-xs text-purple-600 font-medium mt-1">
                                        ⭐ По умолчанию
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if(!($defaultAddress && $address['id'] == $defaultAddress['id']))
                                <button wire:click="setDefault({{ $address['id'] }})"
                                        title="Сделать основным"
                                        class="text-gray-400 hover:text-purple-500 text-sm transition p-1">
                                    ☆
                                </button>
                            @endif
                            <button wire:click="delete({{ $address['id'] }})"
                                    wire:confirm="Удалить этот адрес?"
                                    title="Удалить"
                                    class="text-gray-400 hover:text-red-500 text-sm transition p-1">
                                🗑
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
                    <p class="text-4xl mb-3">🗺</p>
                    <p class="text-sm">Добавьте адреса для быстрого оформления заказов</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
