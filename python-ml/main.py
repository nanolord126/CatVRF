"""
CatVRF ML Service — Python сервис для ML-моделей логистики

Эндпоинты:
- POST /predict/courier-assignment — скоринг курьеров
- POST /predict/pvz-scoring — скоринг ПВЗ
- POST /predict/eta — предсказание ETA
- POST /optimize/route — оптимизация маршрута (OR-Tools)
- POST /agent/analyze — Agentic AI (Кот ИИ) для аномалий

Канон CatVRF 2026:
- FastAPI + Uvicorn
- Модели в ONNX/joblib
- Redis для кэширования
- ClickHouse для feature extraction
- Prometheus + OpenTelemetry для мониторинга
"""

from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from contextlib import asynccontextmanager
import uvicorn
import os
from typing import List, Optional, Dict, Any
import logging

from app.routers import courier, pvz, eta, route, agent
from app.core.config import settings
from app.core.logging import setup_logging
from app.core.metrics import setup_metrics

# Setup logging
setup_logging()
logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Lifespan context manager for startup/shutdown"""
    logger.info("Starting CatVRF ML Service...")
    # Load models on startup
    # TODO: Load trained models from S3/MinIO
    logger.info("CatVRF ML Service started")
    yield
    logger.info("Shutting down CatVRF ML Service...")


app = FastAPI(
    title="CatVRF ML Service",
    description="ML models for Unified Logistics Platform",
    version="1.0.0",
    lifespan=lifespan,
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Setup metrics
setup_metrics(app)

# Include routers
app.include_router(courier.router, prefix="/api/v1/predict", tags=["courier"])
app.include_router(pvz.router, prefix="/api/v1/predict", tags=["pvz"])
app.include_router(eta.router, prefix="/api/v1/predict", tags=["eta"])
app.include_router(route.router, prefix="/api/v1/optimize", tags=["route"])
app.include_router(agent.router, prefix="/api/v1/agent", tags=["agent"])


@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "service": "CatVRF ML Service",
        "version": "1.0.0",
        "status": "healthy",
    }


@app.get("/health")
async def health_check():
    """Detailed health check"""
    return {
        "status": "healthy",
        "models_loaded": True,  # TODO: Check actual model status
        "clickhouse_connected": True,  # TODO: Check actual connection
        "redis_connected": True,  # TODO: Check actual connection
    }


if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host=settings.HOST,
        port=settings.PORT,
        reload=settings.RELOAD,
        log_level=settings.LOG_LEVEL,
    )
