# Notification Reaction Tracking & ML Recommendation System

## Overview

This implementation adds comprehensive reaction tracking and ML-powered recommendation capabilities to the CatVRF notification system. Users can react to notifications (like, dislike, helpful, etc.), and the system uses this data to provide personalized recommendations for optimal notification delivery.

## Architecture

### Components

1. **Database Layer**
   - `notification_logs` table extended with reaction fields
   - `notification_reactions` table for detailed reaction tracking

2. **Models**
   - `NotificationLog` - Updated with reaction fields and scopes
   - `NotificationReaction` - New model for reaction records

3. **Services**
   - `NotificationAnalyticsService` - Reaction tracking and analytics
   - `NotificationRecommendationService` - ML-powered recommendations

4. **API**
   - `NotificationReactionController` - REST API endpoints
   - Routes in `routes/api/notifications.php`

5. **Admin Interface**
   - `NotificationReactionResource` - Filament resource
   - `NotificationReactionStatsWidget` - Dashboard statistics widget
   - `NotificationRecommendationWidget` - Recommendation insights widget

## Database Schema

### notification_logs (extended)

```sql
ALTER TABLE notification_logs ADD COLUMN reaction_type ENUM('like', 'dislike', 'neutral', 'helpful', 'not_helpful', 'reported') NULL;
ALTER TABLE notification_logs ADD COLUMN reacted_at TIMESTAMP NULL;
ALTER TABLE notification_logs ADD COLUMN reaction_metadata JSON NULL;
```

### notification_reactions (new)

```sql
CREATE TABLE notification_reactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_log_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    reaction_type ENUM('like', 'dislike', 'neutral', 'helpful', 'not_helpful', 'reported') NOT NULL,
    reaction_metadata JSON NULL,
    reacted_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (notification_log_id) REFERENCES notification_logs(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    INDEX idx_reaction_type_reacted_at (reaction_type, reacted_at),
    INDEX idx_user_id_reaction_type (user_id, reaction_type)
);
```

## Reaction Types

- **like** - User liked the notification
- **dislike** - User disliked the notification
- **neutral** - Neutral reaction
- **helpful** - User found the notification helpful
- **not_helpful** - User did not find it helpful
- **reported** - User reported the notification (spam, inappropriate, etc.)

## API Endpoints

### Track Reaction

**POST** `/api/notifications/reactions`

```json
{
  "notification_log_id": 123,
  "reaction_type": "helpful",
  "metadata": {
    "reason": "provided useful information"
  }
}
```

### Get Analytics

**GET** `/api/notifications/reactions/analytics?from=2026-01-01&to=2026-01-31`

Returns:
- Overall reaction statistics
- Statistics by channel
- Statistics by notification type
- User reaction trends
- Engagement score

### Get Recommendations

**GET** `/api/notifications/reactions/recommendations`

Returns:
- Recommended channels with scores
- Top notification types
- Optimal send time
- Personalization insights

### Predict Reaction

**POST** `/api/notifications/reactions/predict`

```json
{
  "channel": "email",
  "notification_type": "order_confirmed"
}
```

Returns:
- Probability of positive reaction
- Confidence level
- Recommendation (send/maybe/avoid)
- Factor breakdown

## Services

### NotificationAnalyticsService

**Key Methods:**

- `trackReaction()` - Record user reaction to notification
- `getReactionStats()` - Get overall statistics for tenant
- `getReactionStatsByChannel()` - Statistics grouped by channel
- `getReactionStatsByType()` - Statistics grouped by notification type
- `getUserReactionTrends()` - User's reaction history over time
- `getUserEngagementScore()` - Calculate user engagement score (0-100)
- `getTopPerformingTypes()` - Best-performing notification types

**Caching:** All analytics methods use Redis caching with 15-minute TTL.

### NotificationRecommendationService

**Key Methods:**

- `getRecommendedChannels()` - Get channel recommendations with scores
- `getRecommendedTypes()` - Get notification type recommendations
- `getOptimalSendTime()` - Predict best hour to send notifications
- `predictPositiveReaction()` - Predict if user will react positively
- `getPersonalizationSuggestions()` - Get personalization insights
- `batchGetRecommendations()` - Get recommendations for multiple users
- `trainModel()` - Train/retrain ML model (placeholder for production ML)

**Algorithm:**

The recommendation system uses a hybrid approach:
1. **User History (70% weight)** - Personal reaction history
2. **Tenant-wide Performance (30% weight)** - Aggregate tenant data
3. **Engagement Score Adjustment** - Low-engagement users prefer less intrusive channels

**Confidence Levels:**
- < 5 reactions: 20% confidence
- 5-9 reactions: 50% confidence
- 10-19 reactions: 70% confidence
- 20+ reactions: 90% confidence

## Filament Admin Interface

### NotificationReactionResource

Full CRUD interface for viewing and managing reactions:
- List view with filters (reaction type, channel, date range)
- Detailed view of individual reactions
- Export capabilities

### NotificationReactionStatsWidget

Dashboard widget showing:
- Total reactions (with 7-day trend chart)
- Positive reactions count
- Negative reactions count
- Reaction rate (reactions per delivered notification)

### NotificationRecommendationWidget

Personalized recommendations dashboard showing:
- Channel preferences with scores
- Top notification types
- Optimal send time with confidence
- Personalization insights (engagement level, frequency suggestion)

## Usage Examples

### Tracking a Reaction

```php
use App\Services\NotificationAnalyticsService;

$analyticsService = app(NotificationAnalyticsService::class);

$reaction = $analyticsService->trackReaction(
    notificationLogId: 123,
    userId: 456,
    tenantId: 1,
    reactionType: 'helpful',
    metadata: ['reason' => 'provided useful information'],
    correlationId: 'uuid-here'
);
```

### Getting Recommendations for a User

```php
use App\Services\NotificationRecommendationService;

$recommendationService = app(NotificationRecommendationService::class);

$channels = $recommendationService->getRecommendedChannels($userId, $tenantId);
// Returns: ['email' => ['score' => 85, ...], 'push' => ['score' => 72, ...], ...]

$types = $recommendationService->getRecommendedTypes($userId, $tenantId, 10);
// Returns: ['order_confirmed' => ['score' => 15, ...], 'payment_success' => ['score' => 12, ...], ...]

$optimalTime = $recommendationService->getOptimalSendTime($userId, $tenantId);
// Returns: ['hour' => 10, 'confidence' => 0.85, 'reason' => 'high_confidence']
```

### Predicting Reaction Before Sending

```php
$prediction = $recommendationService->predictPositiveReaction(
    userId: 456,
    tenantId: 1,
    channel: 'email',
    notificationType: 'order_confirmed'
);

if ($prediction['probability'] > 60) {
    // Send notification
} elseif ($prediction['probability'] > 40) {
    // Maybe send, consider alternative
} else {
    // Avoid sending or use different channel
}
```

## Integration with Notification Services

To integrate reaction tracking into existing notification services:

1. **Add reaction tracking endpoint to frontend** - Allow users to react to notifications
2. **Call analytics service** - Use `NotificationAnalyticsService::trackReaction()`
3. **Invalidate caches** - Caches are automatically invalidated on new reactions
4. **Use recommendations** - Check recommendations before sending notifications

Example integration in notification sending flow:

```php
// Before sending notification
$recommendation = $recommendationService->predictPositiveReaction(
    userId: $userId,
    tenantId: $tenantId,
    channel: $channel,
    notificationType: $type
);

if ($recommendation['recommendation'] === 'avoid') {
    // Try alternative channel
    $altChannel = $recommendationService->getRecommendedChannels($userId, $tenantId);
    $channel = array_key_first($altChannel);
}

// Send notification
$notification = $this->sendNotification(...);
```

## Performance Considerations

### Caching Strategy

- Analytics data: 15-minute TTL
- Recommendations: 6-hour TTL
- User trends: 30-minute TTL
- Engagement scores: 2-hour TTL

All caches are tagged by tenant/user for easy invalidation.

### Database Optimization

- Indexes on reaction_type, reacted_at, user_id
- Join queries optimized with proper indexes
- Batch operations for bulk recommendations

### ML Model Training

The current implementation uses a rule-based approach. For production ML:

1. Collect reaction data
2. Extract features (channel, type, time, user attributes)
3. Train model (XGBoost, LightGBM, or neural network)
4. Evaluate performance
5. Deploy to inference endpoint

The `trainModel()` method is a placeholder for this integration.

## Security & Compliance

### Data Privacy

- All reactions are linked to user_id and tenant_id
- PII is not stored in reaction metadata
- Audit logging for all reaction tracking

### Fraud Prevention

- Reaction rate limiting (recommended)
- Validation of notification_log_id ownership
- Audit trail for all reactions

## Future Enhancements

1. **Real ML Integration** - Replace rule-based with actual ML models
2. **A/B Testing** - Test recommendation effectiveness
3. **Multi-armed Bandit** - Explore/exploit for channel selection
4. **Content Personalization** - Recommend notification content variations
5. **Timezone Awareness** - Optimal send time per timezone
6. **Batch Optimization** - Optimize sending schedule for bulk notifications
7. **Feedback Loop** - Continuously improve recommendations

## Testing

Run the migration:

```bash
php artisan migrate
```

Test the API:

```bash
# Track reaction
curl -X POST http://localhost/api/notifications/reactions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"notification_log_id": 1, "reaction_type": "helpful"}'

# Get analytics
curl http://localhost/api/notifications/reactions/analytics \
  -H "Authorization: Bearer {token}"

# Get recommendations
curl http://localhost/api/notifications/reactions/recommendations \
  -H "Authorization: Bearer {token}"

# Predict reaction
curl -X POST http://localhost/api/notifications/reactions/predict \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"channel": "email", "notification_type": "order_confirmed"}'
```

## Files Created

1. `database/migrations/2026_04_26_000013_add_reaction_fields_to_notification_logs_table.php`
2. `app/Models/NotificationReaction.php`
3. `app/Services/NotificationAnalyticsService.php`
4. `app/Services/NotificationRecommendationService.php`
5. `app/Http/Controllers/Api/NotificationReactionController.php`
6. `app/Filament/Widgets/NotificationReactionStatsWidget.php`
7. `app/Filament/Widgets/NotificationRecommendationWidget.php`
8. `app/Filament/Resources/NotificationReactionResource.php`
9. `app/Filament/Resources/NotificationReactionResource/Pages/*.php`
10. `resources/views/filament/widgets/notification-recommendation-widget.blade.php`

## Files Modified

1. `app/Models/NotificationLog.php` - Added reaction fields and scopes
2. `routes/api/notifications.php` - Added reaction API routes

## Summary

The notification reaction tracking and ML recommendation system provides:

- **Comprehensive analytics** - Track user reactions across all channels and types
- **Personalized recommendations** - ML-powered suggestions for channels, types, and timing
- **Real-time insights** - Dashboard widgets for monitoring engagement
- **Admin tools** - Full Filament resource for managing reactions
- **Scalable architecture** - Cached analytics, optimized queries, ready for ML integration

This system enables data-driven notification optimization, improving user engagement while reducing notification fatigue.
