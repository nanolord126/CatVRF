# RealEstate Module

Production-ready real estate booking system for CatVRF marketplace with AI-powered features, blockchain verification, and modern fraud detection.

## Architecture Overview

The RealEstate module follows the 9-layer architecture:
- **Layer 1: Models** - Property, PropertyBooking
- **Layer 2: DTOs** - Inline in Service methods
- **Layer 3: Services** - PropertyBookingService, RealEstateDesignConstructorService
- **Layer 4: Requests** - CreatePropertyBookingRequest
- **Layer 5: Resources** - PropertyBookingResource
- **Layer 6: Events** - BookingCreated, BookingConfirmed, DealCompleted
- **Layer 7: Listeners** - SendBookingConfirmationNotification
- **Layer 8: Jobs** - ProcessBookingExpirationJob
- **Layer 9: Filament** - PropertyBookingResource

## Killer Features

### 1. AI-Powered Predictive Deal Scoring
- **Credit Score Analysis** - ML-based creditworthiness evaluation (40% weight)
- **Legal Risk Assessment** - Document and ownership verification (30% weight)
- **Liquidity Prediction** - Market demand and price trend analysis (30% weight)
- **Overall Recommendation** - Automated deal approval/rejection based on composite score

### 2. ML Fraud Detection
- User history analysis (booking patterns, cancellations)
- Document verification scoring
- Price anomaly detection
- Real-time fraud blocking with configurable threshold (default: 0.7)

### 3. Blockchain Document Verification
- SHA-256 hashing of ownership, cadastre, and technical documents
- Immutable record on blockchain for audit trail
- Smart contract support for rental agreements
- Document tamper detection

### 4. Real-Time Booking with Hold Slots
- B2C: 15-minute hold slot
- B2B: 60-minute hold slot
- Redis-based distributed locking for conflict prevention
- Automatic expiration processing via queue jobs

### 5. Dynamic Pricing
- AI demand forecasting based on time, season, and location
- Peak hour detection (10-12, 17-19)
- Weekend multiplier (+20%)
- Seasonality adjustments (Spring +15%, Summer -10%, Fall +20%)
- Flash discounts for last-minute bookings (<24h)

### 6. B2C/B2B Support
- **B2C**: Standard pricing, quick booking, individual users
- **B2B**: 15% discount, fleet rental, commission split (8% platform, 3% agent, 2% referral)
- INN validation for business groups
- Credit limits for corporate accounts

### 7. WebRTC Video Calls
- Instant video viewing with realtor/owner
- Token-based room authentication
- 2-hour session expiration
- Participant authorization checks

### 8. Escrow Payment Mode
- Hold funds during booking process
- Automatic capture on deal completion
- Release on cancellation
- Split payment support for multi-party transactions

### 9. CRM Integration
- Status sync at every stage (pending, confirmed, completed, cancelled)
- Deal completion recording
- Cancellation tracking with reasons
- Full audit trail with correlation IDs

### 10. AI Design Constructor
- Interior style analysis (minimalist, Scandinavian, loft)
- Layout optimization recommendations
- Lighting analysis with smart home suggestions
- Material recommendations based on climate
- 3D/AR visualization generation
- Renovation cost estimation with timeline

## API Endpoints

### Bookings
- `POST /api/v1/real-estate/bookings` - Create booking
- `GET /api/v1/real-estate/bookings` - List bookings
- `GET /api/v1/real-estate/bookings/{id}` - Get booking details
- `POST /api/v1/real-estate/bookings/{id}/confirm` - Confirm booking
- `POST /api/v1/real-estate/bookings/{id}/complete` - Complete deal
- `POST /api/v1/real-estate/bookings/{id}/cancel` - Cancel booking
- `POST /api/v1/real-estate/bookings/{id}/video-call` - Initiate video call

### Properties
- `GET /api/v1/real-estate/properties/{id}/available-slots` - Get available viewing slots

## Configuration

All configuration in `config/real_estate.php`:

```php
'booking' => [
    'hold_slot_b2c_minutes' => 15,
    'hold_slot_b2b_minutes' => 60,
    'cache_ttl_seconds' => 3600,
    'booking_lock_ttl' => 300,
],

'ai' => [
    'constructor_enabled' => true,
    'vision_provider' => 'openai',
    'cache_ttl_seconds' => 3600,
],

'fraud' => [
    'max_score_threshold' => 0.7,
    'enable_ml_detection' => true,
],

'pricing' => [
    'dynamic_pricing_enabled' => true,
    'b2b_discount_percent' => 15,
    'peak_demand_multiplier' => 1.1,
],
```

## Database Schema

### real_estate_bookings
- UUID-based identification
- Full tenant and business group isolation
- Deal score, fraud score fields
- Blockchain verification flags
- WebRTC room IDs
- Escrow tracking
- Commission split (B2B)

## Security Features

1. **Tenant Isolation** - Global scope on all models
2. **Fraud Control** - Pre-mutation fraud checks
3. **Idempotency** - Duplicate request prevention
4. **Correlation IDs** - Full audit trail
5. **DB Transactions** - ACID compliance
6. **Rate Limiting** - Via middleware
7. **Policy-Based Authorization** - Granular permissions

## Testing

### Feature Tests
- Booking creation, confirmation, completion, cancellation
- B2B commission split
- Fraud score blocking
- Hold slot expiration
- Video call initiation
- Available slots retrieval

### Unit Tests
- Fraud score calculation
- Deal score calculation
- Dynamic pricing
- Demand prediction
- Commission split

## Queue Jobs

### ProcessBookingExpirationJob
- Automatically cancels expired pending bookings
- Releases escrow holds
- Sends notifications
- Retries on failure (3 attempts)

## Events & Listeners

- **BookingCreated** → No listeners (can be extended)
- **BookingConfirmed** → SendBookingConfirmationNotification
- **DealCompleted** → No listeners (can be extended)

## Filament Admin Panel

Full CRUD interface for PropertyBooking management:
- List view with filters (status, B2B/B2C)
- Create/Edit forms
- Status badges with color coding
- Fraud score display
- Blockchain verification indicators

## How This Beats CIAN/Avito

1. **AI Predictive Scoring** - Neither has ML-based deal risk assessment
2. **Blockchain Verification** - Immutable document proof, not just upload
3. **Dynamic Pricing** - Real-time demand-based pricing, not fixed rates
4. **WebRTC Integration** - Built-in video calls, no third-party needed
5. **Escrow Mode** - Secure payment holding, platform doesn't hold funds manually
6. **B2B Fleet Rental** - Corporate discounts and commission splitting
7. **AI Design Constructor** - 3D/AR visualization with renovation estimates
8. **Real-Time Fraud ML** - Continuous learning, not rule-based blocking

## Risks Closed

1. **Payment Fraud** - ML detection + escrow hold
2. **Document Forgery** - Blockchain verification
3. **Double Booking** - Redis distributed locking
4. **Race Conditions** - DB transactions + idempotency
5. **Data Leakage** - Tenant isolation + correlation ID audit
6. **Payment Disputes** - Full audit trail with blockchain proof
7. **System Overload** - Queue-based expiration processing
8. **Unauthorized Access** - Policy-based authorization

## Performance Optimizations

1. **Redis Caching** - Available slots (1h TTL), AI results (1h TTL)
2. **Database Indexes** - Composite indexes on tenant+status, property+slot
3. **Queue Processing** - Async expiration handling
4. **Lazy Loading** - Eloquent relationships loaded on demand
5. **Pagination** - List endpoints with configurable page size

## Future Enhancements

- Smart contract deployment for automated rental agreements
- Integration with credit scoring APIs (Experian, Equifax)
- AR mobile app for on-site property visualization
- Voice assistant integration for booking management
- Predictive maintenance for property management
- Integration with property management systems
- Multi-language support for international markets

## Installation

1. Run migrations:
```bash
php artisan migrate --path=database/migrations/2026_01_01_000003_create_real_estate_bookings_table.php
```

2. Register service provider in `config/app.php`:
```php
Modules\RealEstate\Providers\RealEstateServiceProvider::class,
Modules\RealEstate\Providers\EventServiceProvider::class,
```

3. Publish configuration:
```bash
php artisan vendor:publish --tag=real_estate-config
```

4. Run seeders (optional):
```bash
php artisan db:seed --class=RealEstateSeeder
```

## Support

For issues or questions, refer to the main CatVRF documentation or create an issue in the repository.
