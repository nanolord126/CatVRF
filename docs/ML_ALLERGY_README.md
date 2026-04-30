# ML Allergy Risk Prediction System

AI-powered allergy risk prediction for CatVRF with real-time assessment, cross-allergy detection, and safe alternative recommendations.

## Overview

The ML Allergy Risk Prediction System predicts the probability of allergic reactions to services/products based on:
- User/pet medical history
- Existing allergies and contraindications
- Breed/age demographics
- Product/service composition
- Cross-allergy relationships
- Regional and seasonal factors

## Architecture

```
┌─────────────────┐
│   Feature       │
│   Extractor     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   ML Model      │ (XGBoost / LightGBM)
│   (or Rule-Based│
│    Fallback)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Cross-Allergy │
│   Detector      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Alternative   │
│   Recommender   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Risk          │
│   Prediction    │
└─────────────────┘
```

## Components

### MLAllergyService

Main service orchestrating risk prediction:

```php
use App\Services\ML\MLAllergyService;
use Modules\Contraindications\Domain\ValueObjects\Scope;

$service = app(MLAllergyService::class);

$risk = $service->predictRisk($service, $user, Scope::Cosmetology);

// Check if booking should be blocked
if ($service->shouldBlock($entity, $subject, $scope)) {
    // Block booking
}

// Get warnings for UI
$warnings = $service->getWarnings($entity, $subject, $scope);
```

### AllergyRiskPredictor

ML model interface with rule-based fallback:

```php
use App\Services\ML\AllergyRiskPredictor;

$predictor = new AllergyRiskPredictor();
$features = $featureExtractor->extract($entity, $subject, $scope);

$prediction = $predictor->predict($features);
// Returns: ['probability' => 0.75, 'confidence' => 0.85]
```

### CrossAllergyDetector

Detects cross-allergy relationships:

```php
use App\Services\ML\CrossAllergyDetector;

$detector = new CrossAllergyDetector();
$crossRisks = $detector->detect($features);

// Example: chicken allergy → egg, turkey, duck risks
```

### SafeAlternativeRecommender

Recommends safe alternatives:

```php
use App\Services\ML\SafeAlternativeRecommender;

$recommender = new SafeAlternativeRecommender($contraindicationService);
$alternatives = $recommender->recommend($entity, $subject, $scope, 5);
```

### AllergyFeatureExtractor

Extracts features for ML prediction:

```php
use App\Services\ML\AllergyFeatureExtractor;

$extractor = new AllergyFeatureExtractor(
    $allergyRepository,
    $contraindicationRepository
);

$features = $extractor->extract($entity, $subject, $scope);
```

## Feature Engineering

### Subject Features

- **Demographics**: Age, gender, species, breed
- **Medical History**: Existing allergies, contraindications
- **Severity Profile**: History of severe reactions
- **Breed Risk Factor**: Breed-specific allergy predisposition

### Entity Features

- **Composition**: Ingredients, allergens
- **Category**: Service/product category
- **Tags**: Descriptive tags
- **Similarity**: Ingredient similarity to known allergens

### Contextual Features

- **Scope**: Vertical context (cosmetology, food, medical, etc.)
- **Season**: Seasonal allergy patterns
- **Region**: Regional allergen prevalence

## Risk Levels

| Level | Probability | Action |
|-------|-------------|--------|
| Low | 0-30% | Allow booking |
| Medium | 30-60% | Show warning |
| High | 60-80% | Strong warning + alternatives |
| Critical | 80-100% | Block booking |

## Integration

### Appointment Booking

```php
// In AppointmentController
$service = app(MLAllergyService::class);

if ($service->shouldBlock($serviceEntity, $pet, Scope::Grooming)) {
    return response()->json([
        'error' => 'Booking blocked due to allergy risk',
        'risk' => $service->explainRisk($serviceEntity, $pet, Scope::Grooming),
    ], 403);
}

// Get warnings for UI
$warnings = $service->getWarnings($serviceEntity, $pet, Scope::Grooming);
```

### Filament Dashboard

```php
// In Pet profile page
<livewire:ml.allergy-risk-panel 
    :entity="$service"
    :subject="$pet"
    scope="grooming"
/>
```

### API Endpoint

```php
// routes/api.php
Route::post('/api/allergy-risk', function (Request $request) {
    $service = app(MLAllergyService::class);
    
    $risk = $service->predictRisk(
        $request->entity,
        $request->subject,
        Scope::from($request->scope)
    );
    
    return response()->json($risk->toArray());
});
```

## Model Training

### Data Collection

Collect anonymized data from:
- Historical appointment outcomes
- Allergy reaction reports
- Cross-allergy patterns
- Breed-specific prevalence

### Training Pipeline

```bash
# Trigger model training
php artisan ml:train-allergy-model

# Or via job
dispatch(new \App\Jobs\TrainAllergyModel($trainingData));
```

### Model Versioning

Models are versioned and can be rolled back:
- Production model: `v1.2.3`
- Staging model: `v1.2.4-rc1`
- Training data hash for reproducibility

## Privacy & Compliance

### Data Anonymization

- PII removed before ML training
- Tenant data aggregated
- No raw medical data in external ML

### Consent

- User opt-in for ML predictions
- Transparent about model limitations
- Right to opt-out

### Audit Logging

All predictions logged for:
- Compliance verification
- Model performance monitoring
- Bias detection

## Performance

### Target Metrics

- **Prediction Time**: < 150ms
- **Accuracy**: > 85% on validation
- **Precision**: > 90% for critical cases
- **Recall**: > 80% for high-risk cases

### Monitoring

Track:
- Prediction latency
- Model accuracy drift
- False positive/negative rates
- Feature distribution shifts

## Cross-Allergy Database

Known cross-allergy relationships:

| Allergen | Cross-Allergens | Confidence |
|----------|----------------|------------|
| Chicken | Egg, Turkey, Duck, Feathers | 0.75 |
| Beef | Lamb, Pork, Milk | 0.70 |
| Dairy | Casein, Whey, Lactose | 0.85 |
| Wheat | Gluten, Barley, Rye, Oats | 0.90 |
| Latex | Banana, Avocado, Kiwi | 0.80 |

## Testing

```bash
# Run ML allergy tests
php artisan test --filter=MLAllergyServiceTest
```

## Troubleshooting

### Model Unavailable

If ML model is down, system falls back to rule-based prediction:
- Check model service health
- Review fallback logs
- Monitor prediction quality

### High False Positive Rate

- Review feature weights
- Adjust risk thresholds
- Retrain with more data
- Check for data drift

### Slow Predictions

- Cache feature extraction
- Optimize model inference
- Use batch predictions
- Consider edge deployment

## Future Enhancements

- [ ] Real-time learning from feedback
- [ ] Image-based allergen detection
- [ ] Genetic predisposition analysis
- [ ] Environmental allergen integration
- [ ] Mobile app notifications
- [ ] Integration with wearable devices

## Configuration

Environment variables:

```bash
# ML Model Endpoint
ML_ALLERGY_MODEL_ENDPOINT=http://localhost:8000/predict/allergy-risk
ML_ALLERGY_MODEL_TIMEOUT=2000

# Feature Extraction
ML_ENABLE_BREED_RISK=true
ML_ENABLE_REGIONAL_ANALYSIS=true

# Risk Thresholds
ML_RISK_CRITICAL_THRESHOLD=0.8
ML_RISK_HIGH_THRESHOLD=0.6
ML_RISK_MEDIUM_THRESHOLD=0.3

# Training
ML_TRAINING_SCHEDULE=weekly
ML_MIN_TRAINING_SAMPLES=10000
```

## References

- [FSTEC BDU Threat Model](../../compliance/FSTEC_BDU_INTEGRATION_README.md)
- [152-ФZ Compliance](../../compliance/152-fz/)
- [Contraindications Layer](../../modules/Contraindications/README.md)
