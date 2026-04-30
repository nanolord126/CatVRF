# Example Loyalty Rules for Restaurant

## Basic Order-Based Rule

```json
{
  "name": "Standard Points",
  "type": "order_based",
  "calculation_type": "percentage",
  "points_value": 1.0,
  "conditions": {
    "min_amount": 0
  },
  "point_multiplier": 1.0,
  "priority": 1
}
```

## Happy Hours Rule

```json
{
  "name": "Happy Hours Bonus",
  "type": "time_based",
  "calculation_type": "multiplier",
  "point_multiplier": 2.0,
  "conditions": {
    "day_of_week": ["friday", "saturday"],
    "hour_start": 18,
    "hour_end": 22
  },
  "priority": 10
}
```

## First Visit Bonus

```json
{
  "name": "First Visit Welcome",
  "type": "first_visit",
  "calculation_type": "fixed",
  "points_value": 500,
  "point_multiplier": 1.0,
  "max_uses_per_guest": 1,
  "priority": 20
}
```

## High Value Order Bonus

```json
{
  "name": "Big Spender Bonus",
  "type": "order_based",
  "calculation_type": "fixed",
  "points_value": 200,
  "conditions": {
    "min_amount": 5000
  },
  "point_multiplier": 1.0,
  "priority": 5
}
```

## Specific Menu Items Bonus

```json
{
  "name": "Dessert Lover",
  "type": "item_based",
  "calculation_type": "fixed",
  "points_value": 100,
  "conditions": {
    "item_ids": [15, 16, 17, 18],
    "item_category": "dessert"
  },
  "point_multiplier": 1.0,
  "priority": 8
}
```

## Weekend Multiplier

```json
{
  "name": "Weekend Warrior",
  "type": "time_based",
  "calculation_type": "multiplier",
  "point_multiplier": 1.5,
  "conditions": {
    "day_of_week": ["saturday", "sunday"]
  },
  "priority": 15
}
```

## Birthday Bonus (Automatic)

```json
{
  "name": "Birthday Treat",
  "type": "birthday",
  "calculation_type": "fixed",
  "points_value": 1000,
  "point_multiplier": 1.0,
  "max_uses_per_guest": 1,
  "priority": 25
}
```

## Referral Bonus

```json
{
  "name": "Bring a Friend",
  "type": "referral",
  "calculation_type": "fixed",
  "points_value": 300,
  "point_multiplier": 1.0,
  "max_uses_per_guest": null,
  "priority": 30
}
```

## Tier-Specific Bonus (Gold & Platinum)

```json
{
  "name": "VIP Treatment",
  "type": "order_based",
  "calculation_type": "multiplier",
  "point_multiplier": 1.5,
  "conditions": {
    "min_amount": 1000
  },
  "target_tiers": ["gold", "platinum"],
  "priority": 12
}
```

## Example Rewards

### Free Dessert
```json
{
  "name": "Free Dessert",
  "type": "free_item",
  "points_cost": 500,
  "value_type": "item",
  "item_code": "dessert_free",
  "target_tiers": ["bronze", "silver", "gold", "platinum"]
}
```

### 10% Discount
```json
{
  "name": "10% Off Next Order",
  "type": "discount",
  "points_cost": 1000,
  "value_type": "percentage",
  "value_amount": 0.10,
  "max_redemptions_per_guest": 1
}
```

### Table Upgrade
```json
{
  "name": "VIP Table",
  "type": "upgrade",
  "points_cost": 2000,
  "value_type": "fixed",
  "target_tiers": ["gold", "platinum"]
}
```

### Free Drink
```json
{
  "name": "Complimentary Drink",
  "type": "free_item",
  "points_cost": 300,
  "value_type": "item",
  "item_code": "drink_free"
}
```
