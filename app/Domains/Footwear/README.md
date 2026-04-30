# Footwear Domain — CatVRF Vertical

## Overview

Footwear domain implements the shoes vertical of the CatVRF marketplace with full support for:
- Material composition with allergen tracking (healthcare compliance 152-ФЗ, ФЗ-323)
- Size variants with width (narrow, regular, wide, extra wide)
- Foot length-based sizing (cm as primary, EU/US/UK conversions)
- Shoe last type support
- ML-powered size recommendations
- Context-aware allergy checking
- 360° view support

## Architecture

### Domain Structure

```
app/Domains/Footwear/
├── Models/
│   ├── FootwearProduct.php          # Main footwear product model
│   ├── FootwearVariant.php          # Size/width/color variants
│   ├── FootSizeChart.php            # Foot size conversion charts
│   ├── ShoeLastType.php             # Shoe last (колодка) types
│   ├── FootwearStore.php            # Footwear store
│   └── FootwearReview.php           # Product reviews
├── Services/
│   ├── FootwearAllergyService.php   # Context-aware allergy checking
│   └── FootwearSizeService.php      # Footwear-specific size logic
├── Filament/
│   └── Resources/
│       └── FootwearProductResource.php  # Admin resource
├── Livewire/
│   └── FootwearSizeSelector.php     # Size selection with width
└── README.md                        # This file
```

### Shared Layers

The Footwear domain integrates with shared layers:

- **Material** (`app/Shared/Domain/Entities/Material.php`) — Material definitions with allergen information
- **MaterialAllergy** (`app/Shared/Domain/Entities/MaterialAllergy.php`) — Allergy mappings
- **SizeChart** (`app/Shared/Domain/Entities/SizeChart.php`) — Size conversion charts
- **SizeRecommendationService** (`app/Shared/Application/Services/SizeRecommendationService.php`) — ML-powered size recommendations

## Key Features

### 1. Material Allergy Checking

```php
use App\Domains\Footwear\Services\FootwearAllergyService;

$service = app(FootwearAllergyService::class);

// Check product safety
$result = $service->checkProductSafety(
    ['leather', 'rubber'],
    ['latex', 'contact_dermatitis']
);

// Result:
// [
//     'is_safe' => false,
//     'conflicts' => [...],
//     'severity' => 'severe'
// ]
```

### 2. Size Recommendations (Foot Length + Width)

```php
use App\Domains\Footwear\Services\FootwearSizeService;

$service = app(FootwearSizeService::class);

$recommendation = $service->recommendSize(
    footLengthCm: 26.5,
    footWidthCm: 10.0,
    gender: 'male',
    brand: 'brand-name'  // Optional for brand-specific charts
);

// Result:
// [
//     'size_eu' => 42,
//     'size_us' => 9,
//     'size_uk' => 8,
//     'recommended_width' => 'regular',
//     'confidence' => 0.92,
//     'foot_length_fit' => 'perfect_fit',
//     'foot_width_fit' => 'good_width'
// ]
```

### 3. Variant Management

```php
use App\Domains\Footwear\Models\FootwearProduct;
use App\Domains\Footwear\Models\FootwearVariant;

$product = FootwearProduct::create([
    'name' => 'Классические кроссовки',
    'material' => 'кожа',
    'sole_type' => 'rubber',
    'season' => 'all_season',
    'purpose' => 'casual',
    'gender' => 'unisex',
    // ...
]);

$variant = FootwearVariant::create([
    'footwear_product_id' => $product->id,
    'color' => 'Черный',
    'color_code' => '#000000',
    'size_eu' => '42',
    'size_us' => '9',
    'size_uk' => '8',
    'size_cm' => 26.5,
    'width' => 'regular',
    'current_stock' => 50,
    // ...
]);
```

## Size Systems

Footwear domain supports multiple size systems:

- **EU** — European sizes (primary for Russia)
- **US** — American sizes
- **UK** — British sizes
- **CM** — Foot length in centimeters (most accurate)

### Width Options

- **narrow** — Узкая
- **regular** — Обычная
- **wide** — Широкая
- **extra_wide** — Очень широкая

## Livewire Components

### FootwearSizeSelector

Displays size grid with width options and ML recommendations:

```blade
<livewire:footwear.size-selector 
    :productId="$product->id" 
    gender="male" 
    brand="brand-name"
/>
```

Features:
- Foot length + width input for size calculation
- Size grid grouped by EU size
- Width selection per size
- Color variants
- Stock availability display

## Filament Admin

Access via Filament admin panel under **Footwear → Обувь**.

Features:
- Product management with material and sole type
- Variant management with size/width/color grid
- Stock tracking per variant
- Image upload with 360° view support
- Required angles: 360°, sole, top view
- SEO fields
- Season and purpose filters
- Warranty and return policy fields

## Foot Size Chart

The `FootSizeChart` model provides detailed size recommendations:

```php
use App\Domains\Footwear\Models\FootSizeChart;

// Get recommendation for specific measurements
$recommendation = FootSizeChart::getRecommendedSizeForFootMeasurements(
    footLengthCm: 26.5,
    footWidthCm: 10.0,
    gender: 'male',
    brand: 'brand-name'
);
```

Chart includes:
- Size conversions (EU/US/UK/JP)
- Foot length ranges
- Foot width recommendations
- Heel height
- Toe box width
- Arch support level

## Healthcare Compliance

All allergy-related features comply with:
- **152-ФЗ** — Personal data protection
- **ФЗ-323** — Healthcare regulations

Key compliance features:
- Context-aware allergy filtering (no food allergies for footwear)
- Audit logging for allergy conflicts
- PII anonymization before external AI calls
- Material allergen database with medical references

## Performance

- Caching with Cache tags for invalidation
- Eager loading for variants
- Redis for real-time stock tracking
- CDN for image delivery
- Batch loading support
- Size chart caching (7-day TTL)

## Testing

Run tests:

```bash
php artisan test tests/Domains/Footwear/
```

Key test scenarios:
- Allergy checking accuracy
- Size recommendation precision (length + width)
- Variant stock management
- Size conversion between systems
- Width availability per size

## Integration Points

- **Inventory FIFO** — Stock tracking with FIFO batch management
- **ML Personalization** — Size recommendations based on purchase history
- **Media + CDN** — Image optimization and 360° view delivery
- **Search** — Material and size-based search filters
- **Payments** — Integration with cart and checkout

## Shoe Last Types

The domain includes support for different shoe last (колодка) types:

- Standard
- Wide last
- Narrow last
- Athletic last
- Comfort last

This affects the fit and should be considered in size recommendations.

## Future Enhancements

- [ ] Virtual try-on with AR
- [ ] 3D foot scanning integration
- [ ] Gait analysis for running shoes
- [ ] Size feedback loop for ML training
- [ ] Brand-specific size database expansion
- [ ] Custom insole recommendations

## Support

For issues or questions, refer to the main CatVRF documentation or contact the development team.
