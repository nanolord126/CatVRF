<div class="min-h-screen bg-gradient-to-br from-red-900 via-slate-900 to-slate-900 p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">Конфигуратор автомобилей</h1>
            <p class="text-slate-300">Собирайте и настраивайте свой автомобиль</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 3D Viewer -->
            <div class="lg:col-span-2">
                <div id="canvas-vehicle" class="w-full h-[600px] bg-gradient-to-b from-slate-800 to-slate-900 rounded-lg shadow-2xl overflow-hidden"></div>

                <!-- Color Preview -->
                <div class="mt-6 grid grid-cols-4 gap-2">
                    @foreach($vehicleData['colors'] ?? ['#000000'] as $color)
                        <button wire:click="selectColor('{{ $color }}')"
                            class="py-4 rounded-lg border-2 transition cursor-pointer font-semibold"
                            style="background-color: {{ $color }}; border-color: {{ $selectedColor === $color ? '#00FF00' : 'transparent' }}; color: {{ $selectedColor === $color ? '#00FF00' : '#FFFFFF' }}">
                            {{ strtoupper(substr($color, 1)) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Configuration Panel -->
            <div class="lg:col-span-1">
                <div class="bg-slate-800 rounded-lg p-6 space-y-6 sticky top-8">
                    <!-- Vehicle Info -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ $vehicleData['brand'] ?? 'Автомобиль' }} {{ $vehicleData['model'] ?? '' }}</h3>
                        <p class="text-slate-300">Базовая цена: <strong>${{ number_format($vehicleData['basePrice'] ?? 0, 0) }}</strong></p>
                    </div>

                    <!-- Interior Selection -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Интерьер</h3>
                        <div class="space-y-2">
                            @foreach($vehicleData['interiors'] ?? [] as $interior)
                                <button wire:click="selectInterior('{{ $interior }}')"
                                    class="w-full px-4 py-2 rounded-lg transition text-left {{ $selectedInterior === $interior ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">
                                    {{ ucfirst($interior) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Options -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-3">Опции</h3>
                        <div class="space-y-2">
                            @foreach($vehicleData['options'] ?? [] as $option)
                                <label class="flex items-center p-3 bg-slate-700 rounded-lg cursor-pointer hover:bg-slate-600 transition">
                                    <input type="checkbox" 
                                        wire:click="toggleOption({{ $option['id'] }})"
                                        {{ in_array($option['id'], $selectedOptions) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded">
                                    <span class="ml-3 flex-1 text-slate-300">{{ $option['name'] }}</span>
                                    <span class="text-slate-400 text-sm">+${{ number_format($option['price'] ?? 0, 0) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Total Price -->
                    <div class="pt-6 border-t border-slate-700">
                        <p class="text-slate-400 text-sm mb-2">ИТОГОВАЯ ЦЕНА</p>
                        <p class="text-3xl font-bold text-green-400">${{ number_format($price, 0) }}</p>
                    </div>

                    <!-- Action Button -->
                    <button class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                        🛒 Заказать
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
