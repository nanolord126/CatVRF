"""
CatVRF Recommendation Engine - ML Inference Service
Two-tower model + Feature Store + FastAPI API
"""

from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import List, Dict, Optional, Any
import numpy as np
import logging
from datetime import datetime
import json

app = FastAPI(
    title="CatVRF Recommendation ML Service",
    description="Two-tower recommendation model inference with feature store integration",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# ============================================================================
# Pydantic Models
# ============================================================================

class CandidateRequest(BaseModel):
    tenant_id: int
    user_features: List[float]
    limit: int = 200
    vertical: Optional[str] = None

class CandidateItem(BaseModel):
    item_id: int
    seller_id: int
    vertical: str
    score: float
    confidence: float = 0.8

class CandidatesResponse(BaseModel):
    candidates: List[CandidateItem]
    model_version: str
    latency_ms: float

class RankingRequest(BaseModel):
    tenant_id: int
    user_features: List[float]
    item_features: List[Dict[str, Any]]
    context_features: List[float]

class RankedItem(BaseModel):
    item_id: int
    seller_id: int
    score: float
    confidence: float
    position: int
    vertical: str

class RankingResponse(BaseModel):
    ranked_items: List[RankedItem]
    model_version: str
    latency_ms: float

class EmbeddingResponse(BaseModel):
    embedding: List[float]
    model_version: str

class HealthResponse(BaseModel):
    status: str
    model_version: str
    latency_ms: float
    timestamp: str

class TrainModelRequest(BaseModel):
    model_type: str
    config: Dict[str, Any]

class TrainingResponse(BaseModel):
    success: bool
    job_id: Optional[str]
    estimated_time_minutes: int = 0
    error: Optional[str] = None

# ============================================================================
# Two-Tower Model Implementation
# ============================================================================

class TwoTowerModel:
    def __init__(self, embedding_dim: int = 128):
        self.embedding_dim = embedding_dim
        self.user_tower_weights = np.random.randn(512, embedding_dim) * 0.01
        self.item_tower_weights = np.random.randn(1024, embedding_dim) * 0.01
        self.version = "v1.0.0"
        logger.info(f"TwoTowerModel initialized with embedding_dim={embedding_dim}")

    def get_user_embedding(self, user_features: List[float]) -> np.ndarray:
        if len(user_features) < 512:
            user_features = user_features + [0.0] * (512 - len(user_features))
        else:
            user_features = user_features[:512]
        
        user_vec = np.array(user_features)
        embedding = np.dot(user_vec, self.user_tower_weights)
        return embedding / (np.linalg.norm(embedding) + 1e-8)

    def get_item_embedding(self, item_features: List[float]) -> np.ndarray:
        if len(item_features) < 1024:
            item_features = item_features + [0.0] * (1024 - len(item_features))
        else:
            item_features = item_features[:1024]
        
        item_vec = np.array(item_features)
        embedding = np.dot(item_vec, self.item_tower_weights)
        return embedding / (np.linalg.norm(embedding) + 1e-8)

    def compute_similarity(self, user_embedding: np.ndarray, item_embedding: np.ndarray) -> float:
        return float(np.dot(user_embedding, item_embedding))

# ============================================================================
# Feature Store Mock
# ============================================================================

class FeatureStore:
    def __init__(self):
        self.user_features_cache = {}
        self.item_features_cache = {}
        logger.info("FeatureStore initialized")

    def get_user_features(self, tenant_id: int, user_id: int) -> List[float]:
        cache_key = f"{tenant_id}:{user_id}"
        if cache_key in self.user_features_cache:
            return self.user_features_cache[cache_key]
        
        features = np.random.randn(256).tolist()
        self.user_features_cache[cache_key] = features
        return features

    def get_item_features(self, tenant_id: int, item_id: int) -> List[float]:
        cache_key = f"{tenant_id}:{item_id}"
        if cache_key in self.item_features_cache:
            return self.item_features_cache[cache_key]
        
        features = np.random.randn(512).tolist()
        self.item_features_cache[cache_key] = features
        return features

    def get_candidate_items(self, tenant_id: int, vertical: Optional[str] = None, limit: int = 200) -> List[Dict]:
        items = []
        for i in range(min(limit, 1000)):
            item_id = 1000 + i
            seller_id = 100 + (i % 10)
            item_vertical = vertical if vertical else ["electronics", "fashion", "home", "beauty"][i % 4]
            score = max(0.1, 1.0 - (i * 0.005))
            
            items.append({
                "item_id": item_id,
                "seller_id": seller_id,
                "vertical": item_vertical,
                "score": score,
                "confidence": 0.8
            })
        
        return items

# ============================================================================
# Initialize Services
# ============================================================================

model = TwoTowerModel(embedding_dim=128)
feature_store = FeatureStore()

# ============================================================================
# API Endpoints
# ============================================================================

@app.get("/health", response_model=HealthResponse)
async def health_check():
    start_time = datetime.now()
    
    return HealthResponse(
        status="healthy",
        model_version=model.version,
        latency_ms=0.0,
        timestamp=datetime.now().isoformat()
    )

@app.post("/v1/inference/candidates", response_model=CandidatesResponse)
async def get_candidates(request: CandidateRequest):
    start_time = datetime.now()
    
    try:
        user_embedding = model.get_user_embedding(request.user_features)
        
        candidates = feature_store.get_candidate_items(
            tenant_id=request.tenant_id,
            vertical=request.vertical,
            limit=request.limit
        )
        
        scored_candidates = []
        for item in candidates:
            item_features = feature_store.get_item_features(request.tenant_id, item["item_id"])
            item_embedding = model.get_item_embedding(item_features)
            similarity = model.compute_similarity(user_embedding, item_embedding)
            
            scored_candidates.append(CandidateItem(
                item_id=item["item_id"],
                seller_id=item["seller_id"],
                vertical=item["vertical"],
                score=similarity,
                confidence=item["confidence"]
            ))
        
        scored_candidates.sort(key=lambda x: x.score, reverse=True)
        scored_candidates = scored_candidates[:request.limit]
        
        latency_ms = (datetime.now() - start_time).total_seconds() * 1000
        
        return CandidatesResponse(
            candidates=scored_candidates,
            model_version=model.version,
            latency_ms=latency_ms
        )
    except Exception as e:
        logger.error(f"Error generating candidates: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/v1/inference/rank", response_model=RankingResponse)
async def rank_items(request: RankingRequest):
    start_time = datetime.now()
    
    try:
        user_embedding = model.get_user_embedding(request.user_features)
        
        ranked_items = []
        for idx, item in enumerate(request.item_features):
            item_features = item.get("features", [0.0] * 512)
            if isinstance(item_features, dict):
                item_features = list(item_features.values())[:512]
            
            item_embedding = model.get_item_embedding(item_features)
            similarity = model.compute_similarity(user_embedding, item_embedding)
            
            ranked_items.append(RankedItem(
                item_id=item.get("item_id", idx),
                seller_id=item.get("seller_id", 0),
                score=similarity,
                confidence=0.85,
                position=idx,
                vertical=item.get("vertical", "unknown")
            ))
        
        ranked_items.sort(key=lambda x: x.score, reverse=True)
        
        for idx, item in enumerate(ranked_items):
            item.position = idx
        
        latency_ms = (datetime.now() - start_time).total_seconds() * 1000
        
        return RankingResponse(
            ranked_items=ranked_items,
            model_version=model.version,
            latency_ms=latency_ms
        )
    except Exception as e:
        logger.error(f"Error ranking items: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/v1/embeddings/{entity_type}/{entity_id}", response_model=EmbeddingResponse)
async def get_embeddings(entity_type: str, entity_id: int, tenant_id: int = 1):
    start_time = datetime.now()
    
    try:
        if entity_type == "user":
            features = feature_store.get_user_features(tenant_id, entity_id)
            embedding = model.get_user_embedding(features)
        elif entity_type == "item":
            features = feature_store.get_item_features(tenant_id, entity_id)
            embedding = model.get_item_embedding(features)
        else:
            raise HTTPException(status_code=400, detail=f"Unknown entity type: {entity_type}")
        
        return EmbeddingResponse(
            embedding=embedding.tolist(),
            model_version=model.version
        )
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error getting embeddings: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/v1/models/train", response_model=TrainingResponse)
async def train_model(request: TrainModelRequest, background_tasks: BackgroundTasks):
    try:
        job_id = f"train_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        background_tasks.add_task(background_training_task, job_id, request.model_type, request.config)
        
        return TrainingResponse(
            success=True,
            job_id=job_id,
            estimated_time_minutes=30
        )
    except Exception as e:
        logger.error(f"Error starting training: {e}")
        return TrainingResponse(
            success=False,
            job_id=None,
            error=str(e)
        )

async def background_training_task(job_id: str, model_type: str, config: Dict[str, Any]):
    logger.info(f"Starting background training job {job_id} for model {model_type}")
    # Simulate training
    import asyncio
    await asyncio.sleep(2)
    logger.info(f"Training job {job_id} completed")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
