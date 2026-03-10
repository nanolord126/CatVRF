<div class="fi-wi-widget" 
     x-data="{
        map: null,
        heatmapLayer: null,
        init() {
            setTimeout(() => {
                if (typeof L === 'undefined' || typeof HeatmapOverlay === 'undefined') { 
                    return; 
                }
                this.map = L.map(this.$refs.map).setView([55.7558, 37.6173], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OS'
                }).addTo(this.map);

                let cfg = {
                    'radius': 40,
                    'maxOpacity': .6,
                    'scaleRadius': false,
                    'useLocalExtrema': true,
                    latField: 'lat',
                    lngField: 'lng',
                    valueField: 'count'
                };

                this.heatmapLayer = new HeatmapOverlay(cfg);
                this.map.addLayer(this.heatmapLayer);
                
                let data = {
                    max: 10,
                    data: @json($this->getHeatmapData())
                };
                this.heatmapLayer.setData(data);
            }, 1000);
        }
    }">
    <div x-ref="map" style="height: 450px; width: 100%; border-radius: 12px; overflow: hidden;" wire:ignore></div>
</div>
