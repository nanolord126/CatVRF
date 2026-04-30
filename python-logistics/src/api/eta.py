"""
ETA Prediction API endpoints
"""

from fastapi import APIRouter, HTTPException
from typing import List, Tuple
import time
import numpy as np
import logging
from datetime import datetime

from ..models.eta import (
    ETARequest,
    ETAResponse,
    RouteSegment,
    BatchETARequest
)
from ..services.feature_store import FeatureStoreService
from ..services.ml_inference import MLInferenceService
from ..core.logging import get_logger

logger = get_logger(__name__)
router = APIRouter(prefix="/v1/eta", tags=["eta"])


@router.post("/predict", response_model=ETAResponse)
async def predict_eta(
    request: ETARequest,
    feature_store: FeatureStoreService,
    ml_service: MLInferenceService
):
    """
    Predict Estimated Time of Arrival (ETA) using hybrid ML model
    
    Model combines:
    - LightGBM for tabular features (distance, traffic, weather)
    - Neural Network for sequence-based route features
    - Historical performance data from feature store
    
    Returns:
    - Estimated time in minutes
    - Confidence interval (optional)
    - Route breakdown with segment-level predictions (optional)
    """
    start_time = time.time()
    
    try:
        features = request.features.dict()
        
        # Get zone speed features
        pickup_zone_speed = None
        delivery_zone_speed = None
        
        pickup_speed = feature_store.get_zone_speed_features(
            tenant_id=features['tenant_id'],
            zone_id=features['zone_pickup'],
            vehicle_type=features['vehicle_type'],
            hour=datetime.now()
        )
        
        delivery_speed = feature_store.get_zone_speed_features(
            tenant_id=features['tenant_id'],
            zone_id=features['zone_delivery'],
            vehicle_type=features['vehicle_type'],
            hour=datetime.now()
        )
        
        if pickup_speed:
            pickup_zone_speed = pickup_speed.get('avg_speed_kmh')
        
        if delivery_speed:
            delivery_zone_speed = delivery_speed.get('avg_speed_kmh')
        
        # Get ML model and predict ETA
        eta_model = ml_service.get_eta_prediction_model()
        eta_minutes = eta_model.predict(
            features=features,
            pickup_zone_speed=pickup_zone_speed,
            delivery_zone_speed=delivery_zone_speed
        )
        
        # Calculate confidence interval (simplified - would come from model uncertainty)
        confidence_interval = None
        if request.include_confidence_interval:
            std_error = eta_minutes * 0.15  # 15% coefficient of variation
            z_score = 1.96 if request.confidence_level >= 0.95 else 1.645
            lower = max(eta_minutes - z_score * std_error, 1.0)
            upper = eta_minutes + z_score * std_error
            confidence_interval = (lower, upper)
        
        # Calculate route segments (if requested)
        route_segments = None
        if request.include_route_breakdown:
            # Simplified route breakdown (would use routing API)
            route_segments = [
                RouteSegment(
                    from_point={
                        "latitude": features['pickup_lat'],
                        "longitude": features['pickup_lon'],
                        "stop_type": "pickup",
                        "sequence": 0
                    },
                    to_point={
                        "latitude": features['delivery_lat'],
                        "longitude": features['delivery_lon'],
                        "stop_type": "delivery",
                        "sequence": 1
                    },
                    distance_km=features['distance_km'],
                    estimated_time_minutes=eta_minutes,
                    traffic_level=features['traffic_level'],
                    weather_impact=1.0 if features['weather_condition'] == 'clear' else 0.8
                )
            ]
        
        # Calculate impact factors
        traffic_impact = 1.0
        if features['traffic_level'] == 2:
            traffic_impact = 1.2
        elif features['traffic_level'] == 3:
            traffic_impact = 1.5
        elif features['traffic_level'] == 4:
            traffic_impact = 2.0
        
        weather_impact = 1.0
        if features['weather_condition'] in ['rain', 'heavy_rain']:
            weather_impact = 1.2
        elif features['weather_condition'] in ['snow', 'blizzard']:
            weather_impact = 1.5
        
        # Create response
        inference_time = (time.time() - start_time) * 1000
        response = ETAResponse(
            shipment_id=features['shipment_id'],
            estimated_time_minutes=eta_minutes,
            confidence_interval=confidence_interval,
            route_segments=route_segments,
            model_version="eta_prediction_v1",
            model_type="hybrid",
            inference_time_ms=inference_time,
            features_used=[
                "distance_km",
                "hour_of_day",
                "day_of_week",
                "is_weekend",
                "is_holiday",
                "traffic_level",
                "weather_impact",
                "pickup_zone_speed",
                "delivery_zone_speed",
                "vehicle_type",
                "courier_acceptance_rate",
                "historical_eta_error"
            ],
            traffic_impact_factor=traffic_impact,
            weather_impact_factor=weather_impact
        )
        
        logger.info(
            f"ETA prediction completed for shipment {features['shipment_id']}",
            extra={
                "tenant_id": features['tenant_id'],
                "shipment_id": features['shipment_id'],
                "eta_minutes": eta_minutes,
                "inference_time_ms": inference_time
            }
        )
        
        return response
        
    except Exception as e:
        logger.error(f"ETA prediction failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/predict/batch")
async def predict_eta_batch(
    request: BatchETARequest,
    feature_store: FeatureStoreService,
    ml_service: MLInferenceService
):
    """
    Batch ETA prediction for multiple shipments
    """
    start_time = time.time()
    
    try:
        results = []
        eta_model = ml_service.get_eta_prediction_model()
        
        for shipment_features in request.shipments:
            # Create individual ETA request
            eta_request = ETARequest(
                features=shipment_features,
                include_route_breakdown=request.include_route_breakdown,
                include_confidence_interval=request.include_confidence_interval
            )
            
            # Get prediction (reuse the single endpoint logic)
            response = await predict_eta(eta_request, feature_store, ml_service)
            results.append({
                "shipment_id": shipment_features.shipment_id,
                "eta_minutes": response.estimated_time_minutes,
                "confidence_interval": response.confidence_interval
            })
        
        inference_time = (time.time() - start_time) * 1000
        
        return {
            "predictions": results,
            "total_shipments": len(request.shipments),
            "inference_time_ms": inference_time
        }
        
    except Exception as e:
        logger.error(f"Batch ETA prediction failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))
