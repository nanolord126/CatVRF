<div class="min-h-screen bg-gradient-to-br from-yellow-900 via-slate-900 to-slate-900 p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">3D Просмотр ювелирных украшений</h1>
            <p class="text-slate-300">360° вращение с высочайшей четкостью</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 3D Viewer -->
            <div class="lg:col-span-2">
                <div id="canvas-jewelry" class="w-full h-[600px] bg-gradient-to-b from-slate-800 to-slate-900 rounded-lg shadow-2xl overflow-hidden flex items-center justify-center">
                    <p class="text-slate-400">Загрузка 3D модели...</p>
                </div>

                <!-- Rotation Controls -->
                <div class="mt-6 flex gap-4 justify-center">
                    <button wire:click="rotate('left')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        ← Влево
                    </button>
                    <button wire:click="rotate('right')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Вправо →
                    </button>
                    <button wire:click="rotate('up')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        ↑ Вверх
                    </button>
                    <button wire:click="rotate('down')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        ↓ Вниз
                    </button>
                </div>

                <!-- Zoom Controls -->
                <div class="mt-4 flex gap-4 justify-center">
                    <button wire:click="zoomIn()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                        🔍+ Увеличить
                    </button>
                    <button wire:click="zoomOut()" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        🔍- Уменьшить
                    </button>
                </div>
            </div>

            <!-- Details Panel -->
            <div class="lg:col-span-1">
                <div class="bg-slate-800 rounded-lg p-6 space-y-6 sticky top-8">
                    <!-- Product Info -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ $jewelryData['name'] ?? 'Украшение' }}</h3>
                        <p class="text-slate-300 mb-2">Тип: <strong>{{ ucfirst($jewelryData['type'] ?? 'ring') }}</strong></p>
                        <p class="text-slate-300">Сертификат: <strong>{{ $jewelryData['certificate'] ?? 'GIA' }}</strong></p>
                    </div>

                    <!-- Material Selection -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Материал</h3>
                        <div class="space-y-2">
                            @foreach($jewelryData['materials'] ?? [] as $material)
                                <button wire:click="selectMaterial('{{ $material }}')"
                                    class="w-full px-4 py-2 rounded-lg transition text-left {{ $selectedMaterial === $material ? 'bg-yellow-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">
                                    {{ match($material) {
                                        'gold' => '🟡 Золото',
                                        'silver' => '⚪ Серебро',
                                        'platinum' => '⚡ Платина',
                                        'rose_gold' => '🌹 Розовое золото',
                                        default => ucfirst($material),
                                    } }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Size Selection -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Размер</h3>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($jewelryData['sizes'] ?? ['small', 'medium', 'large'] as $size)
                                <button wire:click="selectSize('{{ $size }}')"
                                    class="px-3 py-2 rounded-lg transition font-semibold text-sm {{ $selectedSize === $size ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">
                                    {{ match($size) {
                                        'small' => 'S',
                                        'medium' => 'M',
                                        'large' => 'L',
                                        default => $size,
                                    } }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Certificate Info -->
                    <div class="bg-slate-700 rounded-lg p-4">
                        <p class="text-yellow-400 font-semibold text-sm mb-2">✓ СЕРТИФИЦИРОВАНО</p>
                        <p class="text-slate-300 text-sm">
                            Подлинность подтверждена международным сертификатом
                        </p>
                    </div>

                    <!-- Price -->
                    <div class="pt-6 border-t border-slate-700">
                        <p class="text-slate-400 text-sm mb-2">ЦЕНА</p>
                        <p class="text-3xl font-bold text-yellow-400">${{ number_format($jewelryData['price'] ?? 0, 0) }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <button class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                            🛒 Добавить в корзину
                        </button>
                        <button class="w-full px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition">
                            ❤️ Добавить в избранное
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
