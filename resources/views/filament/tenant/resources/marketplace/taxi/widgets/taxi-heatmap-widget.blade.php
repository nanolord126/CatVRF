<x-filament::widget>
    <x-filament::card>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold tracking-tight">🗺️ Монитор Спроса и Геолокация (GPS/ГЛОНАСС)</h3>
            <div class="flex space-x-2 text-xs">
                <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-blue-500 mr-1"></span> Эконом</span>
                <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-yellow-500 mr-1"></span> Комфорт</span>
                <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-purple-500 mr-1"></span> Бизнес</span>
                <span class="flex items-center ml-4 px-2 py-1 bg-red-100 text-red-700 rounded border border-red-200">💎 Зона Surge (Повышенный спрос)</span>
                <span class="flex items-center ml-4 px-2 py-1 bg-emerald-100 text-emerald-700 rounded border border-emerald-200 animate-pulse">🤖 AI Рекомендация</span>
            </div>
        </div>

        <div 
            wire:ignore
            class="relative w-full h-[600px] bg-slate-100 rounded-lg overflow-hidden border border-slate-200"
            style="z-index: 1;"
        >
            <div id="taxi-heatmap-map" class="absolute inset-0"></div>
            
            {{-- Информационное табло --}}
            <div class="absolute top-4 right-4 z-[999] bg-white/90 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-slate-100 w-64 pointer-events-none">
                <p class="text-[10px] uppercase font-bold text-slate-500 mb-2">Статус сети (2026 Engine)</p>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm italic">
                        <span>Активно водителей:</span>
                        <span class="font-mono text-emerald-600 font-bold">42</span>
                    </div>
                    <div class="flex justify-between text-sm italic">
                        <span>Зон наценки:</span>
                        <span class="font-mono text-red-600 font-bold">5</span>
                    </div>
                    <div class="flex justify-between text-sm italic border-t pt-2 border-slate-200">
                        <span>Наценка (Surge):</span>
                        <span class="px-2 py-0.5 bg-red-600 text-white rounded text-[10px] font-bold">X1.8 MAX</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="flex items-center text-[10px] text-emerald-500">
                        <span class="relative flex h-2 w-2 mr-1">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        SYNC: OK (ГЛОНАСС)
                    </span>
                    <span class="text-[10px] text-slate-400">Delay: 32ms</span>
                </div>
            </div>

            {{-- Легенда Surge Logic --}}
            <div class="absolute bottom-4 left-4 z-[999] bg-white/80 p-3 rounded-lg text-[9px] text-slate-600 max-w-xs border border-white/50 shadow-sm">
                <b>Алгоритм наценки:</b> Деление прибыли 50/50 между платформой и флотом.
                <br><b>GPS/Glonass Integration:</b> Активно. Фильтр фрода по трекам включен.
            </div>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var map = L.map('taxi-heatmap-map').setView([55.751244, 37.618423], 12);
                
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors'
                }).addTo(map);

                const data = @json($this->getMapData());

                // Отрисовка зон спроса (Surge Zones)
                data.surge_zones.forEach(zone => {
                    const polygon = L.polygon(zone.path, {
                        color: 'red',
                        fillColor: '#f03',
                        fillOpacity: 0.2,
                        weight: 2
                    }).addTo(map);
                    
                    polygon.bindTooltip(`<b>${zone.name}</b><br>Surge: <b>x${zone.multiplier}</b>`, {
                        permanent: false, 
                        direction: 'top'
                    });
                });

                // Отрисовка водителей
                const colors = {
                    'economy': '#3b82f6', // blue
                    'comfort': '#f59e0b', // amber/yellow
                    'business': '#a855f7' // purple
                };

                data.drivers.forEach(driver => {
                    L.circleMarker([driver.lat, driver.lng], {
                        radius: 6,
                        fillColor: colors[driver.category] || '#3b82f6',
                        color: "#fff",
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(map);
                });

                // Отрисовка AI предсказаний (Smart Hotspots)
                data.predictions.forEach(spot => {
                    L.circle([spot.lat, spot.lng], {
                        radius: 300,
                        color: '#10b981', // emerald-500
                        fillColor: '#10b981',
                        fillOpacity: 0.15,
                        dashArray: '5, 10'
                    }).addTo(map);

                    L.marker([spot.lat, spot.lng], {
                        icon: L.divIcon({
                            className: 'ai-prediction-label',
                            html: `<div class="bg-emerald-500 text-white rounded px-2 py-0.5 text-[8px] font-bold shadow-sm whitespace-nowrap animate-bounce">🤖 SMART SPOT: +${spot.expected_avg_fare}₽</div>`,
                        })
                    }).addTo(map);
                });
            });
        </script>
    </x-filament::card>
</x-filament::widget>
