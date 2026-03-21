<div class="min-h-screen bg-gradient-to-br from-purple-900 via-slate-900 to-slate-900 p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">Примерочная 3D</h1>
            <p class="text-slate-300">Виртуальная примерка одежды</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 3D Viewer -->
            <div class="lg:col-span-2">
                <div id="canvas-fitting-room" class="w-full h-[600px] bg-gradient-to-b from-slate-800 to-slate-900 rounded-lg shadow-2xl overflow-hidden flex items-center justify-center">
                    <div class="text-center">
                        <p class="text-slate-400 text-lg">Загрузка 3D модели...</p>
                        <div class="mt-4 animate-spin">
                            <p class="text-2xl">🔄</p>
                        </div>
                    </div>
                </div>

                <!-- Avatar Options -->
                @if($showAvatarOptions)
                    <div class="mt-6 grid grid-cols-4 gap-4">
                        <button wire:click="selectBodyType('slim')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                            Стройное
                        </button>
                        <button wire:click="selectBodyType('regular')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            Среднее
                        </button>
                        <button wire:click="selectBodyType('plus')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                            Полное
                        </button>
                        <button wire:click="toggleAvatarOptions()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                            Готово
                        </button>
                    </div>
                @endif
            </div>

            <!-- Controls Panel -->
            <div class="lg:col-span-1">
                <div class="bg-slate-800 rounded-lg p-6 space-y-6">
                    <!-- Size Selection -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Размер</h3>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($availableSizes as $size)
                                <button wire:click="selectSize('{{ $size }}')"
                                    class="px-3 py-2 rounded-lg transition {{ $selectedSize === $size ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">
                                    {{ $size }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Color Selection -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Цвет</h3>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($availableColors as $color)
                                <button wire:click="selectColor('{{ $color }}')"
                                    class="w-10 h-10 rounded-lg border-2 transition cursor-pointer"
                                    style="background-color: {{ match($color) {
                                        'black' => '#000000',
                                        'white' => '#FFFFFF',
                                        'red' => '#FF0000',
                                        'blue' => '#0000FF',
                                        'navy' => '#000080',
                                        'grey' => '#808080',
                                    } }}; border-color: {{ $selectedColor === $color ? '#00FF00' : 'transparent' }}">
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Body Type Selection -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Тип фигуры</h3>
                        <button wire:click="toggleAvatarOptions()" class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                            👤 {{ match($selectedBodyType) {
                                'slim' => 'Стройное',
                                'regular' => 'Среднее',
                                'plus' => 'Полное',
                            } }}
                        </button>
                    </div>

                    <!-- Add to Cart -->
                    <button wire:click="addToCart()" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition mt-8">
                        🛒 Добавить в корзину
                    </button>

                    <!-- Product Info -->
                    <div class="pt-6 border-t border-slate-700">
                        <h4 class="text-sm font-semibold text-slate-400 mb-2">ИНФОРМАЦИЯ</h4>
                        <p class="text-slate-300 text-sm mb-2">Размер: <strong>{{ $selectedSize }}</strong></p>
                        <p class="text-slate-300 text-sm mb-2">Цвет: <strong>{{ ucfirst($selectedColor) }}</strong></p>
                        <p class="text-slate-300 text-sm">Тип: <strong>Рубашка</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@latest/examples/js/loaders/GLTFLoader.js"></script>
