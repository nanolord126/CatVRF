# Beauty & Personal Care Super-Vertical

**Purpose:** Orchestration layer for beauty and personal care services.

**Structure:**
```
BeautyAndPersonalCare/
├── SubVerticals/
│   └── Beauty/              # Beauty salons, masters, appointments
├── Domain/                  # Super-vertical specific domain logic
├── Services/
│   └── BeautyAndPersonalCareService.php  # Main orchestration service
└── AI/
    └── BeautyAndPersonalCareConstructorService.php  # AI-powered recommendations
```

**Sub-Verticals:**

### 1. Beauty (Beauty Salons & Services)
- Appointment booking
- Master profiles and schedules
- Service catalog
- Certifications and specializations
- Venue management
- **Source:** `modules/BeautyMasters/`

**Service Responsibilities:**

- `BeautyAndPersonalCareService`: Main orchestration, cross-sub-vertical operations
- `BeautyAndPersonalCareConstructorService`: AI recommendations, treatment suggestions, pricing

**Migration Status:**
- [x] Directory structure created
- [x] Super-vertical service created
- [x] AI constructor service created
- [ ] Beauty sub-vertical migrated from `modules/BeautyMasters/`
- [ ] Route files updated
- [ ] Service providers updated
- [ ] Tests migrated

**Integration Points:**
- Technical domains: Geo (location), Payment, Cart, FraudDetection
- Common domain: Product (for beauty products), Category (service types)

**Note:** Pharmacy has been extracted as a separate super-vertical due to medical compliance requirements (152-ФЗ, ФЗ-323, Licensing).
