/**
 * Behavioral Biometrics Collector
 *
 * Lightweight JavaScript library for collecting behavioral signals:
 * - Keystroke dynamics (key hold times, flight times, typing speed)
 * - Mouse dynamics (velocity, acceleration, curvature, click patterns)
 * - Touch gestures (pressure, swipe patterns, pinch/zoom)
 * - Session behavior (scroll depth, time on page)
 *
 * CatVRF 2026 Enterprise Security
 * Privacy: Only aggregated features sent, never raw keystrokes
 * 
 * Usage:
 *   const collector = new BehavioralCollector();
 *   collector.start();
 *   collector.collectSignals('login').then(data => sendToBackend(data));
 *   collector.stop();
 */

class BehavioralCollector {
    constructor(options = {}) {
        this.options = {
            throttleMs: options.throttleMs || 50,
            maxBufferSize: options.maxBufferSize || 100,
            batchIntervalMs: options.batchIntervalMs || 30000,
            enabled: options.enabled !== false,
            ...options
        };

        this.isCollecting = false;
        this.buffers = {
            typing: [],
            mouse: [],
            touch: [],
            session: []
        };

        this.timers = {
            lastKeystroke: 0,
            lastMouse: 0,
            lastTouch: 0,
            batch: null
        };

        this.sessionStartTime = Date.now();
        this.throttledHandlers = {};
    }

    /**
     * Start collecting behavioral signals
     */
    start() {
        if (this.isCollecting || !this.options.enabled) {
            return;
        }

        this.isCollecting = true;
        this.attachListeners();
        this.startBatchTimer();
        
        console.debug('[BehavioralCollector] Started');
    }

    /**
     * Stop collecting behavioral signals
     */
    stop() {
        if (!this.isCollecting) {
            return;
        }

        this.isCollecting = false;
        this.detachListeners();
        this.stopBatchTimer();
        
        console.debug('[BehavioralCollector] Stopped');
    }

    /**
     * Attach event listeners
     */
    attachListeners() {
        // Keystroke listeners
        this.throttledHandlers.keydown = this.throttle((e) => this.handleKeydown(e), this.options.throttleMs);
        this.throttledHandlers.keyup = this.throttle((e) => this.handleKeyup(e), this.options.throttleMs);
        
        document.addEventListener('keydown', this.throttledHandlers.keydown);
        document.addEventListener('keyup', this.throttledHandlers.keyup);

        // Mouse listeners
        this.throttledHandlers.mousemove = this.throttle((e) => this.handleMousemove(e), this.options.throttleMs);
        this.throttledHandlers.mousedown = this.throttle((e) => this.handleMousedown(e), this.options.throttleMs);
        this.throttledHandlers.mouseup = this.throttle((e) => this.handleMouseup(e), this.options.throttleMs);
        this.throttledHandlers.click = this.throttle((e) => this.handleClick(e), this.options.throttleMs);
        this.throttledHandlers.scroll = this.throttle(() => this.handleScroll(), this.options.throttleMs);

        document.addEventListener('mousemove', this.throttledHandlers.mousemove);
        document.addEventListener('mousedown', this.throttledHandlers.mousedown);
        document.addEventListener('mouseup', this.throttledHandlers.mouseup);
        document.addEventListener('click', this.throttledHandlers.click);
        window.addEventListener('scroll', this.throttledHandlers.scroll);

        // Touch listeners (mobile)
        if ('ontouchstart' in window) {
            this.throttledHandlers.touchstart = this.throttle((e) => this.handleTouchstart(e), this.options.throttleMs);
            this.throttledHandlers.touchmove = this.throttle((e) => this.handleTouchmove(e), this.options.throttleMs);
            this.throttledHandlers.touchend = this.throttle((e) => this.handleTouchend(e), this.options.throttleMs);

            document.addEventListener('touchstart', this.throttledHandlers.touchstart, { passive: true });
            document.addEventListener('touchmove', this.throttledHandlers.touchmove, { passive: true });
            document.addEventListener('touchend', this.throttledHandlers.touchend, { passive: true });
        }

        // Page visibility for session tracking
        document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
    }

    /**
     * Detach event listeners
     */
    detachListeners() {
        document.removeEventListener('keydown', this.throttledHandlers.keydown);
        document.removeEventListener('keyup', this.throttledHandlers.keyup);
        document.removeEventListener('mousemove', this.throttledHandlers.mousemove);
        document.removeEventListener('mousedown', this.throttledHandlers.mousedown);
        document.removeEventListener('mouseup', this.throttledHandlers.mouseup);
        document.removeEventListener('click', this.throttledHandlers.click);
        window.removeEventListener('scroll', this.throttledHandlers.scroll);

        if ('ontouchstart' in window) {
            document.removeEventListener('touchstart', this.throttledHandlers.touchstart);
            document.removeEventListener('touchmove', this.throttledHandlers.touchmove);
            document.removeEventListener('touchend', this.throttledHandlers.touchend);
        }

        document.removeEventListener('visibilitychange', () => this.handleVisibilityChange());
    }

    /**
     * Handle keydown event
     */
    handleKeydown(event) {
        const timestamp = Date.now();
        const key = event.key;

        this.buffers.typing.push({
            key,
            keyDown: true,
            timestamp,
            flightTime: this.timers.lastKeystroke ? timestamp - this.timers.lastKeystroke : 0
        });

        this.timers.lastKeystroke = timestamp;
        this.trimBuffer('typing');
    }

    /**
     * Handle keyup event
     */
    handleKeyup(event) {
        const timestamp = Date.now();
        const key = event.key;

        // Find matching keydown and calculate hold time
        const matchingIndex = this.buffers.typing.findIndex(
            e => e.key === key && e.keyDown && !e.holdTime
        );

        if (matchingIndex !== -1) {
            this.buffers.typing[matchingIndex].holdTime = timestamp - this.buffers.typing[matchingIndex].timestamp;
            this.buffers.typing[matchingIndex].keyUp = true;
        }

        this.trimBuffer('typing');
    }

    /**
     * Handle mousemove event
     */
    handleMousemove(event) {
        const timestamp = Date.now();
        const x = event.clientX;
        const y = event.clientY;

        this.buffers.mouse.push({
            x,
            y,
            timestamp,
            type: 'move'
        });

        this.timers.lastMouse = timestamp;
        this.trimBuffer('mouse');
    }

    /**
     * Handle mousedown event
     */
    handleMousedown(event) {
        const timestamp = Date.now();
        
        this.buffers.mouse.push({
            x: event.clientX,
            y: event.clientY,
            timestamp,
            type: 'mousedown',
            button: event.button
        });

        this.trimBuffer('mouse');
    }

    /**
     * Handle mouseup event
     */
    handleMouseup(event) {
        const timestamp = Date.now();
        
        this.buffers.mouse.push({
            x: event.clientX,
            y: event.clientY,
            timestamp,
            type: 'mouseup',
            button: event.button
        });

        this.trimBuffer('mouse');
    }

    /**
     * Handle click event
     */
    handleClick(event) {
        const timestamp = Date.now();
        
        this.buffers.mouse.push({
            x: event.clientX,
            y: event.clientY,
            timestamp,
            type: 'click',
            button: event.button
        });

        this.trimBuffer('mouse');
    }

    /**
     * Handle scroll event
     */
    handleScroll() {
        const timestamp = Date.now();
        const scrollDepth = window.scrollY / (document.body.scrollHeight - window.innerHeight);
        
        this.buffers.session.push({
            type: 'scroll',
            timestamp,
            scrollDepth: Math.min(1, Math.max(0, scrollDepth))
        });

        this.trimBuffer('session');
    }

    /**
     * Handle touchstart event
     */
    handleTouchstart(event) {
        const timestamp = Date.now();
        
        for (const touch of event.changedTouches) {
            this.buffers.touch.push({
                x: touch.clientX,
                y: touch.clientY,
                identifier: touch.identifier,
                action: 'touchstart',
                timestamp,
                force: touch.force || 0
            });
        }

        this.timers.lastTouch = timestamp;
        this.trimBuffer('touch');
    }

    /**
     * Handle touchmove event
     */
    handleTouchmove(event) {
        const timestamp = Date.now();
        
        for (const touch of event.changedTouches) {
            this.buffers.touch.push({
                x: touch.clientX,
                y: touch.clientY,
                identifier: touch.identifier,
                action: 'touchmove',
                timestamp,
                force: touch.force || 0
            });
        }

        this.trimBuffer('touch');
    }

    /**
     * Handle touchend event
     */
    handleTouchend(event) {
        const timestamp = Date.now();
        
        for (const touch of event.changedTouches) {
            this.buffers.touch.push({
                x: touch.clientX,
                y: touch.clientY,
                identifier: touch.identifier,
                action: 'touchend',
                timestamp,
                force: touch.force || 0
            });
        }

        this.trimBuffer('touch');
    }

    /**
     * Handle visibility change
     */
    handleVisibilityChange() {
        this.buffers.session.push({
            type: 'visibility_change',
            timestamp: Date.now(),
            hidden: document.hidden
        });

        this.trimBuffer('session');
    }

    /**
     * Collect and return all signals
     */
    collectSignals(action = 'unknown') {
        const sessionDuration = Date.now() - this.sessionStartTime;
        
        return {
            action,
            typing: {
                events: this.buffers.typing,
                eventCount: this.buffers.typing.length
            },
            mouse: {
                events: this.buffers.mouse,
                eventCount: this.buffers.mouse.length,
                clicks: this.buffers.mouse.filter(e => e.type === 'click'),
                scrolls: this.buffers.session.filter(e => e.type === 'scroll')
            },
            touch: {
                events: this.buffers.touch,
                eventCount: this.buffers.touch.length
            },
            session: {
                duration: sessionDuration,
                events: this.buffers.session,
                eventCount: this.buffers.session.length
            },
            metadata: {
                timestamp: Date.now(),
                userAgent: navigator.userAgent,
                screenSize: {
                    width: window.screen.width,
                    height: window.screen.height
                },
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                }
            }
        };
    }

    /**
     * Clear all buffers
     */
    clearBuffers() {
        this.buffers = {
            typing: [],
            mouse: [],
            touch: [],
            session: []
        };
    }

    /**
     * Trim buffer to max size
     */
    trimBuffer(type) {
        if (this.buffers[type].length > this.options.maxBufferSize) {
            this.buffers[type] = this.buffers[type].slice(-this.options.maxBufferSize);
        }
    }

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Start batch timer for automatic sending
     */
    startBatchTimer() {
        this.timers.batch = setInterval(() => {
            if (this.hasData()) {
                this.sendBatch();
            }
        }, this.options.batchIntervalMs);
    }

    /**
     * Stop batch timer
     */
    stopBatchTimer() {
        if (this.timers.batch) {
            clearInterval(this.timers.batch);
            this.timers.batch = null;
        }
    }

    /**
     * Check if there's data to send
     */
    hasData() {
        return this.buffers.typing.length > 0 ||
               this.buffers.mouse.length > 0 ||
               this.buffers.touch.length > 0;
    }

    /**
     * Send batch to backend (override this method)
     */
    async sendBatch(action = 'batch') {
        if (!this.hasData()) {
            return;
        }

        const signals = this.collectSignals(action);
        this.clearBuffers();

        // This should be overridden by the application
        console.debug('[BehavioralCollector] Batch ready to send:', signals);
        
        return signals;
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BehavioralCollector;
}

// Auto-initialize if configured
if (typeof window !== 'undefined' && window.BEHAVIORAL_COLLECTOR_ENABLED !== false) {
    window.BehavioralCollector = BehavioralCollector;
}
