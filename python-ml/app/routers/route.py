"""Route optimization endpoint using OR-Tools"""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import List, Optional
import logging
import time

logger = logging.getLogger(__name__)

router = APIRouter()


class Location(BaseModel):
    """Location point"""
    lat: float
    lng: float


class Stop(BaseModel):
    """Delivery stop"""
    id: int
    location: Location
    service_time_minutes: int = 5
    time_window_start: Optional[int] = None
    time_window_end: Optional[int] = None


class RouteOptimizationRequest(BaseModel):
    """Request for route optimization"""
    courier_id: int
    vehicle_type: str
    capacity_kg: float
    start_location: Location
    stops: List[Stop]
    max_distance_km: Optional[float] = None
    max_stops: Optional[int] = None


class OptimizedStop(BaseModel):
    """Optimized stop"""
    id: int
    sequence: int
    arrival_time_minutes: int
    distance_from_previous_km: float


class RouteOptimizationResponse(BaseModel):
    """Response with optimized route"""
    courier_id: int
    optimized_stops: List[OptimizedStop]
    total_distance_km: float
    total_time_minutes: int
    algorithm: str


@router.post("/route", response_model=RouteOptimizationResponse)
async def optimize_route(request: RouteOptimizationRequest):
    """
    Optimize delivery route using VRP (Vehicle Routing Problem)
    
    Uses Google OR-Tools for:
    - Optimal stop ordering
    - Time window constraints
    - Capacity constraints
    - Distance minimization
    """
    start_time = time.time()
    
    try:
        # TODO: Implement actual OR-Tools VRP solver
        # For now, use simple nearest-neighbor heuristic
        
        optimized_stops = []
        current_location = request.start_location
        total_distance = 0.0
        total_time = 0
        
        # Simple greedy nearest-neighbor
        remaining_stops = request.stops.copy()
        sequence = 1
        
        while remaining_stops:
            # Find nearest stop
            nearest_idx = 0
            nearest_dist = float('inf')
            
            for idx, stop in enumerate(remaining_stops):
                dist = calculate_distance(
                    current_location.lat, current_location.lng,
                    stop.location.lat, stop.location.lng
                )
                if dist < nearest_dist:
                    nearest_dist = dist
                    nearest_idx = idx
            
            # Add to route
            stop = remaining_stops.pop(nearest_idx)
            travel_time = (nearest_dist / 30) * 60  # Assume 30 km/h avg speed
            
            optimized_stops.append(OptimizedStop(
                id=stop.id,
                sequence=sequence,
                arrival_time_minutes=int(total_time + travel_time),
                distance_from_previous_km=round(nearest_dist, 2)
            ))
            
            total_distance += nearest_dist
            total_time += travel_time + stop.service_time_minutes
            current_location = stop.location
            sequence += 1
        
        # Apply constraints
        if request.max_distance_km and total_distance > request.max_distance_km:
            raise HTTPException(
                status_code=400,
                detail=f"Route exceeds max distance: {total_distance:.2f}km > {request.max_distance_km}km"
            )
        
        if request.max_stops and len(optimized_stops) > request.max_stops:
            optimized_stops = optimized_stops[:request.max_stops]
        
        logger.info(f"Route optimized for courier {request.courier_id}: {len(optimized_stops)} stops, {total_distance:.2f}km")
        
        return RouteOptimizationResponse(
            courier_id=request.courier_id,
            optimized_stops=optimized_stops,
            total_distance_km=round(total_distance, 2),
            total_time_minutes=int(total_time),
            algorithm="nearest_neighbor"  # Will be "ortools_vrp" when implemented
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error in route optimization: {e}")
        raise HTTPException(status_code=500, detail=str(e))


def calculate_distance(lat1: float, lng1: float, lat2: float, lng2: float) -> float:
    """Calculate distance between two points in km (Haversine formula)"""
    import math
    
    earth_radius = 6371  # km
    
    lat1_rad = math.radians(lat1)
    lat2_rad = math.radians(lat2)
    delta_lat = math.radians(lat2 - lat1)
    delta_lng = math.radians(lng2 - lng1)
    
    a = (math.sin(delta_lat / 2) ** 2 +
         math.cos(lat1_rad) * math.cos(lat2_rad) *
         math.sin(delta_lng / 2) ** 2)
    
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))
    
    return earth_radius * c
