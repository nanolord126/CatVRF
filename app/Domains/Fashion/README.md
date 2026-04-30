# Fashion Domain — CatVRF Vertical

## Overview

Fashion domain implements the clothing vertical of the CatVRF marketplace with full support for:
- Material composition with allergen tracking (healthcare compliance 152-ФЗ, ФЗ-323)
- Size variants with fit types (slim, regular, relaxed, oversized)
- Season and care instructions
- Look builder support
- ML-powered size recommendations
- Context-aware allergy checking

## Architecture

### Domain Structure

```
app/Domains/Fashion/
├── Models/
│   ├── ClothingProduct.php          # Main clothing product model
│   ├── ClothingVariant.php          # Size/color variants
│   ├── FashionProduct.php           # Base fashion product
│   ├── FashionStore.php             # Fashion store
│   └── ...                          # Other existing models
├── Services/
│   ├── FashionAllergyService.php    # Context-aware allergy checking
│   └── ...                          # Other existing services
├── Filament/
│   └── Resources/
│       └── ClothingProductResource.php  # Admin resource
├── Livewire/
│   ├── SizeSelector.php             # Size selection with ML recommendations
│   ├── MaterialAllergyChecker.php   # Material allergy checking UI
│   └── ProductGallery.php           # Image gallery with 360° support
└── README.md                        # This file
```

### Shared Layers

The Fashion domain integrates with shared layers:

- **Material** (`app/Shared/Domain/Entities/Material.php`) — Material definitions with allergen information
- **MaterialAllergy** (`app/Shared/Domain/Entities/MaterialAllergy.php`) — Allergy mappings
- **SizeChart** (`app/Shared/Domain/Entities/SizeChart.php`) — Size conversion charts
- **SizeRecommendationService** (`app/Shared/Application/Services/SizeRecommendationService.php`) — ML-powered size recommendations

## Key Features

### 1. Material Allergy Checking

```php
use App\Domains\Fashion\Services\FashionAllergyService;

$service = app(FashionAllergyService::class);

// Check product safety
$result = $service->checkProductSafety(
    ['cotton', 'wool'],
    ['contact_dermatitis', 'textile']
);

// Result:
// [
//     'is_safe' => false,
//     'conflicts' => [...],
//     'severity' => 'moderate'
// ]
```

### 2. Size Recommendations

```php
use App\Shared\Application\Services\SizeRecommendationService;

$service = app(SizeRecommendationService::class);

$recommendation = $service->recommendFashionSize(
    [
        'chest' => 98,
        'waist' => 83,
        'hips' => 98,
    ],
    'male',
    'brand-name'  // Optional for brand-specific charts
);
```

### 3. Variant Management

```php
use App\Domains\Fashion\Models\ClothingProduct;
use App\Domains\Fashion\Models\ClothingVariant;

$product = ClothingProduct::create([
    'name' => 'Классическая футболка',
    'materials' => ['cotton 100%'],
    'season' => 'all_season',
    'fit_type' => 'regular',
    'gender' => 'unisex',
    // ...
]);

$variant = ClothingVariant::create([
    'clothing_product_id' => $product->id,
    'color' => 'Белый',
    'size_eu' => '50',
    'size_us' => '40',
    'size_uk' => '39',
    'size_international' => 'M',
    'fit_type' => 'regular',
    'current_stock' => 100,
    // ...
]);
```

## Livewire Components

### SizeSelector

Displays size grid with ML recommendations:

```blade
<livewire:fashion.size-selector 
    :productId="$product->id" 
    gender="male" 
    brand="brand-name"
/>
```

### MaterialAllergyChecker

Checks material allergies for user:

```blade
<livewire:fashion.material-allergy-checker 
    :materials="$product->materials"
/>
```

### ProductGallery

Image gallery with required angles (front, back, side, detail, on_model):

```blade
<livewire:fashion.product-gallery :images="$product->images"/>
```

## Filament Admin

Access via Filament admin panel under **Fashion → Одежда**.

Features:
- Product management with material tags
- Variant management with size/color grid
- Stock tracking per variant
- Image upload with CDN support
- SEO fields
- Season and fit type filters

## Healthcare Compliance

All allergy-related features comply with:
- **152-ФЗ** — Personal data protection
- **ФЗ-323** — Healthcare regulations

Key compliance features:
- Context-aware allergy filtering (no food allergies for clothing)
- Audit logging for allergy conflicts
- PII anonymization before external AI calls
- Material allergen database with medical references

## Performance

- Caching with Cache tags for invalidation
- Eager loading for variants
- Redis for real-time stock tracking
- CDN for image delivery
- Batch loading support

## Testing

Run tests:

```bash
php artisan test tests/Domains/Fashion/
```

Key test scenarios:
- Allergy checking accuracy
- Size recommendation precision
- Variant stock management
- Context-aware allergen filtering

## Integration Points

- **Inventory FIFO** — Stock tracking with FIFO batch management
- **ML Personalization** — Size recommendations based on purchase history
- **Media + CDN** — Image optimization and delivery
- **Search** — Material and size-based search filters
- **Payments** — Integration with cart and checkout

## Future Enhancements

- [ ] Virtual try-on integration
- [ ] AR preview for products
- [ ] Outfit builder (look recommendations)
- [ ] Size feedback loop for ML training
- [ ] Sustainable material scoring
- [ ] Brand-specific size database expansion

## Support

For issues or questions, refer to the main CatVRF documentation or contact the development team.
