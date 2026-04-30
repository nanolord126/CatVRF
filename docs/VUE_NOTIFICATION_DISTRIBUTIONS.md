# Vue Notification Distribution Components with Animations

## Overview

Modern Vue.js components for notification distribution tracking with real-time animations and live metrics. Built with Vue 3 Composition API, TypeScript, and smooth CSS animations.

## Components

### 1. NotificationDistributionCard

Animated card component for displaying individual notification distributions with real-time progress tracking.

**Features:**
- Gradient header with status indicators
- Animated progress bar with shimmer effect
- Real-time metrics with animated number counters
- Channel distribution visualization
- Live activity feed
- Hover effects and transitions
- Action buttons (Start, Pause, Clone, View Details)

**Props:**
```typescript
interface Distribution {
  name: string
  type: string
  status: 'scheduled' | 'sending' | 'completed' | 'paused' | 'failed'
  progress: number
}

interface Metric {
  label: string
  value: number
  trend: number
  bgClass: string
  valueClass: string
}

interface Channel {
  name: string
  percentage: number
  colorClass: string
}

interface Activity {
  message: string
  time: string
}
```

**Usage:**
```vue
<NotificationDistributionCard
  :distribution="distribution"
  :metrics="metrics"
  :channels="channels"
  :recentActivities="activities"
  :is-active="isActive"
  @view-details="handleViewDetails"
  @start-distribution="handleStart"
  @pause-distribution="handlePause"
  @clone-distribution="handleClone"
/>
```

### 2. AnimatedNumber

Reusable component for animating number changes with smooth easing functions.

**Features:**
- Configurable duration
- Decimal precision control
- Ease-out quart easing for natural motion
- Reacts to prop changes

**Props:**
```typescript
{
  value: number        // Target value
  duration?: number    // Animation duration in ms (default: 1000)
  decimals?: number    // Decimal places (default: 0)
}
```

**Usage:**
```vue
<AnimatedNumber :value="12345" :duration="1500" :decimals="2" />
```

### 3. LiveMetricsDashboard

Comprehensive real-time dashboard for notification metrics with dark theme.

**Features:**
- Real-time key metrics with sparklines
- Live delivery rate chart (Chart.js)
- Channel performance doughnut chart
- Live activity feed with animations
- Top performing campaigns
- Geographic distribution (map/list toggle)
- WebSocket integration for real-time updates
- Auto-refreshing data

**Key Metrics:**
- Total Sent
- Delivered
- Opened
- Reactions

**Charts:**
- Delivery Rate (real-time line chart)
- Channel Performance (doughnut chart)
- Sparklines for each metric

**Live Activity Feed:**
- Real-time event streaming
- Slide-in animations for new events
- Status indicators (Delivered, Opened, Failed, Liked)
- Timestamp formatting

**Usage:**
```vue
<LiveMetricsDashboard />
```

### 4. NotificationDistributionTracker

Main page component for managing notification distributions.

**Features:**
- Grid layout of distribution cards
- Search and filtering (status, channel, date range)
- Create new distribution modal
- Live metrics modal integration
- Empty state handling
- Responsive design

**Filters:**
- Search by name
- Filter by status (All, Scheduled, Sending, Completed, Paused, Failed)
- Filter by channel (Email, SMS, Push, Telegram, WhatsApp)
- Date range picker

**Usage:**
```vue
<NotificationDistributionTracker />
```

## Composables

### useNotificationWebSocket

WebSocket composable for real-time notification metrics and activity updates.

**Features:**
- Automatic reconnection with exponential backoff
- Message type handling (metrics, activity, alerts)
- Connection state management
- Error handling
- Max reconnection attempts

**API:**
```typescript
const {
  isConnected,      // Ref<boolean>
  metrics,          // Ref<NotificationMetrics>
  recentActivities, // Ref<any[]>
  error,            // Ref<string | null>
  connect,          // (url: string) => void
  disconnect,       // () => void
  send              // (data: any) => void
} = useNotificationWebSocket()
```

**Usage:**
```typescript
const { isConnected, metrics, connect } = useNotificationWebSocket()

onMounted(() => {
  connect('wss://your-domain.com/ws/notifications/metrics')
})
```

## WebSocket Integration

### Message Types

**Metrics Update:**
```json
{
  "type": "metrics",
  "data": {
    "totalSent": 125847,
    "delivered": 118234,
    "opened": 89452,
    "reacted": 45678,
    "deliveryRate": 94.5,
    "openRate": 75.6,
    "reactionRate": 38.6
  },
  "timestamp": "2026-04-26T14:30:00Z"
}
```

**Activity Update:**
```json
{
  "type": "activity",
  "data": {
    "message": "Order confirmation sent",
    "details": "To user #12345 via Telegram",
    "status": "Delivered",
    "channel": "telegram"
  },
  "timestamp": "2026-04-26T14:30:00Z"
}
```

**Alert:**
```json
{
  "type": "alert",
  "data": {
    "type": "delivery_rate_drop",
    "message": "Delivery rate dropped below 90%",
    "threshold": 90,
    "current": 87.5
  },
  "timestamp": "2026-04-26T14:30:00Z"
}
```

## Animations

### CSS Animations

**Shimmer Effect:**
```css
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.animate-shimmer {
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}
```

**Slide In:**
```css
@keyframes slide-in {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.animate-slide-in {
  animation: slide-in 0.3s ease-out;
}
```

**Pulse:**
```css
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
```

### JavaScript Animations

**Number Animation:**
- Uses `requestAnimationFrame` for smooth 60fps animation
- Ease-out quart easing function: `1 - Math.pow(1 - progress, 4)`
- Configurable duration and precision

**Chart Animations:**
- Chart.js built-in animations
- Real-time updates with `chart.update('none')` for performance
- Smooth transitions between data points

## Styling

### Color Scheme

**Primary Colors:**
- Indigo: `rgb(99, 102, 241)` - Primary actions, links
- Purple: `rgb(168, 85, 247)` - Secondary elements
- Blue: `rgb(59, 130, 246)` - Information
- Green: `rgb(34, 197, 94)` - Success, positive trends
- Red: `rgb(239, 68, 68)` - Errors, negative trends
- Amber: `rgb(234, 179, 8)` - Warnings

**Dark Theme:**
- Background: `rgb(17, 24, 39)` - Gray 900
- Card Background: `rgb(31, 41, 55)` - Gray 800
- Text: `rgb(255, 255, 255)` - White
- Text Muted: `rgb(156, 163, 175)` - Gray 400

### Tailwind CSS Classes

Components use Tailwind CSS utility classes for:
- Layout (flex, grid, spacing)
- Typography (font sizes, weights, colors)
- Effects (shadows, rounded corners, transitions)
- Responsive design (breakpoints)

## Integration with Backend

### API Endpoints

**Create Distribution:**
```typescript
POST /api/notifications/distributions
{
  "name": "Summer Sale 2026",
  "type": "promotional",
  "channels": ["email", "push"],
  "scheduleType": "immediate"
}
```

**List Distributions:**
```typescript
GET /api/notifications/distributions?status=sending&channel=email
```

**Start Distribution:**
```typescript
POST /api/notifications/distributions/:id/start
```

**Pause Distribution:**
```typescript
POST /api/notifications/distributions/:id/pause
```

**Get Metrics:**
```typescript
GET /api/notifications/reactions/analytics
```

**Get Recommendations:**
```typescript
GET /api/notifications/reactions/recommendations
```

## Performance Optimizations

1. **Virtual Scrolling** - For large lists (consider implementing for activity feed)
2. **Debouncing** - For search and filter inputs
3. **Chart Updates** - Use `update('none')` for real-time charts
4. **WebSocket Reconnection** - Exponential backoff to prevent spam
5. **Memoization** - Use `computed` for expensive calculations
6. **Lazy Loading** - Load charts only when visible

## Browser Support

- Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- WebSocket support required for real-time features
- Chart.js for chart rendering
- CSS Grid and Flexbox support

## Dependencies

### Required
- Vue 3.x
- TypeScript 5.x
- Chart.js 4.x
- Tailwind CSS 3.x

### Optional
- Pinia (for state management)
- Vue Router (for navigation)
- Axios (for HTTP requests)

## File Structure

```
frontend/src/
├── components/
│   └── business/
│       └── notifications/
│           ├── NotificationDistributionCard.vue
│           ├── AnimatedNumber.vue
│           ├── LiveMetricsDashboard.vue
│           └── NotificationDistributionTracker.vue
├── composables/
│   └── useNotificationWebSocket.ts
└── app/
    └── account/
        └── notifications/
            └── distributions/
                └── page.tsx
```

## Usage Example

```vue
<template>
  <div>
    <NotificationDistributionTracker />
  </div>
</template>

<script setup lang="ts">
import NotificationDistributionTracker from '@/components/business/notifications/NotificationDistributionTracker.vue'
</script>
```

## Future Enhancements

1. **A/B Testing** - Compare distribution performance
2. **Multivariate Testing** - Test different content variations
3. **Advanced Analytics** - Cohort analysis, retention metrics
4. **Export Functionality** - CSV, PDF exports
5. **Scheduled Reports** - Email digest of metrics
6. **Mobile App** - Push notifications for critical alerts
7. **AI Recommendations** - Smart scheduling based on user behavior
8. **Geographic Heatmap** - Interactive map for delivery visualization
9. **Voice Commands** - Voice-controlled dashboard
10. **Dark/Light Mode Toggle** - Theme switching

## Troubleshooting

**WebSocket Not Connecting:**
- Check WebSocket URL is correct
- Verify CORS configuration
- Check network connectivity
- Review browser console for errors

**Charts Not Rendering:**
- Ensure Chart.js is installed
- Check canvas element dimensions
- Verify data format matches Chart.js expectations

**Animations Not Smooth:**
- Reduce number of animated elements
- Check for memory leaks
- Verify browser performance
- Consider using will-change CSS property

## License

Part of CatVRF project - Healthcare Marketplace Platform
