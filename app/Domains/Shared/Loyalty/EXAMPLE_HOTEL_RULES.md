# Example Loyalty Rules for Hotel

## Standard Stay Points

```json
{
  "name": "Stay Points",
  "type": "order_based",
  "calculation_type": "percentage",
  "points_value": 10,
  "conditions": {
    "min_amount": 0
  },
  "point_multiplier": 1.0,
  "priority": 1
}
```

## Extended Stay Bonus

```json
{
  "name": "Long Stay Bonus",
  "type": "order_based",
  "calculation_type": "fixed",
  "points_value": 500,
  "conditions": {
    "min_nights": 5
  },
  "point_multiplier": 1.0,
  "priority": 5
}
```

## Suite Booking Bonus

```json
{
  "name": "Suite Lover",
  "type": "item_based",
  "calculation_type": "multiplier",
  "point_multiplier": 1.5,
  "conditions": {
    "room_type": ["suite", "presidential_suite", "penthouse"]
  },
  "priority": 10
}
```

## First Booking Bonus

```json
{
  "name": "First Stay Welcome",
  "type": "first_visit",
  "calculation_type": "fixed",
  "points_value": 1000,
  "point_multiplier": 1.0,
  "max_uses_per_guest": 1,
  "priority": 20
}
```

## Direct Booking Bonus

```json
{
  "name": "Book Direct",
  "type": "order_based",
  "calculation_type": "multiplier",
  "point_multiplier": 1.2,
  "conditions": {
    "booking_channel": "direct"
  },
  "priority": 15
}
```

## Seasonal Promotion

```json
{
  "name": "Summer Special",
  "type": "time_based",
  "calculation_type": "multiplier",
  "point_multiplier": 1.5,
  "conditions": {
    "months": ["june", "july", "august"]
  },
  "starts_at": "2026-06-01 00:00:00",
  "ends_at": "2026-08-31 23:59:59",
  "priority": 12
}
```

## Spa Service Bonus

```json
{
  "name": "Spa Add-on",
  "type": "item_based",
  "calculation_type": "fixed",
  "points_value": 200,
  "conditions": {
    "service_category": "spa",
    "min_amount": 5000
  },
  "priority": 8
}
```

## Conference Booking Bonus

```json
{
  "name": "Business Meeting",
  "type": "order_based",
  "calculation_type": "fixed",
  "points_value": 2000,
  "conditions": {
    "booking_type": "conference",
    "min_amount": 50000
  },
  "priority": 7
}
```

## Tier-Specific Privileges

### Late Check-out (Gold & Platinum)
```json
{
  "name": "Late Check-out",
  "type": "privilege",
  "points_cost": 0,
  "target_tiers": ["gold", "platinum"],
  "conditions": {
    "request_hours_before": 24
  }
}
```

### Room Upgrade (Platinum)
```json
{
  "name": "Room Upgrade",
  "type": "upgrade",
  "points_cost": 3000,
  "value_type": "fixed",
  "target_tiers": ["platinum"],
  "max_redemptions_per_guest": 2
}
```

### Free Breakfast
```json
{
  "name": "Complimentary Breakfast",
  "type": "service",
  "points_cost": 1500,
  "value_type": "fixed",
  "target_tiers": ["silver", "gold", "platinum"]
}
```

### Free Airport Transfer
```json
{
  "name": "Airport Transfer",
  "type": "service",
  "points_cost": 5000,
  "value_type": "fixed",
  "target_tiers": ["gold", "platinum"],
  "max_redemptions_per_guest": 1
}
```

### Late Check-out Pass
```json
{
  "name": "2 PM Check-out",
  "type": "privilege",
  "points_cost": 1000,
  "value_type": "fixed",
  "target_tiers": ["silver", "gold", "platinum"]
}
```

### Welcome Amenity
```json
{
  "name": "Welcome Amenity",
  "type": "free_item",
  "points_cost": 2000,
  "value_type": "item",
  "item_code": "welcome_amenity",
  "target_tiers": ["platinum"],
  "max_redemptions_per_guest": 1
}
```

### Free Mini Bar
```json
{
  "name": "Mini Bar Credit",
  "type": "cashback",
  "points_cost": 2500,
  "value_amount": 3000,
  "target_tiers": ["gold", "platinum"]
}
```
