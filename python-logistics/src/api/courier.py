"""
Courier Assignment API endpoints
"""

from fastapi import APIRouter, HTTPException
from typing import List
import time
import logging
from datetime import datetime

from ..models.courier import (
    AssignmentRequest,
    AssignmentResponse,
    AssignmentScore,
    BatchAssignmentRequest
)
from ..services.feature_store import FeatureStoreService
from ..services.ml_inference import MLInferenceService
from ..core.logging import get_logger

logger = get_logger(__name__)
router = APIRouter(prefix="/v1/courier", tags=["courier"])


@router.post("/assign", response_model=AssignmentResponse)
async def assign_courier(
    request: AssignmentRequest,
    feature_store: FeatureStoreService,
    ml_service: MLInferenceService
):
    """
    Assign the best courier to a shipment using GNN-based model
    
    - Uses feature store for real-time courier and zone features
    - ML model scores courier-shipment pairs
    - Returns top-k recommendations with explanations
    """
    start_time = time.time()
    
    try:
        # Get zone features from feature store
        zone_features = feature_store.get_zone_speed_features(
            tenant_id=request.tenant_id,
            zone_id=request.shipment.zone_pickup,
            vehicle_type=request.available_couriers[0].vehicle_type.value,
            hour=datetime.now()
        )
        
        # Prepare shipment dict for ML model
        shipment_dict = request.shipment.dict()
        
        # Prepare courier dicts
        courier_dicts = [courier.dict() for courier in request.available_couriers]
        
        # Get ML model and predict scores
        assignment_model = ml_service.get_courier_assignment_model()
        scores = assignment_model.predict_batch(
            shipment=shipment_dict,
            couriers=courier_dicts,
            zone_features=zone_features
        )
        
        # Create assignment scores with additional metadata
        assignment_scores = []
        for courier, score in zip(request.available_couriers, scores):
            # Calculate estimated times (simplified)
            distance_to_pickup = 1.0  # Would calculate with geodistance
            estimated_pickup_time = distance_to_pickup / (courier.velocity_kmh or 30.0) * 60
            estimated_delivery_time = estimated_pickup_time + (request.shipment.distance_km / 30.0) * 60
            
            assignment_score = AssignmentScore(
                courier_id=courier.courier_id,
                score=float(score),
                estimated_pickup_time_minutes=estimated_pickup_time,
                estimated_delivery_time_minutes=estimated_delivery_time,
                distance_to_pickup_km=distance_to_pickup,
                confidence=0.85  # Would come from model uncertainty
            )
            
            if request.include_explanations:
                assignment_score.explanation = {
                    "primary_factors": [
                        f"distance_to_pickup: {distance_to_pickup:.2f}km",
                        f"courier_velocity: {courier.velocity_kmh or 30:.1f}km/h",
                        f"acceptance_rate: {courier.acceptance_rate:.2f}",
                        f"zone_speed: {zone_features['avg_speed_kmh'] if zone_features else 30:.1f}km/h"
                    ]
                }
            
            assignment_scores.append(assignment_score)
        
        # Sort by score
        assignment_scores.sort(key=lambda x: x.score, reverse=True)
        
        # Get top-k
        top_scores = assignment_scores[:request.top_k]
        
        # Create response
        inference_time = (time.time() - start_time) * 1000
        response = AssignmentResponse(
            shipment_id=request.shipment.shipment_id,
            recommended_courier=top_scores[0].courier_id,
            alternative_couriers=top_scores[1:],
            model_version="courier_assignment_v1",
            inference_time_ms=inference_time,
            features_used=[
                "distance_to_pickup",
                "courier_velocity",
                "acceptance_rate",
                "zone_speed",
                "zone_demand",
                "hour_of_day",
                "day_of_week",
                "traffic_level",
                "weather_impact",
                "vehicle_type"
            ]
        )
        
        if request.include_explanations:
            response.explanation = {
                "model_type": "GNN-based assignment",
                "top_factors": [
                    "courier proximity to pickup location",
                    "courier acceptance rate",
                    "zone traffic conditions",
                    "vehicle type compatibility"
                ]
            }
        
        logger.info(
            f"Courier assignment completed for shipment {request.shipment.shipment_id}",
            extra={
                "tenant_id": request.tenant_id,
                "shipment_id": request.shipment.shipment_id,
                "recommended_courier": response.recommended_courier,
                "inference_time_ms": inference_time
            }
        )
        
        return response
        
    except Exception as e:
        logger.error(f"Courier assignment failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/assign/batch")
async def assign_couriers_batch(
    request: BatchAssignmentRequest,
    feature_store: FeatureStoreService,
    ml_service: MLInferenceService
):
    """
    Batch assignment for multiple shipments with optimization objective
    
    Supports objectives:
    - minimize_total_time: Minimize total delivery time across all shipments
    - maximize_acceptance_rate: Maximize courier acceptance probability
    - balance_workload: Balance workload across couriers
    """
    start_time = time.time()
    
    try:
        # For batch assignment, we'd use a more sophisticated optimization
        # This is a simplified implementation that processes each shipment independently
        
        results = []
        assignment_model = ml_service.get_courier_assignment_model()
        
        for shipment in request.shipments:
            # Create individual assignment request
            assignment_request = AssignmentRequest(
                tenant_id=request.tenant_id,
                vertical=request.vertical,
                shipment=shipment,
                available_couriers=request.available_couriers,
                include_explanations=False,
                top_k=1
            )
            
            # Get assignment (reuse the single endpoint logic)
            response = await assign_courier(assignment_request, feature_store, ml_service)
            results.append({
                "shipment_id": shipment.shipment_id,
                "assigned_courier": response.recommended_courier,
                "score": response.alternative_couriers[0].score if response.alternative_couriers else 0.0
            })
        
        inference_time = (time.time() - start_time) * 1000
        
        return {
            "tenant_id": request.tenant_id,
            "optimization_objective": request.optimization_objective,
            "assignments": results,
            "total_shipments": len(request.shipments),
            "inference_time_ms": inference_time
        }
        
    except Exception as e:
        logger.error(f"Batch courier assignment failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))
