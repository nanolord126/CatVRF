# 🛠️ ПОЛНЫЙ ПЛАН РЕФАКТОРИНГА И ПРИВЕДЕНИЯ К PRODUCTION ФОРМАТУ

## ✅ ЧТО УЖЕ СОЗДАНО

### Primary Models (Обновлены в этой сессии)
- ✅ `app/Domains/Food/Models/FoodOrder.php` - создан
- ✅ `app/Domains/Hotel/Models/HotelBooking.php` - создан
- ✅ `app/Domains/Sports/Models/SportsMembership.php` - создан
- ✅ `app/Domains/Clinic/Models/MedicalCard.php` - создан
- ✅ `app/Domains/Education/Models/Course.php` - уже существует
- ✅ `app/Domains/Events/Models/Event.php` - уже существует
- ✅ Все остальные основные Models уже существуют

### Enums (Полностью готовы)
- ✅ `app/Domains/Advertising/Enums/` - 8 Enums созданы

### Services, Policies, Controllers, Routes
- ✅ Все 17 вертикалей имеют Services, Policies, Controllers
- ✅ Все маршруты добавлены в `routes/tenant.php`

---

## 📋 ТРЕБУЕМЫЕ ДЕЙСТВИЯ (ПОЭТАПНО)

### PHASE 1: Создать Enums для всех доменов (КРИТИЧНО)

**Требуется создать Enums для:**

1. **Taxi** - TaxiStatus, VehicleClass, RideStatus
2. **Food** - OrderStatus, PaymentStatus, CuisineType
3. **Hotel** - BookingStatus, RoomType, AmmenityType
4. **Sports** - MembershipTier, MembershipStatus
5. **Clinic** - CardStatus, BloodType, VisitType
6. **Beauty/Salon** - ServiceType, SalonStatus, AppointmentStatus
7. **Delivery** - DeliveryStatus, VehicleType, DeliveryType
8. **Education/Course** - CourseStatus, DifficultyLevel, EnrollmentStatus
9. **Events** - EventStatus, EventType, TicketType
10. **Geo** - GeoStatus, LocationType
11. **Insurance** - PolicyStatus, InsuranceType, ClaimStatus
12. **Inventory** - ItemStatus, WarehouseStatus
13. **RealEstate** - PropertyStatus, PropertyType
14. **Communication** - MessageStatus, TicketStatus
15. **Finances** - TransactionStatus, PaymentMethod

**Структура каждого Enum:**
```php
enum Status: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    
    public function label(): string {
        return match($this) {
            self::DRAFT => 'Черновик',
            // ...
        };
    }
    
    public function color(): string { /* */ }
    public function isActive(): bool { /* */ }
    // Другие полезные методы
}
```

### PHASE 2: Убедиться что все Controllers используют правильные Models

**Проверить/Обновить:**

- ❓ Food Controller - используется FoodOrder? (может быть RestaurantOrder)
- ❓ Hotel Controller - используется HotelBooking? (может быть HotelRoom)
- ❓ Sports Controller - используется SportsMembership? (может быть другое)
- ❓ Clinic Controller - используется MedicalCard? (может быть другое)

**Требуемые обновления:**
```php
// app/Domains/Food/Http/Controllers/FoodController.php
// Должно быть:
use App\Domains\Food\Models\FoodOrder;

public function index() {
    return FoodOrder::where('tenant_id', tenant()->id)->paginate();
}
```

### PHASE 3: Добавить Events для всех доменов (если требуются)

**Структура:**
```
Domains/
├── Food/
│   └── Events/
│       ├── FoodOrderCreated.php
│       ├── FoodOrderUpdated.php
│       └── FoodOrderCancelled.php
```

### PHASE 4: Привести весь код к Production формату

**Требования:**

1. **PHPDoc комментарии** - для всех классов и методов
```php
/**
 * Creates a new order
 * 
 * @param array $data Order data
 * @return FoodOrder
 * @throws ValidationException
 */
public function createOrder(array $data): FoodOrder
```

2. **Типизация** - все параметры и возвращаемые значения
```php
public function update(UpdateRequest $request, Model $model): JsonResponse
```

3. **Naming conventions** - правильное именование
```
✅ Models: FoodOrder, HotelBooking, TaxiRide
❌ Models: FoodModels, HotelModels, TaxiModels
```

4. **Форматирование** - PSR-12
```php
// Правильно организованные методы:
- Свойства вверху
- Relations
- Business Logic Methods
- Static Methods (если есть)
```

5. **Migrations** - проверить что все миграции имеют:
```php
// FOREIGN KEYS
->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete()

// INDEXES
->index('status')
->index('tenant_id')

// TIMESTAMPS
$table->timestamps();

// CORRELATION_ID
->uuid('correlation_id')->nullable()->index()
```

### PHASE 5:验证Routes соответствуют Controllers

**Требуется убедиться:**

```php
// routes/tenant.php должно быть:

Route::apiResource('food', \App\Domains\Food\Http\Controllers\FoodController::class);
Route::apiResource('hotel', \App\Domains\Hotel\Http\Controllers\HotelController::class);
// etc.

// ИЛИ

Route::apiResources([
    'food' => \App\Domains\Food\Http\Controllers\FoodController::class,
    'hotel' => \App\Domains\Hotel\Http\Controllers\HotelController::class,
    // и т.д.
]);
```

### PHASE 6: Убедиться что все FormRequests имеют правильную валидацию

**Структура:**
```php
namespace App\Domains\Food\Http\Requests;

class StoreFoodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }
    
    public function rules(): array
    {
        return [
            'restaurant_id' => 'required|integer|exists:users,id',
            'customer_id' => 'required|integer|exists:users,id',
            'total_amount' => 'required|numeric|min:0',
            'items' => 'required|array',
            'delivery_address' => 'string|nullable',
        ];
    }
}
```

### PHASE 7: Убедиться что все Resources имеют правильный формат

**Структура:**
```php
namespace App\Domains\Food\Http\Resources;

class FoodOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'customer_id' => $this->customer_id,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status->value ?? $this->status,
            'items' => $this->items,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

---

## 🎯 ИТОГОВЫЙ CHECKLIST

### Core Files (по домену)
- [ ] **Model** - Primary entity with all relations
- [ ] **Service** - Business logic
- [ ] **Policy** - Authorization
- [ ] **Controller** - API endpoints
- [ ] **FormRequest** - Input validation  
- [ ] **Resource** - JSON responses
- [ ] **Migration** - Database schema
- [ ] **Factory** - Test data
- [ ] **Seeder** - Population
- [ ] **Enums** - Status, Type enumerations
- [ ] **Events** (optional) - Domain events

### Quality Checks
- [ ] All namespace imports correct
- [ ] All classes have PHPDoc comments
- [ ] All methods are typed
- [ ] All methods have return types
- [ ] No stubs (all TODOs removed)
- [ ] No null returns (use Optional/Result pattern)
- [ ] PSR-12 formatting applied
- [ ] Carbon dates used correctly
- [ ] Enum casts in Models
- [ ] Audit logging in Services
- [ ] Correlation ID tracking

### Integration Checks
- [ ] Routes include all 17 verticals
- [ ] Controllers use correct Models
- [ ] Services use correct Policies
- [ ] FormRequests validate all fields
- [ ] Resources format all data
- [ ] Migrations create all tables
- [ ] Factories generate all fields
- [ ] Seeders populate all data

---

## 📊 PROGRESS TRACKING

### COMPLETED ✅
- [x] Advertising Enums (8 шт)
- [x] Primary Models for Food, Hotel, Sports, Clinic
- [x] Audit checklist created

### IN PROGRESS 🔄
- [ ] Create Enums for all remaining 14 domains
- [ ] Verify Controllers use correct Models
- [ ] Add comprehensive Events

### TODO ⏳
- [ ] Production formatting pass
- [ ] Security audit
- [ ] Performance optimization
- [ ] API documentation (Scribe)
- [ ] Test suite validation

---

## 💡 PRODUCTION READINESS CRITERIA

✅ = Domain ready for production
⚠️ = Needs minor fixes
❌ = Needs major work

| Domain | Models | Enums | Events | Services | Controllers | Tests | Status |
|--------|--------|-------|--------|----------|-------------|-------|--------|
| Advertising | ✅ | ✅ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Beauty | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Clinic | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Communication | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Delivery | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Education | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Events | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Finances | ⚠️ | ❌ | ❓ | ✅ | ⚠️ | ❓ | ❌ |
| Food | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Geo | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Hotel | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Insurance | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Inventory | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| RealEstate | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Sports | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |
| Taxi | ✅ | ❌ | ❓ | ✅ | ✅ | ❓ | ⚠️ |

---

**Статус**: PHASE 1 начата - создание Enums
**Следующий шаг**: Создать Enums для всех 14 оставшихся доменов
**ETA**: ~2 часа на полный рефакторинг

