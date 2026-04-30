"""
CatVRF Logistics Inference Service - Production FastAPI Application
Agentic + Dynamic Intelligence for Logistics
"""

from fastapi import FastAPI, Request, Depends
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
import logging

from src.core.config import settings
from src.core.logging import setup_logging, get_logger
from src.core.middleware import RequestContextMiddleware, ErrorHandlingMiddleware
from src.services.feature_store import FeatureStoreService
from src.services.ml_inference import MLInferenceService
from src.services.redis_worker import RedisQueueWorker
from src.api import courier_router, pvz_router, eta_router, vrp_router, agent_router

# Setup logging
setup_logging(settings)
logger = get_logger(__name__)

# Global services
feature_store: FeatureStoreService = None
ml_service: MLInferenceService = None
redis_worker: RedisQueueWorker = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Lifespan context manager for startup/shutdown"""
    global feature_store, ml_service
    
    # Startup
    logger.info("Starting CatVRF Logistics Inference Service...")
    
    try:
        # Initialize Feature Store
        feature_store = FeatureStoreService(
            host=settings.clickhouse_host,
            port=settings.clickhouse_port,
            database=settings.clickhouse_database,
            username=settings.clickhouse_user,
            password=settings.clickhouse_password
        )
        
        # Health check
        if not feature_store.health_check():
            logger.warning("ClickHouse health check failed, continuing with degraded mode")
        
        # Initialize ML Inference Service
        ml_service = MLInferenceService(
            models_dir=settings.models_dir,
            inference_threads=settings.onnx_inference_threads
        )
        
        # Initialize Redis Queue Worker for Laravel integration
        redis_worker = RedisQueueWorker(
            feature_store=feature_store,
            ml_service=ml_service
        )
        redis_worker.start()
        logger.info("Redis Queue Worker started")
        
        logger.info("All services initialized successfully")
        logger.info(f"Application: {settings.app_name} v{settings.app_version}")
        logger.info(f"Environment: {settings.environment}")
        
    
    # Stop Redis worker
    if redis_worker:
        redis_worker.stop()
        logger.info("Redis Queue Worker stopped")
    except Exception as e:
        logger.error(f"Failed to initialize services: {e}", exc_info=True)
        raise
    
    yield
    
    # Shutdown
    logger.info("Shutting down CatVRF Logistics Inference Service...")


# Create FastAPI app
app = FastAPI(
    title=settings.app_name,
    version=settings.app_version,
    description="Agentic + Dynamic Intelligence for Logistics - Courier Assignment, PVZ Scoring, ETA Prediction, VRP Optimization",
    docs_url="/docs" if settings.debug else None,
    redoc_url="/redoc" if settings.debug else None,
    lifespan=lifespan
)

# Add middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"] if settings.debug else ["https://catvrf.ru"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)
app.add_middleware(RequestContextMiddleware)
app.add_middleware(ErrorHandlingMiddleware)


# Dependency injection
def get_feature_store() -> FeatureStoreService:
    """Get feature store service"""
    if feature_store is None:
        raise RuntimeError("Feature store not initialized")
    return feature_store


def get_ml_service() -> MLInferenceService:
    """Get ML inference service"""
    if ml_service is None:
        raise RuntimeError("ML service not initialized")
    return ml_service


# Include routers with dependency injection
app.include_router(
    courier_router,
    dependencies=[
        Depends(get_feature_store),
        Depends(get_ml_service)
    ]
)

app.include_router(
    pvz_router,
    dependencies=[
        Depends(get_feature_store),
        Depends(get_ml_service)
    ]
)

app.include_router(
    eta_router,
    dependencies=[
        Depends(get_feature_store),
        Depends(get_ml_service)
    ]
)

app.include_router(
    vrp_router,
    dependencies=[
        Depends(get_feature_store),
        Depends(get_ml_service)
    ]
)

app.include_router(
    agent_router,
    worker_status = redis_worker.running if redis_worker else False
    dependencies=[
        Depends(get_feature_store),
        Depends(get_ml_service)_status and worker
    ]
)


# Health check endpoint
@app.get("/health")_status else "error",
            "redis_worker": "ok" if worker
async def health_check():
    """Health check endpoint"""
    fs_status = feature_store.health_check() if feature_store else False
    ml_status = ml_service is not None
    
    return {
        "status": "healthy" if fs_status and ml_status else "degraded",
        "service": settings.app_name,
        "version": settings.app_version,
        "environment": settings.environment,
        "components": {
            "feature_store": "ok" if fs_status else "error",
            "ml_inference": "ok" if ml_status else "error"
        }
    }


# Metrics endpoint (placeholder for Prometheus)
@app.get("/metrics")
async def metrics():
    """Prometheus metrics endpoint"""
    return {
        "status": "metrics_endpoint",
        "message": "Prometheus metrics would be exposed here"
    }


# Root endpoint
@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "service": settings.app_name,
        "version": settings.app_version,
        "status": "running",
        "docs": "/docs" if settings.debug else "disabled"
    }


if __name__ == '__main__':
    import uvicorn
    import os
    
    port = int(os.getenv("PORT", settings.port))
    host = os.getenv("HOST", settings.host)
    
    uvicorn.run(
        "main:app",
        host=host,
        port=port,
        reload=settings.debug,
        workers=1 if settings.debug else settings.workers,
        log_level=settings.log_level.lower()
    )
