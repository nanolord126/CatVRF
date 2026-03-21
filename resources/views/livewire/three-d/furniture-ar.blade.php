<div class="min-h-screen bg-gradient-to-br from-amber-900 via-slate-900 to-slate-900 p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">AR Мебель в вашем интерьере</h1>
            <p class="text-slate-300">Посмотрите, как мебель выглядит в вашей комнате</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- AR Viewer -->
            <div class="lg:col-span-2">
                <div id="canvas-furniture-ar" class="w-full h-[600px] bg-gradient-to-b from-slate-800 to-slate-900 rounded-lg shadow-2xl overflow-hidden flex items-center justify-center relative">
                    @if($showARView)
                        <div class="absolute inset-0 flex items-center justify-center">
                            <p class="text-white text-lg">📱 AR View активирован</p>
                        </div>
                    @else
                        <div class="text-center">
                            <p class="text-slate-400 text-lg">3D модель мебели</p>
                        </div>
                    @endif

                    <!-- Room Dimensions Display -->
                    @if($showPlacementGuide)
                        <div class="absolute inset-0 border-4 border-dashed border-blue-500 flex items-end justify-end p-4">
                            <div class="bg-blue-500/20 backdrop-blur px-4 py-2 rounded-lg">
                                <p class="text-white text-sm">{{ $roomDimensions['width'] }}см × {{ $roomDimensions['depth'] }}см</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Configuration Panel -->
            <div class="lg:col-span-1">
                <div class="bg-slate-800 rounded-lg p-6 space-y-6 sticky top-8">
                    <!-- Product Info -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ $furnitureData['name'] ?? 'Мебель' }}</h3>
                        <p class="text-slate-300 mb-4">Тип: <strong>{{ ucfirst($furnitureData['type'] ?? 'unknown') }}</strong></p>

                        <!-- Dimensions -->
                        <div class="bg-slate-700 rounded p-3 mb-4">
                            <p class="text-slate-300 text-sm mb-1">📏 Размеры:</p>
                            <p class="text-slate-200 text-sm">
                                {{ $furnitureData['dimensions']['width'] ?? 0 }} ×
                                {{ $furnitureData['dimensions']['depth'] ?? 0 }} ×
                                {{ $furnitureData['dimensions']['height'] ?? 0 }} см
                            </p>
                        </div>
                    </div>

                    <!-- Color Selection -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Цвет</h3>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($furnitureData['colors'] ?? [] as $color)
                                <button wire:click="selectColor('{{ $color }}')"
                                    class="px-3 py-2 rounded-lg transition text-sm font-semibold {{ $selectedColor === $color ? 'ring-2 ring-green-400 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">
                                    {{ ucfirst($color) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- AR Preview -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Предпросмотр</h3>
                        <button wire:click="enableARView()" class="w-full px-4 py-3 {{ $showARView ? 'bg-purple-600' : 'bg-slate-700 hover:bg-slate-600' }} text-white rounded-lg transition font-semibold">
                            🔮 {{ $showARView ? 'Скрыть AR' : 'Показать AR' }}
                        </button>
                    </div>

                    <!-- Placement Guide -->
                    <div>
                        <button wire:click="togglePlacementGuide()" class="w-full px-4 py-3 {{ $showPlacementGuide ? 'bg-blue-600' : 'bg-slate-700 hover:bg-slate-600' }} text-white rounded-lg transition font-semibold">
                            📐 {{ $showPlacementGuide ? 'Скрыть размеры' : 'Показать размеры' }}
                        </button>
                    </div>

                    <!-- Price -->
                    <div class="pt-6 border-t border-slate-700">
                        <p class="text-slate-400 text-sm mb-2">ЦЕНА</p>
                        <p class="text-3xl font-bold text-green-400">{{ number_format($furnitureData['price'] ?? 0, 0) }} ₽</p>
                    </div>

                    <!-- Add to Cart -->
                    <button wire:click="addToCart()" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                        🛒 Добавить в корзину
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ar.js@latest/three.js/ar.js"></script>
