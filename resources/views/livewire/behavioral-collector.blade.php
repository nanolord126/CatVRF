@php
use Illuminate\Support\Facades\Auth;
@endphp

<div x-data="behavioralCollector()" x-init="initCollector()" style="display: none;">
    <!-- Behavioral Collector Component -->
    <!-- This component is invisible and runs in the background -->
</div>

@push('scripts')
<script>
    function behavioralCollector() {
        return {
            collector: null,
            enabled: {{ $enabled ? 'true' : 'false' }},
            action: '{{ $action }}',
            throttleMs: {{ config('behavioral.collection.throttle_ms', 50) }},
            batchIntervalMs: {{ config('behavioral.collection.batch_interval_ms', 30000) }},
            collecting: false,
            batchTimer: null,

            initCollector() {
                if (!this.enabled || typeof BehavioralCollector === 'undefined') {
                    return;
                }

                // Initialize collector
                this.collector = new BehavioralCollector({
                    throttleMs: this.throttleMs,
                    batchIntervalMs: this.batchIntervalMs,
                    enabled: true
                });

                // Override sendBatch to send to backend
                this.collector.sendBatch = async (action = 'batch') => {
                    if (!this.collector.hasData()) {
                        return;
                    }

                    const signals = this.collector.collectSignals(action);
                    this.collector.clearBuffers();

                    await this.sendToBackend(signals);
                };

                // Start collection
                this.collector.start();
                this.collecting = true;

                // Listen for Livewire events
                window.addEventListener('startBehavioralCollection', (e) => {
                    if (e.detail?.action) {
                        this.action = e.detail.action;
                    }
                });

                window.addEventListener('stopBehavioralCollection', () => {
                    if (this.collector) {
                        this.collector.stop();
                        this.collecting = false;
                    }
                });

                window.addEventListener('collectBehavioralSignals', (e) => {
                    if (this.collector && e.detail?.action) {
                        this.collector.sendBatch(e.detail.action);
                    }
                });

                // Auto-send on page unload
                window.addEventListener('beforeunload', () => {
                    if (this.collector && this.collecting) {
                        this.collector.sendBatch(this.action);
                    }
                });

                console.debug('[BehavioralCollector] Initialized for action:', this.action);
            },

            async sendToBackend(signals) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    
                    const response = await fetch('/api/behavioral/collect', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            action: this.action,
                            signals: signals,
                        }),
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        console.warn('[BehavioralCollector] Failed to send signals:', response.status);
                    }
                } catch (error) {
                    console.error('[BehavioralCollector] Error sending signals:', error);
                }
            }
        };
    }
</script>
@endpush
