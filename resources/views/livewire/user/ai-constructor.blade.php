<div class="min-h-screen bg-gray-50" x-data="{ showAR: false, arUrl: '' }">

    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">🤖 AI-конструктор</h1>
            <p class="text-gray-500 text-sm mt-1">Загрузите фото — AI подберёт образ и товары именно для вас</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Левая панель: выбор вертикали и загрузка --}}
            <div class="lg:col-span-1 space-y-4">

                {{-- Выбор вертикали --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Выберите направление</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($verticals as $key => $label)
                            <button wire:click="selectVertical('{{ $key }}')"
                                    class="py-2 px-3 rounded-xl text-sm font-medium border transition
                                        {{ $vertical === $key
                                            ? 'bg-purple-600 text-white border-purple-600'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-purple-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Загрузка фото --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Загрузите фото</p>

                    <div x-data="{ dragging: false }"
                         @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="dragging = false"
                         :class="dragging ? 'border-purple-400 bg-purple-50' : 'border-gray-200'"
                         class="border-2 border-dashed rounded-xl p-6 text-center transition-colors">

                        @if($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="preview"
                                 class="w-full h-40 object-cover rounded-lg mb-3">
                        @else
                            <div class="text-4xl mb-3">📸</div>
                            <p class="text-sm text-gray-500">Перетащите или нажмите</p>
                        @endif

                        <label class="cursor-pointer mt-2 inline-block">
                            <span class="text-purple-600 text-sm font-medium hover:underline">Выбрать файл</span>
                            <input wire:model="photo" type="file" accept="image/*" class="hidden">
                        </label>

                        @error('photo')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Параметры вертикали --}}
                    @if($vertical === 'furniture')
                        <div class="mt-4">
                            <label class="text-xs text-gray-500">Желаемый стиль</label>
                            <input wire:model="params.style" type="text" placeholder="Скандинавский, лофт, классика..."
                                   class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                        </div>
                        <div class="mt-2">
                            <label class="text-xs text-gray-500">Бюджет (₽)</label>
                            <input wire:model.number="params.budget" type="number" placeholder="100000"
                                   class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                        </div>
                    @elseif($vertical === 'food')
                        <div class="mt-4">
                            <label class="text-xs text-gray-500">Диета</label>
                            <select wire:model="params.diet"
                                    class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                                <option value="normal">Обычное</option>
                                <option value="vegan">Веганское</option>
                                <option value="keto">Кето</option>
                                <option value="paleo">Палео</option>
                            </select>
                        </div>
                    @elseif($vertical === 'fashion')
                        <div class="mt-4">
                            <label class="text-xs text-gray-500">Мероприятие</label>
                            <input wire:model="params.event" type="text" placeholder="Офис, вечеринка, свадьба..."
                                   class="w-full mt-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                        </div>
                    @endif

                    <button wire:click="run"
                            wire:loading.attr="disabled"
                            :disabled="{{ $isProcessing ? 'true' : 'false' }}"
                            class="w-full mt-4 bg-purple-600 hover:bg-purple-700 disabled:opacity-50
                                   text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="run">✨ Запустить AI</span>
                        <span wire:loading wire:target="run" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Анализирую...
                        </span>
                    </button>

                    @if($errorMessage)
                        <p class="text-red-500 text-sm mt-2">{{ $errorMessage }}</p>
                    @endif
                </div>
            </div>

            {{-- Правая панель: результат + сохранённые дизайны --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Результат AI --}}
                @if($hasResult && !empty($result))
                    <div class="bg-white rounded-2xl border border-purple-200 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-2xl">✨</span>
                            <h2 class="text-lg font-semibold text-gray-800">Результат AI-анализа</h2>
                        </div>

                        @if(isset($result['ar_link']))
                            <div class="mb-4">
                                <button @click="showAR = true; arUrl = '{{ $result['ar_link'] ?? '' }}'"
                                        class="flex items-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600
                                               text-white px-4 py-2 rounded-xl text-sm font-medium hover:shadow-lg transition">
                                    🕶 AR-примерка
                                </button>
                            </div>
                        @endif

                        @if(isset($result['style_profile']))
                            <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Профиль стиля</p>
                                <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ json_encode($result['style_profile'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif

                        @if(isset($result['suggestions']) && is_array($result['suggestions']))
                            <div>
                                <p class="text-sm font-semibold text-gray-700 mb-3">Рекомендуемые товары</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach(array_slice($result['suggestions'], 0, 6) as $item)
                                        <div class="border border-gray-100 rounded-xl p-3 hover:border-purple-200 transition">
                                            <p class="text-sm font-medium text-gray-800">{{ $item['name'] ?? 'Товар' }}</p>
                                            @if(isset($item['price']))
                                                <p class="text-sm text-gray-600 mt-1">{{ number_format($item['price'] / 100, 2) }} ₽</p>
                                            @endif
                                            @if(isset($item['in_stock']))
                                                <span class="text-xs {{ $item['in_stock'] ? 'text-green-600' : 'text-gray-400' }}">
                                                    {{ $item['in_stock'] ? '✓ В наличии' : 'Нет в наличии' }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Сохранённые дизайны --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Мои дизайны ({{ count($savedDesigns) }})</h2>
                    @forelse($savedDesigns as $design)
                        <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-sm">
                                    {{ match($design['vertical']) {
                                        'beauty' => '💄', 'furniture' => '🛋', 'food' => '🍽',
                                        'fashion' => '👗', 'fitness' => '💪', 'hotel' => '🏨', default => '🤖'
                                    } }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $design['label'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $design['created'] }}</p>
                                </div>
                            </div>
                            <button wire:click="deleteDesign({{ $design['id'] }})"
                                    wire:confirm="Удалить этот дизайн?"
                                    class="text-gray-400 hover:text-red-500 transition text-sm">
                                🗑
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Создайте свой первый AI-дизайн!</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- AR Modal --}}
    <div x-show="showAR" x-cloak
         class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center"
         @click.self="showAR = false">
        <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="font-semibold">🕶 AR-примерка</h3>
                <button @click="showAR = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="p-4">
                <iframe :src="arUrl" class="w-full h-96 rounded-xl border border-gray-200" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</div>
