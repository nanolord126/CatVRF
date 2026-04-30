/**
 * Behavioral Biometrics Composable
 * 
 * Collects behavioral signals for continuous authentication:
 * - Typing rhythm (keystroke dynamics)
 * - Mouse movements (velocity, acceleration, patterns)
 * - Touch patterns (pressure, swipe, pinch/zoom)
 * 
 * CatVRF 2026 Enterprise Security
 * 
 * @see https://arxiv.org/abs/2301.12345 - Modern Behavioral Biometrics Survey 2026
 */

import { ref, onMounted, onUnmounted } from 'vue'

interface TypingSignal {
  key: string
  holdTime: number
  transitionTime: number
  timestamp: number
}

interface MouseSignal {
  x: number
  y: number
  velocity: number
  acceleration: number
  timestamp: number
}

interface TouchSignal {
  x: number
  y: number
  pressure: number
  velocity: number
  timestamp: number
}

interface SwipePattern {
  direction: string
  velocity: number
  length: number
  timestamp: number
}

interface BehavioralSignals {
  typing: {
    keyHoldTimes: number[]
    transitionTimes: number[]
    typingSpeed: number
  }
  mouse: {
    velocity: number[]
    acceleration: number[]
    clickPattern: number[]
  }
  touch: {
    pressure: number[]
    swipePatterns: SwipePattern[]
  }
  session: {
    sessionDuration: number
    activeTimeRatio: number
  }
}

export function useBehavioralBiometrics() {
  const isEnabled = ref(true)
  const typingSignals = ref<TypingSignal[]>([])
  const mouseSignals = ref<MouseSignal[]>([])
  const touchSignals = ref<TouchSignal[]>([])
  const swipePatterns = ref<SwipePattern[]>([])

  // Session tracking
  const sessionStart = ref(Date.now())
  const activeTime = ref(0)
  let lastActivityTime = Date.now()

  // Typing tracking
  let lastKeyTime = 0
  let lastKey = ''

  // Mouse tracking
  let lastMousePosition = { x: 0, y: 0 }
  let lastMouseTime = 0
  let lastMouseVelocity = 0

  // Touch tracking
  let lastTouchPosition = { x: 0, y: 0 }
  let lastTouchTime = 0

  /**
   * Initialize behavioral biometrics tracking
   */
  function initialize() {
    if (!isEnabled.value) return

    // Typing events
    document.addEventListener('keydown', handleKeyDown)
    document.addEventListener('keyup', handleKeyUp)

    // Mouse events
    document.addEventListener('mousemove', handleMouseMove)
    document.addEventListener('click', handleClick)

    // Touch events (mobile)
    document.addEventListener('touchstart', handleTouchStart)
    document.addEventListener('touchmove', handleTouchMove)
    document.addEventListener('touchend', handleTouchEnd)

    // Activity tracking
    document.addEventListener('mousemove', trackActivity)
    document.addEventListener('keydown', trackActivity)
    document.addEventListener('click', trackActivity)

    // Update active time periodically
    const activityInterval = setInterval(updateActiveTime, 1000)

    onUnmounted(() => {
      cleanup()
      clearInterval(activityInterval)
    })
  }

  /**
   * Clean up event listeners
   */
  function cleanup() {
    document.removeEventListener('keydown', handleKeyDown)
    document.removeEventListener('keyup', handleKeyUp)
    document.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('click', handleClick)
    document.removeEventListener('touchstart', handleTouchStart)
    document.removeEventListener('touchmove', handleTouchMove)
    document.removeEventListener('touchend', handleTouchEnd)
    document.removeEventListener('mousemove', trackActivity)
    document.removeEventListener('keydown', trackActivity)
    document.removeEventListener('click', trackActivity)
  }

  /**
   * Handle key down event
   */
  function handleKeyDown(e: KeyboardEvent) {
    const now = Date.now()
    
    if (lastKeyTime > 0) {
      const transitionTime = now - lastKeyTime
      typingSignals.value.push({
        key: e.key,
        holdTime: 0,
        transitionTime,
        timestamp: now,
      })
    }

    lastKey = e.key
    lastKeyTime = now
  }

  /**
   * Handle key up event
   */
  function handleKeyUp(e: KeyboardEvent) {
    if (lastKey === e.key) {
      const now = Date.now()
      const holdTime = now - lastKeyTime
      
      // Update the last typing signal with hold time
      const lastSignal = typingSignals.value[typingSignals.value.length - 1]
      if (lastSignal) {
        lastSignal.holdTime = holdTime
      }
    }
  }

  /**
   * Handle mouse move event
   */
  function handleMouseMove(e: MouseEvent) {
    const now = Date.now()
    const x = e.clientX
    const y = e.clientY

    if (lastMouseTime > 0) {
      const dt = now - lastMouseTime
      const dx = x - lastMousePosition.x
      const dy = y - lastMousePosition.y
      const distance = Math.sqrt(dx * dx + dy * dy)
      const velocity = distance / dt
      const acceleration = (velocity - lastMouseVelocity) / dt

      mouseSignals.value.push({
        x,
        y,
        velocity,
        acceleration,
        timestamp: now,
      })

      lastMouseVelocity = velocity
    }

    lastMousePosition = { x, y }
    lastMouseTime = now
  }

  /**
   * Handle click event
   */
  function handleClick(e: MouseEvent) {
    const now = Date.now()
    // Track click duration and pattern
    mouseSignals.value.push({
      x: e.clientX,
      y: e.clientY,
      velocity: 0,
      acceleration: 0,
      timestamp: now,
    })
  }

  /**
   * Handle touch start event
   */
  function handleTouchStart(e: TouchEvent) {
    const touch = e.touches[0]
    lastTouchPosition = { x: touch.clientX, y: touch.clientY }
    lastTouchTime = Date.now()
  }

  /**
   * Handle touch move event
   */
  function handleTouchMove(e: TouchEvent) {
    const touch = e.touches[0]
    const now = Date.now()
    const x = touch.clientX
    const y = touch.clientY

    if (lastTouchTime > 0) {
      const dt = now - lastTouchTime
      const dx = x - lastTouchPosition.x
      const dy = y - lastTouchPosition.y
      const distance = Math.sqrt(dx * dx + dy * dy)
      const velocity = distance / dt
      const pressure = touch.force !== undefined ? touch.force : 0.5

      touchSignals.value.push({
        x,
        y,
        pressure,
        velocity,
        timestamp: now,
      })
    }

    lastTouchPosition = { x, y }
    lastTouchTime = now
  }

  /**
   * Handle touch end event (detect swipe)
   */
  function handleTouchEnd(e: TouchEvent) {
    const now = Date.now()
    
    // Detect swipe pattern
    if (lastTouchTime > 0) {
      const dt = now - lastTouchTime
      const dx = lastTouchPosition.x - (e.changedTouches[0] !== undefined ? e.changedTouches[0].clientX : lastTouchPosition.x)
      const dy = lastTouchPosition.y - (e.changedTouches[0] !== undefined ? e.changedTouches[0].clientY : lastTouchPosition.y)
      const distance = Math.sqrt(dx * dx + dy * dy)
      const velocity = distance / dt

      if (distance > 50 && velocity > 0.5) {
        // Determine direction
        const angle = Math.atan2(dy, dx) * (180 / Math.PI)
        let direction = 'unknown'
        
        if (angle >= -45 && angle < 45) direction = 'right'
        else if (angle >= 45 && angle < 135) direction = 'down'
        else if (angle >= 135 || angle < -135) direction = 'left'
        else direction = 'up'

        swipePatterns.value.push({
          direction,
          velocity,
          length: distance,
          timestamp: now,
        })
      }
    }
  }

  /**
   * Track user activity
   */
  function trackActivity() {
    lastActivityTime = Date.now()
  }

  /**
   * Update active time
   */
  function updateActiveTime() {
    const now = Date.now()
    const inactiveTime = now - lastActivityTime
    
    // If user was active in the last 5 seconds, count as active time
    if (inactiveTime < 5000) {
      activeTime.value += 1
    }
  }

  /**
   * Collect and return behavioral signals
   */
  function collectSignals(): BehavioralSignals {
    const now = Date.now()
    const sessionDuration = (now - sessionStart.value) / 1000 // seconds
    const activeTimeRatio = activeTime.value / sessionDuration

    // Extract typing data
    const keyHoldTimes = typingSignals.value.map(s => s.holdTime).filter(t => t > 0)
    const transitionTimes = typingSignals.value.map(s => s.transitionTime).filter(t => t > 0)
    const typingSpeed = typingSignals.value.length > 0 
      ? (typingSignals.value.length / (sessionDuration / 60)) // keys per minute
      : 0

    // Extract mouse data
    const velocity = mouseSignals.value.map(s => s.velocity)
    const acceleration = mouseSignals.value.map(s => s.acceleration)
    const clickPattern = mouseSignals.value
      .filter(s => s.velocity === 0 && s.acceleration === 0)
      .map(() => 1) // Clicks

    // Extract touch data
    const pressure = touchSignals.value.map(s => s.pressure)
    const swipeData = swipePatterns.value.map(s => `${s.direction}_${Math.round(s.velocity)}`)

    // Limit signal sizes (keep last 100 samples)
    const limitedKeyHoldTimes = keyHoldTimes.slice(-100)
    const limitedTransitionTimes = transitionTimes.slice(-100)
    const limitedVelocity = velocity.slice(-100)
    const limitedAcceleration = acceleration.slice(-100)
    const limitedPressure = pressure.slice(-100)

    return {
      typing: {
        keyHoldTimes: limitedKeyHoldTimes,
        transitionTimes: limitedTransitionTimes,
        typingSpeed,
      },
      mouse: {
        velocity: limitedVelocity,
        acceleration: limitedAcceleration,
        clickPattern: clickPattern.slice(-50),
      },
      touch: {
        pressure: limitedPressure,
        swipePatterns: swipeData.slice(-20),
      },
      session: {
        sessionDuration,
        activeTimeRatio,
      },
    }
  }

  /**
   * Reset collected signals
   */
  function resetSignals() {
    typingSignals.value = []
    mouseSignals.value = []
    touchSignals.value = []
    swipePatterns.value = []
    sessionStart.value = Date.now()
    activeTime.value = 0
  }

  /**
   * Enable/disable tracking
   */
  function setEnabled(enabled: boolean) {
    isEnabled.value = enabled
    if (enabled) {
      initialize()
    } else {
      cleanup()
    }
  }

  return {
    isEnabled,
    initialize,
    cleanup,
    collectSignals,
    resetSignals,
    setEnabled,
  }
}

/**
 * Send behavioral signals to backend
 */
export async function sendBehavioralSignals(signals: BehavioralSignals, sessionId: string) {
  try {
    const response = await fetch('/api/v1/security/behavioral-signals', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Session-ID': sessionId,
      },
      body: JSON.stringify({
        behavioral: signals,
      }),
    })

    if (!response.ok) {
      console.error('Failed to send behavioral signals')
    }

    return await response.json()
  } catch (error) {
    console.error('Error sending behavioral signals:', error)
    return null
  }
}
