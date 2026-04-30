/**
 * CatVRF Behavioral Biometrics SDK
 * 
 * Collects typing, mouse, and touch data for continuous authentication.
 * Sends data to backend API for analysis.
 * 
 * @version 1.0.0
 * @see https://github.com/nanolord126/CatVRF
 */

class BehavioralSDK {
    constructor(options = {}) {
        this.apiEndpoint = options.apiEndpoint || '/api/behavioral';
        this.sessionId = options.sessionId || this.generateSessionId();
        this.enabled = options.enabled !== false;
        this.collectInterval = options.collectInterval || 30000; // 30 seconds
        this.maxEventsPerSample = options.maxEventsPerSample || 100;
        
        this.typingCollector = new TypingCollector(this.maxEventsPerSample);
        this.mouseCollector = new MouseCollector(this.maxEventsPerSample);
        this.touchCollector = new TouchCollector(this.maxEventsPerSample);
        
        this.collectTimer = null;
        this.isStarted = false;
    }
    
    /**
     * Start data collection
     */
    start() {
        if (!this.enabled) {
            console.log('BehavioralSDK: Disabled');
            return;
        }
        
        if (this.isStarted) {
            console.log('BehavioralSDK: Already started');
            return;
        }
        
        console.log('BehavioralSDK: Starting collection');
        
        this.typingCollector.start();
        this.mouseCollector.start();
        this.touchCollector.start();
        
        // Schedule periodic data sending
        this.collectTimer = setInterval(() => {
            this.sendSamples();
        }, this.collectInterval);
        
        this.isStarted = true;
    }
    
    /**
     * Stop data collection
     */
    stop() {
        console.log('BehavioralSDK: Stopping collection');
        
        this.typingCollector.stop();
        this.mouseCollector.stop();
        this.touchCollector.stop();
        
        if (this.collectTimer) {
            clearInterval(this.collectTimer);
            this.collectTimer = null;
        }
        
        this.isStarted = false;
    }
    
    /**
     * Send collected samples to backend
     */
    async sendSamples() {
        if (!this.enabled) {
            return;
        }
        
        const samples = {
            typing: this.typingCollector.getSamplesAndClear(),
            mouse: this.mouseCollector.getSamplesAndClear(),
            touch: this.touchCollector.getSamplesAndClear(),
            sessionId: this.sessionId,
            timestamp: Date.now(),
        };
        
        // Only send if we have data
        if (!samples.typing.events.length && !samples.mouse.events.length && !samples.touch.events.length) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiEndpoint}/collect`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify(samples),
            });
            
            if (!response.ok) {
                console.error('BehavioralSDK: Failed to send samples', response.status);
            }
        } catch (error) {
            console.error('BehavioralSDK: Error sending samples', error);
        }
    }
    
    /**
     * Generate session ID
     */
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    /**
     * Get CSRF token
     */
    getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    }
}

/**
 * Typing Data Collector
 */
class TypingCollector {
    constructor(maxEvents = 100) {
        this.maxEvents = maxEvents;
        this.events = [];
        this.isStarted = false;
        this.keyDownTimes = {};
        
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleKeyUp = this.handleKeyUp.bind(this);
    }
    
    start() {
        if (this.isStarted) return;
        
        document.addEventListener('keydown', this.handleKeyDown);
        document.addEventListener('keyup', this.handleKeyUp);
        this.isStarted = true;
    }
    
    stop() {
        document.removeEventListener('keydown', this.handleKeyDown);
        document.removeEventListener('keyup', this.handleKeyUp);
        this.isStarted = false;
    }
    
    handleKeyDown(event) {
        const key = event.key;
        const timestamp = Date.now();
        
        this.events.push({
            key: key,
            timestamp: timestamp,
            keyDown: true,
            keyUp: false,
        });
        
        this.keyDownTimes[key] = timestamp;
        
        // Trim events if too many
        if (this.events.length > this.maxEvents) {
            this.events.shift();
        }
    }
    
    handleKeyUp(event) {
        const key = event.key;
        const timestamp = Date.now();
        
        this.events.push({
            key: key,
            timestamp: timestamp,
            keyDown: false,
            keyUp: true,
        });
        
        delete this.keyDownTimes[key];
        
        // Trim events if too many
        if (this.events.length > this.maxEvents) {
            this.events.shift();
        }
    }
    
    getSamplesAndClear() {
        const samples = {
            events: [...this.events],
        };
        
        this.events = [];
        this.keyDownTimes = {};
        
        return samples;
    }
}

/**
 * Mouse Data Collector
 */
class MouseCollector {
    constructor(maxEvents = 100) {
        this.maxEvents = maxEvents;
        this.events = [];
        this.clicks = [];
        this.scrolls = [];
        this.isStarted = false;
        this.lastPosition = null;
        this.lastTimestamp = null;
        
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleClick = this.handleClick.bind(this);
        this.handleScroll = this.handleScroll.bind(this);
    }
    
    start() {
        if (this.isStarted) return;
        
        document.addEventListener('mousemove', this.handleMouseMove);
        document.addEventListener('click', this.handleClick);
        document.addEventListener('scroll', this.handleScroll, true);
        this.isStarted = true;
    }
    
    stop() {
        document.removeEventListener('mousemove', this.handleMouseMove);
        document.removeEventListener('click', this.handleClick);
        document.removeEventListener('scroll', this.handleScroll, true);
        this.isStarted = false;
    }
    
    handleMouseMove(event) {
        const x = event.clientX;
        const y = event.clientY;
        const timestamp = Date.now();
        
        this.events.push({
            x: x,
            y: y,
            timestamp: timestamp,
            button: event.button,
            action: 'move',
        });
        
        this.lastPosition = { x, y };
        this.lastTimestamp = timestamp;
        
        // Trim events if too many
        if (this.events.length > this.maxEvents) {
            this.events.shift();
        }
    }
    
    handleClick(event) {
        const timestamp = Date.now();
        
        this.clicks.push({
            x: event.clientX,
            y: event.clientY,
            timestamp: timestamp,
            button: event.button,
            doubleClick: event.detail === 2,
        });
        
        // Trim clicks if too many
        if (this.clicks.length > 50) {
            this.clicks.shift();
        }
    }
    
    handleScroll(event) {
        const timestamp = Date.now();
        
        this.scrolls.push({
            deltaX: event.deltaX,
            deltaY: event.deltaY,
            timestamp: timestamp,
        });
        
        // Trim scrolls if too many
        if (this.scrolls.length > 50) {
            this.scrolls.shift();
        }
    }
    
    getSamplesAndClear() {
        const samples = {
            events: [...this.events],
            clicks: [...this.clicks],
            scrolls: [...this.scrolls],
        };
        
        this.events = [];
        this.clicks = [];
        this.scrolls = [];
        
        return samples;
    }
}

/**
 * Touch Data Collector
 */
class TouchCollector {
    constructor(maxEvents = 100) {
        this.maxEvents = maxEvents;
        this.events = [];
        this.gestures = [];
        this.isStarted = false;
        this.activeTouches = {};
        
        this.handleTouchStart = this.handleTouchStart.bind(this);
        this.handleTouchMove = this.handleTouchMove.bind(this);
        this.handleTouchEnd = this.handleTouchEnd.bind(this);
    }
    
    start() {
        if (this.isStarted) return;
        
        document.addEventListener('touchstart', this.handleTouchStart, { passive: true });
        document.addEventListener('touchmove', this.handleTouchMove, { passive: true });
        document.addEventListener('touchend', this.handleTouchEnd, { passive: true });
        this.isStarted = true;
    }
    
    stop() {
        document.removeEventListener('touchstart', this.handleTouchStart);
        document.removeEventListener('touchmove', this.handleTouchMove);
        document.removeEventListener('touchend', this.handleTouchEnd);
        this.isStarted = false;
    }
    
    handleTouchStart(event) {
        for (const touch of event.changedTouches) {
            this.activeTouches[touch.identifier] = {
                startX: touch.clientX,
                startY: touch.clientY,
                startTime: Date.now(),
            };
            
            this.events.push({
                x: touch.clientX,
                y: touch.clientY,
                timestamp: Date.now(),
                identifier: touch.identifier,
                action: 'touchstart',
            });
        }
        
        // Trim events if too many
        if (this.events.length > this.maxEvents) {
            this.events.shift();
        }
    }
    
    handleTouchMove(event) {
        for (const touch of event.changedTouches) {
            this.events.push({
                x: touch.clientX,
                y: touch.clientY,
                timestamp: Date.now(),
                identifier: touch.identifier,
                action: 'touchmove',
            });
        }
        
        // Trim events if too many
        if (this.events.length > this.maxEvents) {
            this.events.shift();
        }
    }
    
    handleTouchEnd(event) {
        for (const touch of event.changedTouches) {
            const activeTouch = this.activeTouches[touch.identifier];
            
            if (activeTouch) {
                const endX = touch.clientX;
                const endY = touch.clientY;
                const endTime = Date.now();
                
                const deltaX = endX - activeTouch.startX;
                const deltaY = endY - activeTouch.startY;
                const deltaTime = endTime - activeTouch.startTime;
                
                const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
                const velocity = distance / Math.max(deltaTime, 1);
                
                // Detect swipe gesture
                if (distance > 50 && deltaTime < 500) {
                    let direction = '';
                    if (Math.abs(deltaX) > Math.abs(deltaY)) {
                        direction = deltaX > 0 ? 'right' : 'left';
                    } else {
                        direction = deltaY > 0 ? 'down' : 'up';
                    }
                    
                    this.gestures.push({
                        type: 'swipe',
                        direction: direction,
                        velocity: velocity,
                        distance: distance,
                        timestamp: endTime,
                    });
                }
                
                // Detect pinch gesture (2+ fingers)
                if (event.touches.length >= 2) {
                    const touch1 = event.touches[0];
                    const touch2 = event.touches[1];
                    
                    const startDistance = Math.sqrt(
                        Math.pow(activeTouch.startX - this.activeTouches[event.touches[1].identifier]?.startX, 2) +
                        Math.pow(activeTouch.startY - this.activeTouches[event.touches[1].identifier]?.startY, 2)
                    );
                    
                    const endDistance = Math.sqrt(
                        Math.pow(touch1.clientX - touch2.clientX, 2) +
                        Math.pow(touch1.clientY - touch2.clientY, 2)
                    );
                    
                    if (startDistance > 0) {
                        const scale = endDistance / startDistance;
                        
                        this.gestures.push({
                            type: 'pinch',
                            scale: scale,
                            center: {
                                x: (touch1.clientX + touch2.clientX) / 2,
                                y: (touch1.clientY + touch2.clientY) / 2,
                            },
                            timestamp: endTime,
                        });
                    }
                }
                
                delete this.activeTouches[touch.identifier];
            }
            
            this.events.push({
                x: touch.clientX,
                y: touch.clientY,
                timestamp: Date.now(),
                identifier: touch.identifier,
                action: 'touchend',
            });
        }
        
        // Trim events and gestures if too many
        if (this.events.length > this.maxEvents) {
            this.events.shift();
        }
        
        if (this.gestures.length > 50) {
            this.gestures.shift();
        }
    }
    
    getSamplesAndClear() {
        const samples = {
            events: [...this.events],
            gestures: [...this.gestures],
        };
        
        this.events = [];
        this.gestures = [];
        this.activeTouches = {};
        
        return samples;
    }
}

// Auto-initialize if data-behavioral-sdk attribute present
document.addEventListener('DOMContentLoaded', () => {
    const element = document.querySelector('[data-behavioral-sdk]');
    
    if (element) {
        const options = JSON.parse(element.getAttribute('data-behavioral-sdk') || '{}');
        const sdk = new BehavioralSDK(options);
        sdk.start();
        
        // Make SDK available globally for debugging
        window.behavioralSDK = sdk;
        
        console.log('BehavioralSDK: Auto-initialized');
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BehavioralSDK, TypingCollector, MouseCollector, TouchCollector };
}
