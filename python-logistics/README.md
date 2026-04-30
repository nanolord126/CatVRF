# CatVRF Logistics Inference Service

Agentic + Dynamic Intelligence for Logistics - Courier Assignment, PVZ Scoring, ETA Prediction, VRP Optimization

## Version

2.0.0 (April 18, 2026)

## Quick Start

### Docker Deployment

```bash
# Build and start services
docker-compose up -d

# Check health
curl http://localhost:8000/health

# View logs
docker-compose logs -f logistics-inference
```

### Manual Deployment

```bash
# Install dependencies
pip install -r requirements.txt

# Configure environment
cp .env.example .env
# Edit .env with your settings

# Run service
uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
```

## API Documentation

When running in debug mode (`DEBUG=true`), Swagger UI is available at:
- http://localhost:8000/docs
- http://localhost:8000/redoc

## Endpoints

### Courier Assignment
- `POST /v1/courier/assign` - Assign best courier to shipment
- `POST /v1/courier/assign/batch` - Batch assignment for multiple shipments

### PVZ Scoring
- `POST /v1/pvz/score` - Score and rank PVZs for user

### ETA Prediction
- `POST /v1/eta/predict` - Predict estimated time of arrival
- `POST /v1/eta/predict/batch` - Batch ETA prediction

### VRP Optimization
- `POST /v1/vrp/optimize` - Optimize vehicle routing

### Agentic AI
- `POST /v1/agent/run-cycle` - Run full agent cycle (observe → decide → act)
- `GET /v1/agent/status` - Get agent status
- `POST /v1/agent/observe` - Observe current system state
- `POST /v1/agent/configure` - Configure agent mode (autonomous/supervised)

### Health & Metrics
- `GET /health` - Health check
- `GET /metrics` - Prometheus metrics
- `GET /` - Service info

## Project Structure

```
python-logistics/
├── src/
│   ├── core/           # Configuration, logging, middleware
│   ├── models/         # Pydantic models
│   ├── services/       # Feature store, ML inference
│   ├── agent/          # Agentic AI tools and agent
│   └── api/            # API endpoints
├── main.py             # FastAPI application
├── requirements.txt    # Python dependencies
├── Dockerfile          # Docker image
├── docker-compose.yml  # Docker orchestration
└── .env.example        # Environment variables template
```

## Configuration

See `.env.example` for all configuration options:

- ClickHouse connection settings
- Redis configuration
- ML model paths
- Agent settings
- Logging configuration

## Models

Place ONNX models in `/app/models/`:
- `courier_assignment_v1.onnx`
- `pvz_scoring_v1.onnx`
- `eta_prediction_v1.onnx`
- `demand_forecast_v1.onnx`

If model files are missing, the service uses placeholder predictions.

## Development

### Running in Debug Mode

```bash
DEBUG=true uvicorn main:app --reload --host 0.0.0.0 --port 8000
```

### Running Tests

```bash
# Install test dependencies
pip install pytest pytest-asyncio httpx

# Run tests
pytest tests/
```

## Monitoring

- Health check: `GET /health`
- Metrics: `GET /metrics`
- Logs: JSON format to stdout and `/app/logs/inference.log`

## Documentation

Full documentation: `../../docs/AGENTIC_DYNAMIC_INTELLIGENCE_GUIDE.md`

## License

CatVRF Internal Use Only
