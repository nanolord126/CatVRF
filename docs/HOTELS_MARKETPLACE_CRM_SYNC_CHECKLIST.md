# Marketplace Booking to CRM Sync Integration Checklist

This checklist provides a step-by-step guide for integrating external marketplace bookings (Booking.com, Ostrovok, Airbnb, etc.) with the Hotels CatCRM system.

## Prerequisites

- [ ] Hotels CRM module activated (migrations run)
- [ ] Loyalty System module activated
- [ ] Payment Integration configured
- [ ] Marketplace API credentials obtained
- [ ] Tenant created for the hotel/venue

## 1. API Credentials Setup

### Booking.com
- [ ] Register hotel on Booking.com Connectivity Partner Portal
- [ ] Obtain API Key (Authorization header)
- [ ] Configure webhook endpoint for booking notifications
- [ ] Set up hotel ID mapping

### Ostrovok
- [ ] Register on Ostrovok Partner Portal
- [ ] Obtain API Key and Hotel ID
- [ ] Configure webhook for booking updates
- [ ] Set up rate plan mapping

### Airbnb
- [ ] Register on Airbnb API Developer Portal
- [ ] Obtain OAuth credentials
- [ ] Configure webhook for booking events
- [ ] Set up listing ID mapping

## 2. Database Configuration

### External Booking Mapping
- [ ] Create `external_booking_references` table (if not exists)
  - `id` (primary key)
  - `booking_id` (foreign key to bookings)
  - `source` (booking_com, ostrovok, airbnb, etc.)
  - `external_id` (external booking ID)
  - `external_confirmation_code` (external confirmation code)
  - `sync_status` (pending, synced, failed)
  - `synced_at` (timestamp)
  - `last_sync_error` (text, nullable)
  - `created_at`, `updated_at`

### Configuration Settings
- [ ] Add marketplace credentials to `.env`
  ```env
  BOOKING_COM_API_KEY=your_key_here
  BOOKING_COM_HOTEL_ID=hotel_id_here
  BOOKING_COM_WEBHOOK_SECRET=webhook_secret
  
  OSTROVOK_API_KEY=your_key_here
  OSTROVOK_HOTEL_ID=hotel_id_here
  
  AIRBNB_CLIENT_ID=client_id_here
  AIRBNG_CLIENT_SECRET=client_secret_here
  ```

## 3. Service Implementation

### Marketplace Integration Service
Create `Modules/Hotels/Application/Services/MarketplaceIntegrationService.php`:

- [ ] `syncBookingFromMarketplace($source, $externalBookingData)` method
  - Parse external booking data
  - Map to CRM booking structure
  - Create/update booking in CRM
  - Store external reference
  - Trigger confirmation email

- [ ] `pushBookingToMarketplace($bookingId, $targetMarketplace)` method
  - Map CRM booking to marketplace format
  - Send booking to marketplace API
  - Handle rate limit errors
  - Store external reference

- [ ] `syncAvailabilityToMarketplace($venueId, $marketplace)` method
  - Get current room availability from CRM
  - Push to marketplace API
  - Handle calendar sync conflicts

- [ ] `syncRatesToMarketplace($venueId, $marketplace)` method
  - Get current room rates from CRM
  - Push to marketplace API
  - Handle currency conversion

### Webhook Handlers
Create webhook endpoints for each marketplace:

- [ ] `POST /api/webhooks/booking-com` - Booking.com webhook handler
  - Verify webhook signature
  - Parse booking notification
  - Call `syncBookingFromMarketplace`
  - Return 200 OK

- [ ] `POST /api/webhooks/ostrovok` - Ostrovok webhook handler
  - Verify webhook signature
  - Parse booking notification
  - Call `syncBookingFromMarketplace`
  - Return 200 OK

- [ ] `POST /api/webhooks/airbnb` - Airbnb webhook handler
  - Verify OAuth signature
  - Parse booking event
  - Call `syncBookingFromMarketplace`
  - Return 200 OK

## 4. Data Mapping

### Booking.com → CRM Mapping
| Booking.com Field | CRM Field | Notes |
|------------------|-----------|-------|
| reservation.id | bookings.uuid | Generate UUID |
| reservation.hotel | bookings.venue_id | Map via hotel_id |
| reservation.room | bookings.booking_items.room_id | Map via room code |
| reservation.checkin | bookings.check_in_date | Convert timezone |
| reservation.checkout | bookings.check_out_date | Convert timezone |
| reservation.customer.first_name | guests.first_name | Create guest if not exists |
| reservation.customer.last_name | guests.last_name | |
| reservation.customer.email | guests.email | |
| reservation.customer.phone | guests.phone | |
| reservation.price | bookings.total_amount | Convert currency |
| reservation.currency | bookings.currency | |
| reservation.status | bookings.status | Map statuses |

### Ostrovok → CRM Mapping
| Ostrovok Field | CRM Field | Notes |
|----------------|-----------|-------|
| order.id | bookings.uuid | Generate UUID |
| order.hotel_id | bookings.venue_id | Map via hotel_id |
| order.room_type_id | bookings.booking_items.room_type_id | Map via room type code |
| order.arrival_date | bookings.check_in_date | Convert timezone |
| order.departure_date | bookings.check_out_date | Convert timezone |
| order.client.name | guests.first_name, guests.last_name | Split name |
| order.client.email | guests.email | |
| order.client.phone | guests.phone | |
| order.price | bookings.total_amount | Convert currency |
| order.status | bookings.status | Map statuses |

### Airbnb → CRM Mapping
| Airbnb Field | CRM Field | Notes |
|--------------|-----------|-------|
| reservation.code | bookings.confirmation_code | Use as confirmation |
| reservation.listing_id | bookings.venue_id | Map via listing |
| reservation.start_date | bookings.check_in_date | Convert timezone |
| reservation.end_date | bookings.check_out_date | Convert timezone |
| reservation.guest.first_name | guests.first_name | |
| reservation.guest.last_name | guests.last_name | |
| reservation.guest.email | guests.email | |
| reservation.total_price | bookings.total_amount | Convert currency |
| reservation.status | bookings.status | Map statuses |

## 5. Status Mapping

### Booking.com Statuses
| Booking.com | CRM Status |
|-------------|------------|
| new | pending |
| confirmed | prepaid |
| cancelled | cancelled |
| no_show | no_show |
| modified | pending (re-sync) |

### Ostrovok Statuses
| Ostrovok | CRM Status |
|----------|------------|
| new | pending |
| confirmed | prepaid |
| cancelled | cancelled |
| completed | checked_out |

### Airbnb Statuses
| Airbnb | CRM Status |
|--------|------------|
| pending | pending |
| accepted | prepaid |
| confirmed | paid |
| cancelled | cancelled |
| completed | checked_out |

## 6. Payment Integration

- [ ] Configure payment link for marketplace bookings
  - Set `payment_status` to `paid` for confirmed marketplace bookings
  - Link to `PaymentRecord` with `payable_type` = BookingModel
  - Set `source` field to marketplace name (booking_com, ostrovok, airbnb)

- [ ] Disable payment processing for marketplace bookings
  - Marketplace payments handled externally
  - CRM only tracks payment status
  - No payment gateway calls for marketplace bookings

## 7. Loyalty Integration

- [ ] Enable loyalty points for marketplace bookings
  - Use `BookingLoyaltyIntegration` service
  - Points calculated based on booking amount
  - Tier upgrades processed automatically
  - Configure loyalty program ID in `config/crm-hotels.php`

- [ ] Guest profile creation
  - Create `Guest` profile from marketplace customer data
  - Link to existing guest if email/phone matches
  - Enroll in loyalty program automatically

## 8. Housekeeping Integration

- [ ] Auto-trigger housekeeping on checkout
  - Use `HousekeepingService::scheduleCleaning`
  - Schedule for check-out date + 2 hours
  - Set priority based on room type
  - Assign to default housekeeping team

## 9. Error Handling & Logging

- [ ] Implement retry logic for failed syncs
  - Exponential backoff (1s, 5s, 15s, 1m, 5m)
  - Max 5 retry attempts
  - Mark as failed after max retries

- [ ] Log all sync operations
  - Use `Log::channel('marketplace-sync')`
  - Include correlation_id for tracing
  - Log external booking ID, CRM booking ID, status
  - Log errors with full stack trace

- [ ] Alert on critical failures
  - Send notification on 5+ consecutive failures
  - Alert for payment amount discrepancies > 10%
  - Alert for booking conflicts (double booking)

## 10. Testing

### Unit Tests
- [ ] Test data mapping for each marketplace
- [ ] Test status conversion logic
- [ ] Test webhook signature verification
- [ ] Test retry logic

### Integration Tests
- [ ] Test booking sync from Booking.com sandbox
- [ ] Test booking sync from Ostrovok sandbox
- [ ] Test booking sync from Airbnb sandbox
- [ ] Test payment linking
- [ ] Test loyalty points accrual

### Manual Testing
- [ ] Create test booking on Booking.com
  - Verify sync to CRM
  - Verify guest profile created
  - Verify loyalty points awarded
  - Verify housekeeping scheduled

- [ ] Cancel test booking on marketplace
  - Verify status updated in CRM
  - Verify housekeeping cancelled
  - Verify refund processed (if applicable)

## 11. Monitoring

- [ ] Set up monitoring dashboard
  - Sync success rate (target > 99%)
  - Average sync latency (target < 30s)
  - Failed syncs count
  - Payment discrepancies

- [ ] Configure alerts
  - Sync success rate < 95%
  - Sync latency > 60s
  - Payment discrepancy > 5%
  - Webhook endpoint down

## 12. Documentation

- [ ] Document API endpoints
- [ ] Document webhook handling
- [ ] Document data mapping rules
- [ ] Document troubleshooting procedures
- [ ] Create SOP for manual sync fixes

## Post-Integration Checklist

- [ ] Run full sync for historical bookings (last 30 days)
- [ ] Verify all active bookings synced correctly
- [ ] Train hotel staff on CRM usage
- [ ] Set up automated daily sync reports
- [ ] Schedule periodic sync audits (weekly)
- [ ] Document integration handoff procedures

## Troubleshooting

### Common Issues

**Booking not appearing in CRM**
1. Check webhook logs for errors
2. Verify webhook signature
3. Check if guest already exists (email/phone match)
4. Verify venue ID mapping

**Payment amount mismatch**
1. Check currency conversion rates
2. Verify booking.com includes taxes/fees
3. Check if deposit amount included in total

**Double booking detected**
1. Check room availability sync
2. Verify calendar sync frequency
3. Check if marketplace has real-time availability

**Guest not enrolled in loyalty**
1. Check loyalty program ID configuration
2. Verify guest profile created successfully
3. Check loyalty service logs

## Security Considerations

- [ ] Encrypt API credentials at rest
- [ ] Use HTTPS for all webhook endpoints
- [ ] Implement rate limiting on webhook endpoints
- [ ] Validate all incoming data
- [ ] Sanitize guest PII before storage
- [ ] Regular security audits of integration

## Compliance

- [ ] 152-ФZ compliance for guest data
- [ ] GDPR compliance for EU marketplaces
- [ ] Data retention policy for booking data
- [ ] Right to erasure implementation
- [ ] Data export functionality for guest requests
