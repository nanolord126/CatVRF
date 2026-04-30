# CatVRF Recommendation & Personalization Engine

Production-ready recommendation system for CatVRF marketplace with ML-based personalization, fairness constraints, and A/B testing.

## Architecture

```
modules/Recommendation/
├── Domain/
│   ├── Entities/          RecommendationItem, ModelDriftReport, SellerRecommendation
│   ├── Enums/             RecommendationSource, RecommendationScenario, FeatureStoreEnums
│   ├── ValueObjects/      RecommendationScore, FeatureVector, FairnessConfig
│   ├── DTOs/              RecommendationRequestDTO, RecommendationResponseDTO
│   ├── Events/            RecommendationServed, ModelDriftDetected, ImpressionTracked
│   ├── Interfaces/        MLInferenceInterface, FairnessEvaluatorInterface
│   └── Repositories/      RecommendationRepositoryInterface
├── Application/
│   ├── Services/          RecommendationOrchestrator, RecommendationFacade, 
│   │                      RuleBasedFallbackService, FairnessEvaluatorService,
│   │                      SellerRecommendationService, ImpressionTrackingService
│   └── Examples/          RecommendationABTestExamples
├── Infrastructure/
│   ├── ClickHouse/        ClickHouseRecommendationRepository
│   ├── ML/                MLInferenceClient
│   └── Redis/             RedisFeatureCache
├── Presentation/
│   ├── Http/Controllers/  RecommendationController, SellerRecommendationController
│   └── Routes/            api.php
└── python/
    └── main.py            FastAPI ML inference service
```

## Features

- **Candidate Generation**: Two-tower model with ANN search
- **Ranking**: Multi-objective optimization (GMV + diversity + fairness)
- **Scenarios**: Home feed, Product detail, Cart, Search, Seller page, Email digest
- **Fairness**: Seller exposure limits, diversity constraints, exploration budget
- **A/B Testing**: Multi-armed bandit, model version canary, scenario optimization
- **Fallback**: Rule-based recommendations when ML fails
- **Observability**: Full metrics (CTR, CVR, GMV lift), model drift detection

## Installation

### 1. Publish Config & Migrations

```bash
php artisan vendor:publish --tag=recommendation-config
php artisan vendor:publish --tag=recommendation-migrations
```

### 2. Run ClickHouse Migrations

```bash
clickhouse-client --host=127.0.0.1 --port=9000 --user=default --password= \
  --queries-file database/clickhouse/recommendation/feature_store.sql
```

### 3. Configure Environment Variables

```env
RECOMMENDATION_ML_SERVICE_URL=http://localhost:8000
RECOMMENDATION_ML_SERVICE_API_KEY=your-api-key
RECOMMENDATION_CACHE_TTL=300
RECOMMENDATION_MIN_SELLER_EXPOSURE=0.02
RECOMMENDATION_MAX_DOMINANCE_SHARE=0.30
```

### 4. Start ML Service

```bash
cd modules/Recommendation/python
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
```

Or with Docker:

```bash
docker-compose -f docker-compose.recommendation.yml up -d
```

## API Usage

### Get Recommendations

```php
use Modules\Recommendation\Application\Services\RecommendationFacade;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;

// Home feed
$response = RecommendationFacade::forUser($userId)
    ->getHomeFeed($tenantId, limit: 20, context: []);

// Product detail
$response = RecommendationFacade::forUser($userId)
    ->getProductRecommendations($tenantId, $itemId: 12345);

// Search
$response = RecommendationFacade::forUser($userId)
    ->getPersonalizedSearch($tenantId, query: 'laptop');

// Seller page
$response = RecommendationFacade::forUser($userId)
    ->getSellerPageRecommendations($tenantId, $sellerId: 789);

// Cart
$response = RecommendationFacade::forUser($userId)
    ->getCartRecommendations($tenantId);
```

### Track Events

```php
use Modules\Recommendation\Application\Services\RecommendationFacade;

// Track impression
RecommendationFacade::forUser($userId)
    ->trackImpression($tenantId, $itemId: 12345, position: 0, 
                     scenario: 'home_feed', source: 'two_tower', 
                     correlationId: $correlationId);

// Track click
RecommendationFacade::forUser($userId)
    ->trackClick($tenantId, $itemId: 12345, scenario: 'home_feed', 
                correlationId: $correlationId);

// Track conversion
RecommendationFacade::forUser($userId)
    ->trackConversion($tenantId, $itemId: 12345, revenue: 99.99, 
                      scenario: 'home_feed', correlationId: $correlationId);
```

### Seller Recommendations

```php
use Modules\Recommendation\Application\Services\RecommendationFacade;

$recommendations = RecommendationFacade::forSeller($sellerId)
    ->suggestProductsToPromote($tenantId, limit: 12);

$metrics = RecommendationFacade::forSeller($sellerId)
    ->getSellerPerformanceMetrics($tenantId);
```

### Direct HTTP API

```bash
# Get recommendations
POST /api/recommendations
{
  "tenant_id": 1,
  "user_id": 123,
  "scenario": "home_feed",
  "limit": 20,
  "context": {},
  "correlation_id": "uuid"
}

# Track impression
POST /api/recommendations/track/impression
{
  "tenant_id": 1,
  "user_id": 123,
  "item_id": 456,
  "position": 0,
  "scenario": "home_feed",
  "source": "two_tower",
  "correlation_id": "uuid"
}

# Get performance metrics
GET /api/recommendations/metrics?tenant_id=1&scenario=home_feed&hours=24
```

## Configuration

### Fairness Settings

```php
// config/recommendation.php
'fairness' => [
    'min_seller_exposure' => 0.02,      // 2% minimum exposure per seller
    'max_dominance_share' => 0.30,     // 30% max share for top seller
    'diversity_min_gap' => 0.05,       // 5% minimum diversity gap
    'exploration_budget' => 0.10,      // 10% exploration budget
    'min_sellers_in_feed' => 5,        // Minimum 5 sellers in feed
],
```

### Scenario Limits

```php
'scenarios' => [
    'home_feed' => ['default_limit' => 20, 'cache_ttl' => 300],
    'product_detail' => ['default_limit' => 12, 'cache_ttl' => 300],
    'cart' => ['default_limit' => 6, 'cache_ttl' => 60],
    // ...
],
```

## A/B Testing Examples

```php
use Modules\Recommendation\Application\Examples\RecommendationABTestExamples;

$abTest = new RecommendationABTestExamples();

// Test two-tower vs collaborative filtering
$abTest->testTwoTowerVsCollaborative();

// Test bandit exploration
$abTest->testBanditExploration();

// Test fairness constraints
$abTest->testFairnessConstraints();

// Get test metrics
$metrics = $abTest->calculateTestMetrics('experiment_id');
```

## Metrics

### Offline Metrics
- **NDCG@K**: Normalized Discounted Cumulative Gain
- **Precision@K**: Precision at K
- **Recall@K**: Recall at K
- **AUC**: Area Under ROC Curve

### Online Metrics
- **CTR**: Click-Through Rate
- **CVR**: Conversion Rate
- **GMV Lift**: Revenue uplift vs control
- **Revenue per User**: Average revenue per user

### Model Drift
- **PSI**: Population Stability Index
- **Accuracy Drop**: Accuracy degradation
- **NDCG Drop**: Ranking quality degradation

## Deployment

### Kubernetes (Helm)

```bash
helm install recommendation-ml ./helm/recommendation/ml-service.yaml \
  --namespace catvrf
```

### Docker Compose

```bash
docker-compose -f docker-compose.recommendation.yml up -d
```

## Integration with Other Verticals

- **CLV**: Stratification for A/B tests, high-value user personalization
- **RFM**: Recency/Frequency/Monetary features for user embeddings
- **A/B Testing**: Experiment assignment, metrics tracking
- **BigData**: ClickHouse feature store, Kafka event streaming
- **Security**: PII anonymization, GDPR compliance

## Performance

- **Latency**: < 100ms P95 for recommendation generation
- **Throughput**: 10k+ RPS with horizontal scaling
- **Cache Hit Rate**: 60-80% with Redis caching
- **ML Service**: 4 workers, auto-scaling with HPA

## Troubleshooting

### ML Service Unreachable

```bash
# Check health
curl http://localhost:8000/health

# Check logs
docker logs catvrf-recommendation-ml
```

### Empty Recommendations

```bash
# Check cache
php artisan cache:clear --tag=recommendations

# Check ClickHouse tables
clickhouse-client --query "SELECT COUNT() FROM feature_store_user"
```

### High Latency

- Enable Redis cache warming
- Increase ML service workers
- Check ClickHouse query performance

## License

Proprietary - CatVRF Internal Use Only
