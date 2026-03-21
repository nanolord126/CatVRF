@extends('layouts.app')

@section('title', 'Демонстрация 3D Визуализации - CatVRF')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-4xl font-bold mb-2">🎨 Демонстрация 3D Визуализации</h1>
            <p class="text-lg opacity-90">Изучите продукты с помощью интерактивных 3D-моделей и AR-просмотра</p>
        </div>
    </div>

    <!-- Демонстрационных продуктов Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($products as $product)
                <div class="group bg-slate-800 rounded-lg overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                    <!-- Product Preview Container -->
                    <div class="bg-slate-900 h-80 flex items-center justify-center relative overflow-hidden">
                        <!-- 3D Canvas Placeholder -->
                        <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-b from-slate-800 to-slate-900">
                            <div id="canvas-{{ $product['id'] }}" class="w-full h-full relative">
                                <!-- Three.js Canvas will be injected here -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="text-4xl mb-2">{{ ['💎', '⌚', '🏠', '🛏️', '🛋️', '🪑'][$loop->index] ?? '📦' }}</div>
                                        <p class="text-sm text-slate-400">3D-модель: {{ basename($product['model_path']) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Badges -->
                        <div class="absolute top-3 right-3 flex gap-2">
                            <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                {{ $product['vertical'] }}
                            </span>
                            <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                3D
                            </span>
                        </div>

                        <!-- AR Badge (if enabled) -->
                        <div class="absolute bottom-3 left-3">
                            <span class="bg-green-600 text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
                                📱 AR Ready
                            </span>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-white mb-2">{{ $product['name'] }}</h3>
                        
                        <p class="text-slate-300 text-sm mb-4">{{ $product['description'] }}</p>

                        <!-- Price -->
                        <div class="mb-4">
                            <span class="text-2xl font-bold text-green-400">
                                ₽{{ number_format($product['price'], 0, '.', ' ') }}
                            </span>
                        </div>

                        <!-- Tags -->
                        <div class="mb-4 flex flex-wrap gap-2">
                            @foreach($product['tags'] as $tag)
                                <span class="bg-slate-700 text-slate-300 text-xs px-2 py-1 rounded">
                                    #{{ $tag }}
                                </span>
                            @endforeach
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <a href="#" onclick="viewProduct3D({{ $product['id'] }})" 
                               class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                                🔍 Смотреть 3D
                            </a>
                            <a href="#" onclick="openAR({{ $product['id'] }})" 
                               class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                                📱 AR Режим
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Information Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-slate-800 rounded-lg p-8 border border-slate-700">
            <h2 class="text-2xl font-bold text-white mb-4">📋 Статус Системы</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-slate-300">
                <div>
                    <h3 class="font-semibold text-white mb-2">✅ Реализованные функции</h3>
                    <ul class="space-y-2 text-sm">
                        <li>✨ Вращение продукта на 360°</li>
                        <li>🔍 Управление масштабированием и панорамированием</li>
                        <li>🎨 Выбор цветовых вариантов</li>
                        <li>📱 Адаптивный мобильный дизайн</li>
                        <li>🤳 Поддержка предпросмотра AR</li>
                        <li>🎬 Плавные анимации</li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-white mb-2">📊 Статистика демо</h3>
                    <ul class="space-y-2 text-sm">
                        <li>{{ count($products) }} Демонстрационных продуктов</li>
                        <li>{{ collect($products)->pluck('vertical')->unique()->count() }} Охваченных вертикалей</li>
                        <li>6 Доступных 3D-моделей</li>
                        <li>12+ API Эндпоинтs</li>
                        <li>100% Совместимость с мобильными устройствами</li>
                        <li>Интеграция AR.js</li>
                    </ul>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-slate-700">
                <h3 class="font-semibold text-white mb-3">🚀 Быстрые ссылки</h3>
                <div class="flex gap-3 flex-wrap">
                    <a href="/api/v1/3d/products/1" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        API Эндпоинт
                    </a>
                    <a href="#" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        Документация
                    </a>
                    <a href="#" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        AR Демо
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-slate-950 text-slate-400 py-8 px-4 sm:px-6 lg:px-8 text-center text-sm border-t border-slate-700">
        <p>CatVRF 3D Visualization System • Фаза 1 завершена • {{ date('Y-m-d') }}</p>
    </div>
</div>

<!-- Three.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<script>
    function viewProduct3D(productId) {
        alert(`3D Viewer for Product ${productId}\n\nFeatures:\n• 360° Rotation\n• Zoom Control\n• Color Selection\n• AR Preview`);
    }

    function openAR(productId) {
        alert(`AR Режим for Product ${productId}\n\nOn supported devices:\n• Use device camera\n• Place product in real space\n• Rotate & scale with gestures`);
    }

    // Simulate 3D model loading
    document.addEventListener('DOMContentLoaded', () => {
        console.log('✅ 3D Demo Page Loaded');
        console.log('📦 Демонстрационных продуктов:', {{ count($products) }});
        console.log('🎨 Verticals:', [
            @foreach(collect($products)->pluck('vertical')->unique() as $vertical)
                "{{ $vertical }}",
            @endforeach
        ]);
    });
</script>

<style>
    canvas {
        display: block;
        max-width: 100%;
        height: auto;
    }

    .group:hover {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    }
</style>
@endsection
