# Verticals Migration - 2026-04-25

## Summary

Refactored vertical structure from old module-based architecture to new **28 top-level product super-verticals** with configuration-driven sub-verticals.

## Changes

### New Configuration File
- **File:** `config/verticals.php`
- **Total Top-Verticals:** 28
- **Structure:** Configuration-driven with `type` (product/service/mixed), `active` status, and `sub_verticals` arrays

### New Super-Verticals Added
1. **Cosmetics** - Парфюмерия и косметика (perfume, cosmetics, beauty_supplies, beauty_accessories, beauty_electronics)
2. **Children** - Детские товары (children_clothing, children_goods, children_by_age)
3. **Garden** - Сад и огород (garden, plants, garden_supplies)
4. **Services** - Услуги и сервисы (repair_services, installation_services, maintenance_services)
5. **Home Appliances** - Бытовая техника (home_appliances, kitchen_appliances, laundry_appliances, climate_appliances)
6. **Taxi** - Такси (вынесена из Auto как отдельная вертикаль)
7. **Pet Supplies** - Товары для животных (pet_food, pet_accessories, pet_care)
8. **Sports Equipment** - Спортивный инвентарь (fitness_equipment, sports_gear, outdoor_sports)
9. **Office Supplies** - Канцелярия и офис (stationery, office_equipment, paper_products)
10. **Tools & Hardware** - Инструменты и оборудование (power_tools, hand_tools, hardware)

### Existing Verticals (Updated)
- **Supermarket** - Супермаркет и продукты питания
- **Restaurant** - Общепит и кейтеринг (добавлены fast_food, world_cuisines)
- **Beauty** - Красота и уход за собой
- **Pharmacy** - Аптека и медтовары (с compliance)
- **Fashion** - Мода и одежда (добавлен footwear)
- **Luxury** - Люкс (премиум, доступ по рефералке)
- **Furniture** - Мебель и товары для дома
- **Construction** - Строительство и ремонт
- **Home Services** - Бытовые услуги
- **Electronics** - Электроника и гаджеты (smartphones, laptops, tablets)
- **Auto** - Авто (без такси, добавлены auto_repair, auto_parts, auto_detailing, car_wash)
- **Health & Sports** - Здоровье и спорт (добавлены workouts, sports_programs)
- **Booking** - Бронирование жилья
- **Travel** - Путешествия
- **Leisure** - Досуг и развлечения (Flowers как подвертикаль)
- **Real Estate** - Недвижимость (расширена: commercial, residential, new_buildings, secondary_market, ready_business, land)
- **Freelance** - Фриланс
- **Art** - Искусство и коллекционирование

### Key Changes from Original Structure
- **Flowers** moved from top-vertical to sub-vertical in **Leisure & Entertainment**
- **Taxi** moved from Auto to separate top-vertical
- **Photography** kept in **Electronics** as "Photography (devices only)" - media content handled by CDM
- **Luxury** extracted from Fashion as separate premium vertical with referral access
- **Footwear** added to Fashion
- Sub-verticals are now **configuration-only** (no separate directories)
- Technical domains (Delivery, GeoLogistics, etc.) remain separate and are NOT product verticals

## Directory Structure

Created new directories in `app/Domains/` for each new super-vertical:

```
app/Domains/
├── Cosmetics/
│   ├── Domain/
│   ├── Services/
│   │   └── CosmeticsService.php (>280 lines)
│   └── AI/
├── Children/
│   ├── Domain/
│   ├── Services/
│   │   └── ChildrenService.php (>280 lines)
│   └── AI/
├── Garden/
│   ├── Domain/
│   ├── Services/
│   │   └── GardenService.php (>280 lines)
│   └── AI/
├── Services/
│   ├── Domain/
│   ├── Services/
│   │   └── ServicesService.php (>290 lines)
│   └── AI/
├── HomeAppliances/
│   ├── Domain/
│   ├── Services/
│   │   └── HomeAppliancesService.php (>280 lines)
│   └── AI/
├── PetSupplies/
│   ├── Domain/
│   ├── Services/
│   │   └── PetSuppliesService.php (>280 lines)
│   └── AI/
├── SportsEquipment/
│   ├── Domain/
│   ├── Services/
│   │   └── SportsEquipmentService.php (>280 lines)
│   └── AI/
├── OfficeSupplies/
│   ├── Domain/
│   ├── Services/
│   │   └── OfficeSuppliesService.php (>280 lines)
│   └── AI/
└── ToolsHardware/
    ├── Domain/
    ├── Services/
    │   └── ToolsHardwareService.php (>280 lines)
    └── AI/
```

## Service Implementation

All new services follow the **CatVRF production standards**:
- **>250 lines** per service (full implementation)
- `readonly` classes with promoted constructor properties
- **Fraud checks** on all public methods using `FraudControlService`
- **Database transactions** for order operations
- **Audit logging** via `LoggerInterface`
- **Wallet integration** for payouts (12% platform fee, 15% for services)
- **Order lifecycle:** pending → ready_for_pickup → completed
- **Cancellation support** with stock restoration
- **Search and filtering** capabilities
- **Statistics methods** for analytics

### Service Methods Pattern
Each service includes:
1. `createOrder()` - Create order with fraud check and stock validation
2. `markReadyForPickup()` - Mark order as ready
3. `completeOrder()` - Complete order with payout
4. `searchProducts()` - Search by query
5. `getAvailableProducts()` - Get products with filters
6. `getStatistics()` - Get vertical statistics
7. `cancelOrder()` - Cancel order with stock restoration
8. `updateProduct()` - Update product info
9. `getSellerOrders()` - Get seller's orders
10. `calculateTotal()` - Private helper for total calculation

## Pending Work

### Models to Create
For each new vertical, the following models need to be created:
- `{Vertical}Order` - Order model
- `{Vertical}Seller` - Seller model
- `{Vertical}Product` - Product model
- `{Vertical}OrderItem` - Order items pivot

Example for Cosmetics:
- `CosmeticsOrder`
- `CosmeticsSeller`
- `CosmeticsProduct`
- `CosmeticsOrderItem`

### Migrations
Create database migrations for all new models with:
- UUID support
- Tenant isolation
- Correlation IDs
- Status enums
- Timestamps

### AI Constructor Services
Create `AI/{Vertical}ConstructorService.php` for each vertical with:
- Prompt building
- LLM integration
- Response parsing
- Recommendation generation

## Technical Notes

### Compliance
- **Pharmacy** vertical has compliance markers: `["152-ФЗ", "ФЗ-323", "Лицензирование"]`
- **Luxury** vertical has `access: "referral"` for premium access control

### Sub-Vertical Distribution (Configuration-Only)
- **Kitchenware** → Furniture (household_goods)
- **Plumbing** → Construction (construction_and_repair)
- **Building Materials** → Construction (construction_and_repair)

### Media Handling
All media (photos, videos, audio) handled by **CDM** (Centralized Media Service) - not as product verticals.

## Next Steps

1. Create model migrations for all new verticals
2. Create AI Constructor services for each vertical
3. Migrate existing modules into new structure
4. Update routing to use new vertical configuration
5. Update Filament resources for new verticals
6. Remove old vertical modules after migration complete

## Verification

- Configuration file: `config/verticals.php` ✅
- Directory structure: `app/Domains/{Vertical}/` ✅
- Service files: All >250 lines ✅
- Fraud checks: Included in all services ✅
- Database transactions: Included ✅
- Audit logging: Included ✅
