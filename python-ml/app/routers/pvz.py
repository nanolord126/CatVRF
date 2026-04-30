"""PVZ scoring prediction endpoint"""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import List
import logging
import time

from app.core.metrics import prediction_counter, prediction_latency

logger = logging.getLogger(__name__)

router = APIRouter()


class PvzCandidate(BaseModel):
    """PVZ candidate for scoring"""
    id: int
    lat: float
    lng: float
    capacity: int
    current_load: int
    is_24h: bool
    working_hours: str
    distance_m: float


class PvzScoringRequest(BaseModel):
    """Request for PVZ scoring prediction"""
    order_id: int
    delivery_lat: float
    delivery_lng: float
    weight_kg: float
    items_count: int
    prefers_pvz: bool
    candidates: List[PvzCandidate]
    hour_of_day: int
    day_of_week: int


class PvzScore(BaseModel):
    """Scored PVZ"""
    pvz_id: int
    score: float
    rank: int
    load_percentage: float


class PvzScoringResponse(BaseModel):
    """Response with scored PVZs"""
    order_id: int
    scored_pvzs: List[PvzScore]
    best_pvz_id: int
    model_version: str


@router.post("/pvz-scoring", response_model=PvzScoringResponse)
async def predict_pvz_scoring(request: PvzScoringRequest):
    """
    Predict best PVZ for order assignment
    
    Uses ML model to score PVZs based on:
    - Distance
    - Load balance
    - 24/7 availability
    - User preference
    - Predicted demand
    """
    start_time = time.time()
    
    try:
        # TODO: Load actual model and predict
        # For now, use heuristic scoring
        
        scored_pvzs = []
        for idx, pvz in enumerate(request.candidates):
            load_percentage = pvz.current_load / max(pvz.capacity, 1)
            
            # Heuristic score
            score = (
                0.40 * (1 / (pvz.distance_m + 1)) +
                0.30 * (1 - load_percentage) +
                0.20 * (1 if pvz.is_24h else 0) +
                0.10 * (1 if request.prefers_pvz else 0)
            )
            
            scored_pvzs.append(PvzScore(
                pvz_id=pvz.id,
                score=float(score),
                rank=idx,
                load_percentage=load_percentage
            ))
        
        # Sort by score
        scored_pvzs.sort(key=lambda x: x.score, reverse=True)
        
        # Update ranks
        for idx, sp in enumerate(scored_pvzs):
            sp.rank = idx + 1
        
        best_pvz_id = scored_pvzs[0].pvz_id if scored_pvzs else None
        
        # Record metrics
        latency = time.time() - start_time
        prediction_latency.labels(model="pvz_scoring").observe(latency)
        prediction_counter.labels(model="pvz_scoring", status="success").inc()
        
        logger.info(f"PVZ scoring predicted for order {request.order_id}, best PVZ: {best_pvz_id}")
        
        return PvzScoringResponse(
            order_id=request.order_id,
            scored_pvzs=scored_pvzs,
            best_pvz_id=best_pvz_id,
            model_version="v1.0.0"
        )
        
    except Exception as e:
        logger.error(f"Error in PVZ scoring prediction: {e}")
        prediction_counter.labels(model="pvz_scoring", status="error").inc()
        raise HTTPException(status_code=500, detail=str(e))
