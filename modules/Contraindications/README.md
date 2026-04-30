# Contraindications & Allergies Layer

A shared, privacy-first domain for managing allergies, contraindications, and product composition across all CatVRF verticals (Beauty, Vet, Grooming, Fitness, Food, etc.).

## Architecture

This module follows Clean Architecture + DDD principles:

```
modules/Contraindications/
├── Domain/
│   ├── Entities/           # Domain entities (Allergy, Contraindication, etc.)
│   ├── Enums/              # Severity enums, Scope enum
│   ├── ValueObjects/       # Scope value object
│   ├── DTOs/               # CompatibilityResult, etc.
│   ├── Repositories/       # Repository interfaces
│   ├── Events/             # Domain events
│   └── Exceptions/         # Domain exceptions
├── Application/
│   ├── Services/           # ContraindicationService
│   └── Http/
│       └── Middleware/     # Privacy middleware
├── Infrastructure/
│   ├── Models/             # Eloquent models
│   └── Repositories/       # Repository implementations
├── Filament/
│   └── Resources/          # Admin panel resources
└── Presentation/
    └── Livewire/           # Frontend components
```

## Scope-Based Privacy

The core privacy feature is the **scope system**. Each allergy/contraindication has a `scopes` array that defines which verticals can see it.

### Available Scopes

- `cosmetology` - Beauty salons, cosmetics
- `food` - Food products, restaurant items
- `medical` - Veterinary services, medical procedures
- `grooming` - Pet grooming services
- `fitness` - Fitness programs, exercises

### Privacy Rules

1. **Empty scopes** = applicable to all verticals (use with caution)
2. **Specific scopes** = visible only in those verticals
3. **Cosmetology** only sees allergies with `cosmetology` scope
4. **Vet/Grooming** sees all relevant allergies (medical context)
5. **Food** only sees food-related allergies

### Examples

```php
// Gluten allergy - only relevant for food
$user->addAllergy(
    name: 'gluten',
    severity: AllergySeverity::Severe,
    reaction: 'anaphylaxis',
    scopes: ['food']
);

// Latex allergy - relevant for cosmetology and medical
$user->addAllergy(
    name: 'latex',
    severity: AllergySeverity::LifeThreatening,
    reaction: 'anaphylaxis',
    scopes: ['cosmetology', 'medical']
);

// Nickel allergy - relevant for cosmetology and grooming
$pet->addAllergy(
    name: 'nickel',
    severity: AllergySeverity::Moderate,
    reaction: 'skin irritation',
    scopes: ['cosmetology', 'grooming']
);
```

## Usage

### Adding the Trait to User/Pet Models

```php
use App\Traits\HasContraindications;

class User extends Model
{
    use HasContraindications;
}

class Pet extends Model
{
    use HasContraindications;
}
```

### Adding Allergies

```php
use Modules\Contraindications\Domain\Enums\AllergySeverity;
use Modules\Contraindications\Domain\ValueObjects\Scope;

$user->addAllergy(
    name: 'gluten',
    severity: AllergySeverity::Severe,
    reaction: 'anaphylaxis',
    scopes: ['food']
);

$pet->addAllergy(
    name: 'chicken',
    severity: AllergySeverity::Moderate,
    reaction: 'skin itching',
    scopes: ['food']
);
```

### Adding Contraindications

```php
use Modules\Contraindications\Domain\Enums\ContraindicationSeverity;

$user->addContraindication(
    name: 'pregnancy',
    severity: ContraindicationSeverity::High,
    description: 'First trimester',
    scopes: ['fitness', 'medical']
);

$pet->addContraindication(
    name: 'heart murmur',
    severity: ContraindicationSeverity::Critical,
    description: 'Grade 3 heart murmur',
    scopes: ['medical', 'grooming']
);
```

### Checking Compatibility

```php
use Modules\Contraindications\Application\Services\ContraindicationService;
use Modules\Contraindications\Domain\ValueObjects\Scope;

$service = app(ContraindicationService::class);

// Check if a service/product is compatible with user's allergies
$result = $service->checkCompatibilityForUser(
    composableType: 'Modules\\BeautyMasters\\Domain\\Entities\\Service',
    composableId: $service->id,
    userId: $user->id,
    scope: Scope::Cosmetology
);

if (!$result->isCompatible) {
    // Handle conflicts
    foreach ($result->allergyConflicts as $conflict) {
        Log::warning("Allergy conflict: {$conflict->name}");
    }
}

// Check for pets
$result = $service->checkCompatibilityForPet(
    composableType: 'Modules\\VetGrooming\\Domain\\Entities\\Service',
    composableId: $groomingService->id,
    petId: $pet->id,
    scope: Scope::Grooming
);
```

### Filtering Recommendations

```php
$recommendations = $recommendationService->getRecommendations($user);

$safeRecommendations = $service->filterSafeRecommendations(
    recommendations: $recommendations,
    userId: $user->id,
    petId: null,
    scope: Scope::Food
);
```

### Privacy Middleware

Apply the middleware to routes to automatically filter data by scope:

```php
// routes/api.php
Route::middleware(['contraindication:cosmetology'])
    ->group(function () {
        Route::apiResource('beauty-services', BeautyServiceController::class);
    });

Route::middleware(['contraindication:food'])
    ->group(function () {
        Route::apiResource('food-products', FoodProductController::class);
    });
```

## Product Composition

Define the composition of products/services to enable automatic allergen detection:

```php
use Modules\Contraindications\Infrastructure\Models\ProductCompositionModel;

ProductCompositionModel::create([
    'tenant_id' => tenant()->id,
    'composable_type' => 'Modules\\Restaurant\\Domain\\Entities\\Product',
    'composable_id' => $product->id,
    'ingredients' => [1, 2, 3], // ingredient IDs
    'calories_per_100g' => 250.50,
    'proteins' => 15.2,
    'fats' => 8.5,
    'carbs' => 30.1,
    'allergens' => ['gluten', 'dairy', 'eggs'],
]);
```

## Filament Resources

Admin panel resources for managing:
- **Allergies** - View/add/edit user and pet allergies
- **Contraindications** - View/add/edit user and pet contraindications
- **Product Compositions** - Manage product/service compositions and allergens

Navigate to: `/admin/health-safety/allergies`, `/admin/health-safety/contraindications`, `/admin/health-safety/product-compositions`

## Livewire Components

### AllergyManager

```blade
<livewire:contraindications.allergy-manager :user-id="$user->id" />
```

### ProductCompositionEditor

```blade
<livewire:contraindications.product-composition-editor
    :composable-type="'Modules\\Restaurant\\Domain\\Entities\\Product'"
    :composable-id="$product->id"
/>
```

## Testing

Run the test suite:

```bash
php artisan test --filter=ContraindicationServiceTest
```

Coverage target: ≥ 95%

## Tenant Isolation

All data is tenant-isolated via `tenant_id` foreign key. The service and repositories automatically filter by the current tenant.

## Compliance

This module is designed with medical compliance in mind:

- PII is never exposed outside the tenant
- Scope-based privacy ensures contextual visibility
- Audit logging is available for all allergy/contraindication operations
- Data is never sent to external LLMs without anonymization

## Integration with Verticals

### BeautyMasters
- Show only cosmetology-scoped allergies
- Check product compatibility before service booking
- Filter recommendations based on allergies

### VetGrooming
- Show all relevant allergies (medical context)
- Check grooming product compatibility
- Display allergy warnings in pet profiles

### Restaurant
- Show only food-scoped allergies
- Check menu item allergens
- Display calorie information

### Fitness
- Show fitness-scoped contraindications
- Check exercise compatibility
- Display health warnings

## Future Enhancements

- ML-based allergy risk prediction
- Cross-allergy detection
- Safe alternative recommendations
- Real-time allergen scanning from product labels
