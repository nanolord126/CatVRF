/**
 * HeatmapService - API wrapper for heatmap data fetching and caching
 * Handles real-time updates, caching, error handling, and rate limiting
 * 
 * Usage:
 *   const service = new HeatmapService();
 *   service.getGeoHeatmap({
 *       tenant_id: 1,
 *       vertical: 'beauty',
 *       from_date: '2026-02-20',
 *       to_date: '2026-03-23'
 *   }).then(data => {
 *       console.log('Heatmap data:', data);
 *   }).catch(error => {
 *       console.error('Error:', error);
 *   });
 */

class HeatmapService {
    constructor(config = {}) {
        this.apiBaseUrl = config.apiBaseUrl || '/api/analytics/heatmaps';
        this.cacheEnabled = config.cacheEnabled !== false;
        this.cacheTtl = config.cacheTtl || 60 * 60 * 1000; // 1 hour
        this.requestTimeout = config.requestTimeout || 30000; // 30 seconds
        this.retryAttempts = config.retryAttempts || 3;
        this.retryDelay = config.retryDelay || 1000; // 1 second
        
        this.cache = new Map();
        this.pendingRequests = new Map();
        this.requestLog = [];
        this.maxLogSize = 100;
        
        // Event emitter for real-time updates
        this.listeners = new Map();
        
        // Initialize WebSocket connection for real-time updates
        if (config.enableRealtime !== false) {
            this.initRealtimeUpdates();
        }
    }

    /**
     * Get geo-heatmap data
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Heatmap data
     */
    async getGeoHeatmap(params = {}) {
        const cacheKey = this.buildCacheKey('geo', params);
        
        // Check cache first
        if (this.cacheEnabled && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTtl) {
                console.log('[HeatmapService] Returning cached geo-heatmap');
                return Promise.resolve(cached.data);
            } else {
                this.cache.delete(cacheKey);
            }
        }

        // Check if request is already pending (prevent duplicate requests)
        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }

        // Make request
        const request = this.fetchWithRetry('/geo', params);
        this.pendingRequests.set(cacheKey, request);

        try {
            const data = await request;
            
            // Cache the result
            if (this.cacheEnabled) {
                this.cache.set(cacheKey, {
                    data,
                    timestamp: Date.now()
                });
            }
            
            // Log request
            this.logRequest('geo', params, 'success');
            
            return data;
        } catch (error) {
            this.logRequest('geo', params, 'error', error.message);
            throw error;
        } finally {
            this.pendingRequests.delete(cacheKey);
        }
    }

    /**
     * Get click-heatmap data
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Heatmap data
     */
    async getClickHeatmap(params = {}) {
        const cacheKey = this.buildCacheKey('click', params);

        // Check cache first
        if (this.cacheEnabled && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTtl) {
                console.log('[HeatmapService] Returning cached click-heatmap');
                return Promise.resolve(cached.data);
            } else {
                this.cache.delete(cacheKey);
            }
        }

        // Check if request is already pending
        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }

        // Make request
        const request = this.fetchWithRetry('/click', params);
        this.pendingRequests.set(cacheKey, request);

        try {
            const data = await request;
            
            // Cache the result
            if (this.cacheEnabled) {
                this.cache.set(cacheKey, {
                    data,
                    timestamp: Date.now()
                });
            }
            
            // Log request
            this.logRequest('click', params, 'success');
            
            return data;
        } catch (error) {
            this.logRequest('click', params, 'error', error.message);
            throw error;
        } finally {
            this.pendingRequests.delete(cacheKey);
        }
    }

    /**
     * Invalidate cache for specific heatmap type
     * @param {string} type - 'geo' or 'click'
     * @param {Object} params - Optional: specific parameters to invalidate
     */
    invalidateCache(type, params = null) {
        if (!params) {
            // Invalidate all cache for this type
            for (const [key, _] of this.cache) {
                if (key.startsWith(`heatmap:${type}:`)) {
                    this.cache.delete(key);
                }
            }
            console.log(`[HeatmapService] Invalidated all ${type}-heatmap cache`);
        } else {
            // Invalidate specific cache key
            const cacheKey = this.buildCacheKey(type, params);
            this.cache.delete(cacheKey);
            console.log(`[HeatmapService] Invalidated ${type}-heatmap cache for:`, params);
        }
    }

    /**
     * Clear all cache
     */
    clearCache() {
        this.cache.clear();
        console.log('[HeatmapService] Cache cleared');
    }

    /**
     * Get cache statistics
     * @returns {Object} Cache stats
     */
    getCacheStats() {
        let cacheSize = 0;
        for (const [_, cached] of this.cache) {
            cacheSize += JSON.stringify(cached.data).length;
        }

        return {
            cacheSize: cacheSize,
            cacheItemCount: this.cache.size,
            pendingRequests: this.pendingRequests.size,
            requestLogSize: this.requestLog.length
        };
    }

    /**
     * Fetch with retry logic
     * @private
     */
    async fetchWithRetry(endpoint, params, attempt = 1) {
        try {
            const url = new URL(this.apiBaseUrl + endpoint, window.location.origin);
            
            // Add parameters to query string
            for (const [key, value] of Object.entries(params)) {
                if (Array.isArray(value)) {
                    value.forEach(v => url.searchParams.append(key, v));
                } else if (value !== null && value !== undefined) {
                    url.searchParams.append(key, value);
                }
            }

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), this.requestTimeout);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Correlation-ID': this.generateCorrelationId()
                },
                signal: controller.signal,
                credentials: 'same-origin'
            });

            clearTimeout(timeout);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            if (attempt < this.retryAttempts) {
                console.warn(`[HeatmapService] Request failed, retrying (${attempt}/${this.retryAttempts})...`);
                await this.sleep(this.retryDelay * attempt);
                return this.fetchWithRetry(endpoint, params, attempt + 1);
            }
            throw new Error(`Request failed after ${this.retryAttempts} attempts: ${error.message}`);
        }
    }

    /**
     * Build cache key from parameters
     * @private
     */
    buildCacheKey(type, params) {
        const hash = this.hashParams(params);
        return `heatmap:${type}:${hash}`;
    }

    /**
     * Hash parameters for cache key
     * @private
     */
    hashParams(params) {
        const sortedParams = {};
        Object.keys(params)
            .sort()
            .forEach(key => {
                sortedParams[key] = params[key];
            });
        
        return btoa(JSON.stringify(sortedParams))
            .replace(/[+/=]/g, c => 
                ({'+': '-', '/': '_', '=': ''}[c])
            );
    }

    /**
     * Log request for debugging
     * @private
     */
    logRequest(type, params, status, errorMessage = null) {
        const entry = {
            timestamp: new Date().toISOString(),
            type,
            params,
            status,
            error: errorMessage
        };

        this.requestLog.push(entry);
        if (this.requestLog.length > this.maxLogSize) {
            this.requestLog.shift();
        }

        console.log(`[HeatmapService] ${status.toUpperCase()} - ${type}-heatmap request:`, entry);
    }

    /**
     * Get request log
     */
    getRequestLog() {
        return [...this.requestLog];
    }

    /**
     * Sleep utility
     * @private
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Generate correlation ID
     * @private
     */
    generateCorrelationId() {
        return 'heatmap-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Event listener registration
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }

    /**
     * Event listener removal
     */
    off(event, callback) {
        if (this.listeners.has(event)) {
            const callbacks = this.listeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    /**
     * Emit event
     * @private
     */
    emit(event, data) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                callback(data);
            });
        }
    }

    /**
     * Initialize real-time updates via WebSocket
     * @private
     */
    initRealtimeUpdates() {
        // Check if WebSocket support is available
        if (!window.WebSocket) {
            console.warn('[HeatmapService] WebSocket not supported');
            return;
        }

        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/api/heatmap-updates`;
            
            this.ws = new WebSocket(wsUrl);

            this.ws.addEventListener('open', () => {
                console.log('[HeatmapService] WebSocket connected');
                this.emit('connected');
            });

            this.ws.addEventListener('message', (event) => {
                try {
                    const message = JSON.parse(event.data);
                    
                    if (message.type === 'heatmap:updated') {
                        // Invalidate relevant cache
                        this.invalidateCache(message.data.heatmap_type, {
                            tenant_id: message.data.tenant_id,
                            vertical: message.data.vertical
                        });
                        
                        // Emit event for subscribers
                        this.emit('heatmap-updated', message.data);
                    }
                } catch (error) {
                    console.warn('[HeatmapService] Failed to parse WebSocket message:', error);
                }
            });

            this.ws.addEventListener('close', () => {
                console.log('[HeatmapService] WebSocket disconnected');
                this.emit('disconnected');
            });

            this.ws.addEventListener('error', (error) => {
                console.error('[HeatmapService] WebSocket error:', error);
                this.emit('error', error);
            });
        } catch (error) {
            console.warn('[HeatmapService] Failed to initialize WebSocket:', error);
        }
    }

    /**
     * Cleanup and close WebSocket
     */
    destroy() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.close();
        }
        this.cache.clear();
        this.pendingRequests.clear();
        this.listeners.clear();
        console.log('[HeatmapService] Service destroyed');
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HeatmapService;
}

// Make available globally
window.HeatmapService = HeatmapService;
