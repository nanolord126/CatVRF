"""Courier assignment prediction endpoint"""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import List, Optional
import numpy as np
import logging

from app.core.metrics import prediction_counter, prediction_latency
import time

logger = logging.getLogger(__name__)

router = APIRouter()


class CourierCandidate(BaseModel):
    """Courier candidate for scoring"""
    id: int
    distance_m: float
    capacity_kg: float
    rating: float
    is_taxi_driver: bool
    vehicle_type: str
    current_lat: float
    current_lng: float


class CourierAssignmentRequest(BaseModel):
    """Request for courier assignment prediction"""
    order_id: int
    delivery_lat: float
    delivery_lng: float
    weight_kg: float
    volume_cm3: float
    items_count: int
    time_sensitive: bool
    candidates: List[CourierCandidate]
    hour_of_day: int
    day_of_week: int
    weather_condition: str = "unknown"
    traffic_level: str = "unknown"


class CourierScore(BaseModel):
    """Scored courier"""
    courier_id: int
    score: float
    rank: int


class CourierAssignmentResponse(BaseModel):
    """Response with scored couriers"""
    order_id: int
    scored_couriers: List[CourierScore]
    best_courier_id: int
    model_version: str
    confidence: float


@router.post("/courier-assignment", response_model=CourierAssignmentResponse)
async def predict_courier_assignment(request: CourierAssignmentRequest):
    """
    Predict best courier for order assignment
    
    Uses ML model to score couriers based on:
    - Distance
    - Capacity fit
    - Rating
    - Vehicle type
    - Is taxi driver
    - Historical performance
    - Current traffic/weather
    """
    start_time = time.time()
    
    try:
        # TODO: Load actual model and predict
        # For now, use heuristic scoring
        
        scored_couriers = []
        for idx, courier in enumerate(request.candidates):
            # Heuristic score (will be replaced with ML)
            score = (
                0.40 * (1 / (courier.distance_m + 1)) +
                0.25 * min(courier.capacity_kg / max(request.weight_kg, 1), 1.0) +
                0.20 * (courier.rating / 5.0) +
                0.10 * (1 if courier.is_taxi_driver else 0) +
                0.05 * (1 if request.time_sensitive and courier.vehicle_type in ['car', 'taxi'] else 0)
            )
            
            scored_couriers.append(CourierScore(
                courier_id=courier.id,
                score=float(score),
                rank=idx
            ))
        
        # Sort by score
        scored_couriers.sort(key=lambda x: x.score, reverse=True)
        
        # Update ranks
        for idx, sc in enumerate(scored_couriers):
            sc.rank = idx + 1
        
        # Get best courier
        best_courier_id = scored_couriers[0].courier_id if scored_couriers else None
        
        # Calculate confidence (difference between top scores)
        confidence = 0.8  # TODO: Calculate based on score distribution
        
        # Record metrics
        latency = time.time() - start_time
        prediction_latency.labels(model="courier_assignment").observe(latency)
        prediction_counter.labels(model="courier_assignment", status="success").inc()
        
        logger.info(f"Courier assignment predicted for order {request.order_id}, best courier: {best_courier_id}")
        
        return CourierAssignmentResponse(
            order_id=request.order_id,
            scored_couriers=scored_couriers,
            best_courier_id=best_courier_id,
            model_version="v1.0.0",
            confidence=confidence
        )
        
    except Exception as e:
        logger.error(f"Error in courier assignment prediction: {e}")
        prediction_counter.labels(model="courier_assignment", status="error").inc()
        raise HTTPException(status_code=500, detail=str(e))
