<div x-data="{
    map: null,
    courierMarker: null,
    init() {
        this.map = L.map('courier-map').setView([{{ $getRecord()->target_lat }}, {{ $getRecord()->target_lng }}], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
        
        // Target (Customer)
        L.marker([{{ $getRecord()->target_lat }}, {{ $getRecord()->target_lng }}], {
            icon: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/25/25694.png',
                iconSize: [24, 24]
            })
        }).addTo(this.map).bindPopup('Клиент');

        // Courier (Simulation)
        this.courierMarker = L.circleMarker([{{ $getRecord()->target_lat + 0.005 }}, {{ $getRecord()->target_lng + 0.005 }}], {
            radius: 8,
            fillColor: '#8B5CF6',
            color: '#fff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        }).addTo(this.map).bindPopup('Курьер (Онлайн)');

        // Draw Route (Simulated)
        L.polyline([
            [{{ $getRecord()->target_lat }}, {{ $getRecord()->target_lng }}],
            [{{ $getRecord()->target_lat + 0.005 }}, {{ $getRecord()->target_lng + 0.005 }}]
        ], {color: '#8B5CF6', dashArray: '5, 10'}).addTo(this.map);
    }
}" x-init="init()" wire:ignore>
    <div id="courier-map" class="w-full h-[400px] rounded-3xl border border-white/10 shadow-2xl relative z-0">
        <div class="absolute top-4 left-4 z-[1000] bg-black/80 backdrop-blur-md p-3 rounded-2xl border border-purple-500/20 flex items-center space-x-3 shadow-xl">
             <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
             <span class="text-xs font-bold uppercase tracking-widest text-white/50">LIVE TRACKING</span>
        </div>
    </div>

    <!-- Styles for Leaflet if not included -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</div>
