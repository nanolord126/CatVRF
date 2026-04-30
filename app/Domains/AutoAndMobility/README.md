# Auto & Mobility Super-Vertical

**Purpose:** Orchestration layer for all mobility-related services (Auto, Taxi, CarRental).

**Structure:**
```
AutoAndMobility/
├── SubVerticals/
│   ├── Auto/              # Auto sales, vehicle catalog, maintenance
│   ├── CarRental/         # Car rental bookings, fleet management
│   └── Taxi/              # Taxi rides, driver matching, surge pricing
├── Domain/                # Super-vertical specific domain logic
├── Services/
│   └── AutoAndMobilityService.php  # Main orchestration service
└── AI/
    └── AutoAndMobilityConstructorService.php  # AI-powered recommendations
```

**Sub-Verticals:**

### 1. Auto (Vehicle Sales)
- Vehicle catalog management
- Vehicle listings
- Maintenance scheduling
- Vehicle history tracking
- **Source:** `modules/Auto/`

### 2. CarRental
- Rental booking management
- Fleet management
- Vehicle availability
- Rental pricing
- **Status:** To be created (currently in config as queue 2)

### 3. Taxi
- Ride booking
- Driver matching
- Dynamic surge pricing
- Route optimization
- **Source:** `modules/Taxi/`

**Service Responsibilities:**

- `AutoAndMobilityService`: Main orchestration, cross-sub-vertical operations
- `AutoAndMobilityConstructorService`: AI recommendations, route optimization, pricing

**Migration Status:**
- [x] Directory structure created
- [x] Super-vertical service created
- [x] AI constructor service created
- [ ] Auto sub-vertical migrated from `modules/Auto/`
- [ ] Taxi sub-vertical migrated from `modules/Taxi/`
- [ ] CarRental sub-vertical created
- [ ] Route files updated
- [ ] Service providers updated
- [ ] Tests migrated

**Integration Points:**
- Technical domains: Geo (location), Payment, Cart, FraudDetection
- Common domain: Product (for vehicle listings), Category (vehicle types)
