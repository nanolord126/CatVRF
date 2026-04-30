"""
PVZ Scoring API endpoints
"""

from fastapi import APIRouter, HTTPException
from typing import List
import time
import logging
from datetime import datetime

from ..models.pvz import (
    PVZScoringRequest,
    PVZScoringResponse,
    PVZScore
)
from ..services.feature_store import FeatureStoreService
from ..services.ml_inference import MLInferenceService
from ..core.logging import get_logger

logger = get_logger(__name__)
router = APIRouter(prefix="/v1/pvz", tags=["pvz"])


@router.post("/score", response_model=PVZScoringResponse)
async def score_pvz(
    request: PVZScoringRequest,
    feature_store: FeatureStoreService,
    ml_service: MLInferenceService
):
    """
    Score and rank PVZs for a user based on multiple factors:
    
    - Distance from user location
    - Current availability (lockers)
    - Historical load and performance
    - Temporal features (forecasted load for next 3 hours)
    - User preference scores
    - Zone demand and courier availability
    """
    start_time = time.time()
    
    try:
        # Filter PVZs by distance and availability
        filtered_pvzs = []
        for pvz in request.available_pvzs:
            # Calculate distance (simplified - would use geodistance)
            distance = 2.0  # Placeholder
            
            if distance <= request.max_distance_km and pvz.available_lockers >= request.min_available_lockers:
                pvz_dict = pvz.dict()
                pvz_dict['distance_km'] = distance
                filtered_pvzs.append(pvz_dict)
        
        if not filtered_pvzs:
            raise HTTPException(
                status_code=404,
                detail="No PVZs found matching criteria"
            )
        
        # Get zone features
        zone_features = feature_store.get_demand_forecast(
            tenant_id=request.tenant_id,
            zone_id=request.user_geo_hash,
            forecast_hour=datetime.now()
        )
        
        # Get ML model and predict scores
        pvz_model = ml_service.get_pvz_scoring_model()
        scores = pvz_model.predict_batch(
            pvzs=filtered_pvzs,
            user_location=(request.user_latitude, request.user_longitude),
            hour_of_day=request.hour_of_day,
            day_of_week=request.day_of_week,
            is_weekend=request.is_weekend,
            zone_features=zone_features
        )
        
        # Create PVZ scores with additional metadata
        pvz_scores = []
        for pvz_dict, score in zip(filtered_pvzs, scores):
            # Calculate component scores
            distance_score = max(0, 1 - pvz_dict['distance_km'] / request.max_distance_km)
            availability_score = pvz_dict['available_lockers'] / max(pvz_dict['total_lockers'], 1)
            preference_score = pvz_dict.get('preference_score', 0.5)
            performance_score = 1.0 if pvz_dict.get('avg_pickup_time_minutes', 10) < 15 else 0.7
            
            pvz_score = PVZScore(
                pvz_id=pvz_dict['pvz_id'],
                pvz_name=pvz_dict['pvz_name'],
                score=float(score),
                distance_km=pvz_dict['distance_km'],
                estimated_arrival_time_minutes=pvz_dict['distance_km'] / 30.0 * 60,  # Assuming 30km/h
                availability_score=availability_score,
                preference_score=preference_score,
                performance_score=performance_score,
                confidence=0.88  # Would come from model uncertainty
            )
            
            if request.include_explanations:
                pvz_score.explanation = {
                    "primary_factors": [
                        f"distance: {pvz_dict['distance_km']:.2f}km",
                        f"availability: {pvz_dict['available_lockers']}/{pvz_dict['total_lockers']}",
                        f"preference_score: {preference_score:.2f}",
                        f"historical_load_avg: {pvz_dict.get('historical_load_avg', 0.5):.2f}"
                    ]
                }
            
            pvz_scores.append(pvz_score)
        
        # Sort by score
        pvz_scores.sort(key=lambda x: x.score, reverse=True)
        
        # Get top-k
        top_scores = pvz_scores[:request.top_k]
        
        # Create response
        inference_time = (time.time() - start_time) * 1000
        response = PVZScoringResponse(
            recommended_pvz=top_scores[0],
            alternative_pvzs=top_scores[1:],
            model_version="pvz_scoring_v1",
            inference_time_ms=inference_time,
            features_used=[
                "distance_km",
                "load_ratio",
                "available_lockers_ratio",
                "avg_pickup_time",
                "preference_score",
                "historical_load_avg",
                "historical_load_p95",
                "zone_demand",
                "hour_of_day",
                "day_of_week",
                "is_weekend"
            ],
            total_pvzs_evaluated=len(filtered_pvzs)
        )
        
        logger.info(
            f"PVZ scoring completed for user location ({request.user_latitude}, {request.user_longitude})",
            extra={
                "tenant_id": request.tenant_id,
                "recommended_pvz": response.recommended_pvz.pvz_id,
                "total_evaluated": len(filtered_pvzs),
                "inference_time_ms": inference_time
            }
        )
        
        return response
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"PVZ scoring failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))
