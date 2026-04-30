# ML Personalization & Recommendation System

AI-powered personalized recommendations for CatVRF using hybrid recommendation architecture with Two-Tower neural networks, XGBoost reranking, and privacy-first feature engineering.

## Overview

The personalization system provides tailored recommendations for users and pets across all verticals (Beauty, Grooming, Food, Fitness) by analyzing:
- User/pet demographics and behavior
- Historical preferences and booking patterns
- Content similarity and collaborative filtering
- Seasonal and contextual factors
- Allergies and contraindications (with privacy)

## Architecture

```
┌─────────────────┐
│   Feature Store │ (User/Pet/Item features)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Candidate      │ (Two-Tower Neural Network)
│  Generator      │ (Top-K retrieval)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Reranker      │ (XGBoost + Context)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Contraindication│ (Privacy filter)
│     Filter      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Explainer     │ (SHAP-like explanations)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Recommendations│
└─────────────────┘
```

## Components

### PersonalizationService

Main orchestrator for recommendations:

```php
use App\Services\Personalization\PersonalizationService;

$service = app(PersonalizationService::class);

// For users
$recommendations = $service->recommendForClient($user, 'beauty', 8);

// For pets
$recommendations = $service->recommendForPet($pet, 'grooming', 6);

// Similar items
$similar = $service->getSimilarItems($item, 'beauty', 5);

// Trending items
$trending = $service->getTrending('fitness', 10);

// Home page recommendations
$homeRecs = $service->getHomePageRecommendations($user, ['beauty', 'grooming', 'food']);
```

### FeatureStore

Extracts and caches features for ML models:

```php
use App\Services\Personalization\FeatureStore;

$store = new FeatureStore();

// User features
$userFeatures = $store->extractForUser($user, 'beauty');

// Pet features
$petFeatures = $store->extractForPet($pet, 'grooming');

// Item features
$itemFeatures = $store->extractForItem($service);

// Update features based on action
$store->updateUserFeatures($userId, 'click');
```

**Feature Categories:**

- **Demographic**: Age, gender, location, membership tier
- **Behavioral**: Visit frequency, spending patterns, preferred times
- **Historical**: Total visits, ratings, cancellation rate, loyalty points
- **Preferences**: Favorite categories, tags, price sensitivity
- **Medical**: Allergy embeddings (anonymized), chronic conditions
- **Content**: Category, price, tags, composition embeddings
- **Collaborative**: Popularity, ratings, conversion rates
- **Contextual**: Seasonal popularity, time-of-day patterns

### CandidateGenerator

Two-Tower architecture for candidate generation:

```php
use App\Services\Personalization\CandidateGenerator;

$generator = new CandidateGenerator();

// Get top K candidates
$candidates = $generator->getTopK($userFeatures, 'beauty', 24);

// Get similar items
$similar = $generator->getSimilarItems($itemFeatures, 'beauty', 10);
```

**Embedding Strategy:**

- User embedding from demographic + behavioral + preference features
- Item embedding from content + collaborative + contextual features
- Cosine similarity for matching
- ML model fallback to rule-based embeddings

### Reranker

XGBoost-based reranking with contextual factors:

```php
use App\Services\Personalization\Reranker;

$reranker = new Reranker($featureStore);

$ranked = $reranker->rank($candidates, $userFeatures, 'beauty');
```

**Reranking Factors:**

- Contextual score (time of day, season)
- Personalization score (price fit, category preference)
- Business rules (promotions, new items, availability, ratings)
- ML model predictions (when available)

### RecommendationExplainer

SHAP-like explanations for recommendations:

```php
use App\Services\Personalization\RecommendationExplainer;

$explainer = new RecommendationExplainer();

$explanation = $explainer->explain($item, $subject);
// Returns: primary_reason, factors, confidence

$detailed = $explainer->explainDetailed($recommendation, $subject);
// Returns: detailed factors with contribution values
```

**Explanation Factors:**

- Category preference match
- Price range fit
- Historical similarity
- Popularity boost
- High rating
- Seasonal relevance

## Integration

### API Endpoint

```php
// routes/api.php
Route::get('/api/recommendations/{vertical}', function (Request $request) {
    $service = app(PersonalizationService::class);
    
    $subject = $request->has('pet_id') 
        ? Pet::find($request->pet_id)
        : auth()->user();
    
    $recommendations = $subject instanceof Pet
        ? $service->recommendForPet($subject, $request->vertical, $request->limit ?? 8)
        : $service->recommendForClient($subject, $request->vertical, $request->limit ?? 8);
    
    return response()->json($recommendations->toArray());
});

Route::post('/api/recommendations/{itemId}/feedback', function (Request $request) {
    $service = app(PersonalizationService::class);
    
    $service->recordFeedback(
        auth()->id(),
        $request->itemId,
        $request->action // 'click', 'book', 'purchase', 'dismiss'
    );
    
    return response()->json(['success' => true]);
});
```

### Livewire Component

```blade
<livewire:personalization.personalized-recommendations-panel 
    :subject="$user"
    vertical="beauty"
    :limit="8"
/>
```

### Filament Widget

```php
// In Filament dashboard
use App\Services\Personalization\Presentation\Livewire\PersonalizedRecommendationsPanel;

protected function getWidgets(): array
{
    return [
        PersonalizedRecommendationsPanel::class,
    ];
}
```

## Privacy & Compliance

### Data Anonymization

- Allergy embeddings use MD5 hashing (no raw data)
- Medical data never sent to external ML
- Tenant data aggregated for training
- PII removed from feature vectors

### Consent

- User opt-in for personalization
- Transparent about data usage
- Right to opt-out
- Clear explanation of recommendations

### Audit Logging

All recommendations logged for:
- Compliance verification
- Model performance monitoring
- Bias detection
- Feedback loop analysis

## Performance

### Target Metrics

- **Recommendation Time**: < 200ms
- **Cache Hit Rate**: > 80%
- **Click-Through Rate**: > 15%
- **Conversion Rate**: > 5%
- **Coverage**: > 95%

### Caching Strategy

- User features: 6 hours
- Pet features: 6 hours
- Item features: 6 hours
- Candidates: 1 hour
- Trending: 1 hour

### Monitoring

Track:
- Recommendation latency
- Cache hit/miss rates
- CTR per vertical
- Conversion rates
- Feature distribution drift

## Model Training

### Data Collection

Anonymized data from:
- Booking history
- Click patterns
- Purchase behavior
- Rating patterns
- Seasonal trends

### Training Pipeline

```bash
# Train Two-Tower model
python train_two_tower.py --vertical beauty --epochs 50

# Train XGBoost reranker
python train_reranker.py --vertical beauty --iterations 1000

# Train embedding models
python train_embeddings.py --mode item
python train_embeddings.py --mode user
```

### Model Versioning

- Two-Tower: `v2.1.0`
- XGBoost: `v1.5.0`
- Embeddings: `v3.0.0`

### Online Learning

Real-time feedback loop:
- User actions update features
- Daily incremental training
- Weekly full retraining
- A/B testing for model updates

## Configuration

Environment variables:

```bash
# Personalization
PERSONALIZATION_ENABLED=true
PERSONALIZATION_CACHE_TTL=3600

# ML Endpoints
PERSONALIZATION_EMBEDDING_ENDPOINT=http://localhost:8001/embed
PERSONALIZATION_RANKING_ENDPOINT=http://localhost:8002/rank
PERSONALIZATION_TIMEOUT=2000

# Feature Store
FEATURE_STORE_REDIS_HOST=127.0.0.1
FEATURE_STORE_REDIS_PORT=6379
FEATURE_STORE_EMBEDDING_DIM=128

# Candidate Generation
CANDIDATE_MULTIPLIER=3
CANDIDATE_CACHE_TTL=3600

# Reranking
RERANK_MODEL_PATH=/models/reranker/xgboost.json
RERANK_USE_ML=true
RERANK_FALLBACK=true
```

## Testing

```bash
# Run personalization tests
php artisan test --filter=PersonalizationServiceTest

# Test feature extraction
php artisan test --filter=FeatureStoreTest

# Test candidate generation
php artisan test --filter=CandidateGeneratorTest
```

## Troubleshooting

### Low Recommendation Quality

- Check feature extraction
- Review model performance
- Verify training data quality
- Check for data drift

### Slow Recommendations

- Verify cache configuration
- Check Redis connectivity
- Optimize embedding computation
- Consider edge deployment

### Poor Diversity

- Adjust diversity filter parameters
- Check category distribution
- Review similarity thresholds
- Increase candidate pool

## Future Enhancements

- [ ] Real-time bandit algorithms
- [ ] Deep learning explainer (Integrated Gradients)
- [ ] Multi-objective optimization
- [ ] Cross-vertical recommendations
- [ ] Cold-start handling for new users
- [ ] Graph neural networks for social recommendations
- [ ] Reinforcement learning for long-term engagement

## References

- [Contraindications Layer](../../modules/Contraindications/README.md)
- [ML Allergy Risk Prediction](../ML_ALLERGY_README.md)
- [FSTEC BDU Threat Model](../../compliance/FSTEC_BDU_INTEGRATION_README.md)
