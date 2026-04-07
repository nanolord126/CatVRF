<div class="min-h-screen bg-gray-50"
     x-data="deliveryTracker()"
     x-init="init()"
     @subscribe-tracking.window="subscribeEcho($event.detail.deliveryOrderId)">

    <div class="max-w-4xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">🚴 Трекинг доставки</h1>
            <p class="text-gray-500 text-sm mt-1">Отслеживайте курьера в реальном времени</p>
        </div>

        @if(empty($activeDeliveries))
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
                <p class="text-4xl mb-3">🏁</p>
                <p class="text-sm">Нет активных доставок</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Левая панель: выбор + статус --}}
                <div class="space-y-4">

                    {{-- Список активных доставок --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-sm font-semibold text-gray-700 mb-3">Активные доставки</p>
                        <div class="space-y-2">
                            @foreach($activeDeliveries as $delivery)
                                <button wire:click="selectDelivery({{ $delivery['id'] }})"
                                        class="w-full text-left p-3 rounded-xl border transition
                                            {{ $deliveryOrderId === $delivery['id']
                                                ? 'border-purple-300 bg-purple-50'
                                                : 'border-gray-100 hover:border-gray-200' }}">
                                    <p class="text-sm font-medium text-gray-800">
                                        Заказ #{{ substr($delivery['uuid'] ?? $delivery['id'], 0, 8) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ match($delivery['status'] ?? '') {
                                            'pending' => '⏳ Ожидает', 'assigned' => '🚴 Назначен',
                                            'picked_up' => '📦 Забрали', 'in_transit' => '🚗 В пути',
                                            default => '📍 ' . ($delivery['status'] ?? '')
                                        } }}
                                    </p>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Статус и timeline --}}
                    @if($deliveryOrderId)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <p class="text-sm font-semibold text-gray-700 mb-4">Статус доставки</p>
                            <div class="space-y-3">
                                @foreach($timeline as $step)
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0
                                            {{ $step['done']
                                                ? 'bg-green-500 text-white'
                                                : ($step['active'] ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-400') }}
                                            text-xs font-bold">
                                            {{ $step['done'] ? '✓' : ($step['active'] ? '●' : '○') }}
                                        </div>
                                        <span class="text-sm {{ $step['active'] ? 'font-semibold text-gray-900' : 'text-gray-500' }}">
                                            {{ $step['label'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            @if($estimatedTime)
                                <div class="mt-4 p-3 bg-blue-50 rounded-xl">
                                    <p class="text-xs text-blue-600 font-medium">⏱ Ожидаемое время</p>
                                    <p class="text-sm font-semibold text-blue-800 mt-0.5">{{ $estimatedTime }}</p>
                                </div>
                            @endif

                            @if($isDelivered)
                                <div class="mt-4 p-3 bg-green-50 rounded-xl text-center">
                                    <p class="text-2xl mb-1">🎉</p>
                                    <p class="text-sm font-semibold text-green-700">Доставлено!</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Карта --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-700">Карта</p>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full animate-pulse"
                                      :class="isTracking ? 'bg-green-400' : 'bg-gray-300'"></span>
                                <span class="text-xs text-gray-400"
                                      x-text="isTracking ? 'Трекинг активен' : 'Ожидание...'"></span>
                            </span>
                        </div>

                        <div id="delivery-map" class="h-80 bg-gray-100 relative">
                            {{-- Карта инициализируется через Alpine/Leaflet --}}
                            <div class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm"
                                 x-show="!mapReady">
                                <div class="text-center">
                                    <p class="text-3xl mb-2">🗺</p>
                                    <p>{{ $deliveryOrderId ? 'Загрузка карты...' : 'Выберите доставку' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Координаты курьера --}}
                        @if($courierLat && $courierLon)
                            <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex items-center gap-4 text-xs text-gray-500">
                                <span>🚴 Курьер: {{ round($courierLat, 5) }}, {{ round($courierLon, 5) }}</span>
                                @if($deliveryLat && $deliveryLon)
                                    <span>📍 Назначение: {{ round($deliveryLat, 5) }}, {{ round($deliveryLon, 5) }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function deliveryTracker() {
    return {
        map: null,
        courierMarker: null,
        pickupMarker: null,
        deliveryMarker: null,
        isTracking: false,
        mapReady: false,
        echoChannel: null,

        init() {
            this.$watch('$wire.deliveryOrderId', (newId) => {
                if (newId) {
                    this.$nextTick(() => this.initMap());
                }
            });
        },

        initMap() {
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
            const container = document.getElementById('delivery-map');
            if (!container) return;

            const lat = this.$wire.courierLat || this.$wire.pickupLat || 55.751244;
            const lon = this.$wire.courierLon || this.$wire.pickupLon || 37.618423;

            this.map = L.map(container).setView([lat, lon], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            if (this.$wire.pickupLat && this.$wire.pickupLon) {
                this.pickupMarker = L.marker([this.$wire.pickupLat, this.$wire.pickupLon], {
                    icon: L.divIcon({ html: '📦', iconSize: [32, 32], className: 'text-2xl' })
                }).addTo(this.map).bindPopup('Пункт выдачи');
            }

            if (this.$wire.deliveryLat && this.$wire.deliveryLon) {
                this.deliveryMarker = L.marker([this.$wire.deliveryLat, this.$wire.deliveryLon], {
                    icon: L.divIcon({ html: '📍', iconSize: [32, 32], className: 'text-2xl' })
                }).addTo(this.map).bindPopup('Место доставки');
            }

            if (this.$wire.courierLat && this.$wire.courierLon) {
                this.updateCourierOnMap(this.$wire.courierLat, this.$wire.courierLon);
            }

            this.mapReady = true;
        },

        updateCourierOnMap(lat, lon) {
            if (!this.map) return;
            if (this.courierMarker) {
                this.courierMarker.setLatLng([lat, lon]);
            } else {
                this.courierMarker = L.marker([lat, lon], {
                    icon: L.divIcon({ html: '🚴', iconSize: [32, 32], className: 'text-2xl' })
                }).addTo(this.map).bindPopup('Курьер');
            }
            this.map.panTo([lat, lon]);
        },

        subscribeEcho(deliveryOrderId) {
            if (!deliveryOrderId) return;
            if (this.echoChannel) this.echoChannel.stopListening('CourierLocationUpdated');

            this.isTracking = true;
            this.echoChannel = window.Echo.private(`delivery.${deliveryOrderId}`)
                .listen('CourierLocationUpdated', (e) => {
                    this.$wire.call('updateCourierPosition', e.lat, e.lon);
                    this.updateCourierOnMap(e.lat, e.lon);
                })
                .listen('DeliveryStatusUpdated', (e) => {
                    this.$wire.call('updateStatus', e.status);
                });
        },
    };
}
</script>
@endpush
