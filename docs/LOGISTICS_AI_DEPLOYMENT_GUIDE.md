# Logistics AI Deployment Guide

**Phase 1 Complete: Infrastructure Ready for Staging Deployment**

This guide covers deployment of the CatVRF Logistics AI Inference Service.

## Prerequisites

- Docker & Docker Compose installed
- ClickHouse server (or use provided container)
- Redis server (or use provided container)
- OpenAI API key (for LLM-powered agent)

## Quick Start (Staging)

### 1. Configure Environment

```bash
# Copy environment template
cp python-logistics/.env.example python-logistics/.env

# Edit .env with your settings
# Set AGENT_LLM_API_KEY=sk-...
# Set CLICKHOUSE_HOST, REDIS_HOST as needed
```

### 2. Initialize ClickHouse Feature Store

```bash
# Run feature store schema
clickhouse-client --host localhost --port 9000 --multiquery < database/clickhouse/feature_store.sql
```

### 3. Start Services

```bash
# Start ClickHouse (WSL)
wsl -d Ubuntu -- bash -c "sudo -u clickhouse /usr/bin/clickhouse server --config-file /etc/clickhouse-server/config.xml &"

# Start Redis
sudo systemctl start redis-server

# Start Laravel queue worker
php artisan queue:work --daemon &

# Health check
curl http://localhost:8000/health
```

### 4. Verify Integration

```bash
# Test Laravel → FastAPI integration
php artisan tinker

# In tinker:
use Modules\GeoLogistics\DTOs\LogisticsInferenceRequestDTO;
use Modules\GeoLogistics\Services\LogisticsInferenceService;

$service = app(LogisticsInferenceService::class);
$request = LogisticsInferenceRequestDTO::forETAPrediction(
    tenantId: 1,
    userId: 100,
    shipmentId: 'test-123',
    pickupLat: 55.7558,
    pickupLon: 37.6173,
    deliveryLat: 55.7558,
    deliveryLon: 37.7173,
    distanceKm: 5.5,
    vehicleType: 'courier_car'
);
$result = $service->predictETA($request);
dd($result);
```

## Architecture

```
Laravel App → Redis Queue → FastAPI Service → ClickHouse → ML Models
                                              ↓
                                        Logistics Agent (LLM)
```

## Configuration

### Laravel (.env)

```bash
LOGISTICS_INFERENCE_ENABLED=true
LOGISTICS_INFERENCE_QUEUE_KEY=logistics:inference:queue
LOGISTICS_AGENT_DEFAULT_MODE=supervised
```

### FastAPI (.env)

```bash
CLICKHOUSE_HOST=clickhouse
REDIS_HOST=redis
AGENT_LLM_API_KEY=sk-...
ENABLE_AGENT=true
```

## Monitoring

### Health Check

```bash
curl http://localhost:8000/health
```

Expected response:
```json
{
  "status": "healthy",
  "components": {
    "feature_store": "ok",
    "ml_inference": "ok",
    "redis_worker": "ok"
  }
}
```

### Filament Dashboard

Navigate to `/admin/logistics-agent-dashboard` in Filament admin panel.

### Prometheus Metrics

```bash
curl http://localhost:8000/metrics
```

## Testing

### Run Unit Tests

```bash
# Laravel tests
php artisan test --filter LogisticsInferenceServiceTest

# Feature tests
php artisan test --filter LogisticsInferenceIntegrationTest
```

### Run Integration Tests

```bash
# Start services
docker-compose -f docker-compose.staging.yml up -d

# Run tests
php artisan test
```

## Troubleshooting

### Redis Connection Failed

```bash
# Check Redis is running
redis-cli ping

# Check ClickHouse is running
clickhouse-client --query "SELECT 1"

# Check Laravel worker
ps aux | grep queue:work
```

### ClickHouse Connection Failed

```bash
# Check ClickHouse is running
curl http://localhost:8123/ping

# Check ClickHouse logs
sudo tail -f /var/log/clickhouse-server/clickhouse-server.log

# Test ClickHouse connection
clickhouse-client --query "SELECT 1"
```

### Agent Not Responding

```bash
# Check agent configuration
curl http://localhost:8000/health

# Check Redis worker is processing
ps aux | grep queue:work

# Verify LLM API key is set
grep AGENT_LLM_API_KEY .env
```

## Production Deployment

### 1. Build Production Image

```bash
cd python-logistics
pip install -r requirements.txt
python main.py &
```

### 2. Deploy to Kubernetes

```bash
kubectl apply -f k8s/logistics-inference-deployment.yaml
kubectl apply -f k8s/logistics-inference-service.yaml
```

### 4. Configure Laravel for Production

```bash
# .env
LOGISTICS_INFERENCE_ENABLED=true
LOGISTICS_INFERENCE_QUEUE_KEY=logistics:inference:queue:prod
LOGISTICS_AGENT_DEFAULT_MODE=autonomous  # Enable autonomous mode in production
```

## Next Steps

1. ✅ Deploy to staging
2. ⏳ Run end-to-end integration tests
3. ⏳ Set up A/B testing (10% traffic)
4. ⏳ Monitor metrics for 1 week
5. ⏳ Gradually increase traffic to 100%

## Support

- Documentation: `docs/AGENTIC_DYNAMIC_INTELLIGENCE_ROADMAP.md`
- Architecture: `docs/AGENTIC_DYNAMIC_INTELLIGENCE_GUIDE.md`
- Issues: GitHub Issues
