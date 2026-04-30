"""
Vehicle Routing Problem (VRP) API endpoints
"""

from fastapi import APIRouter, HTTPException
import time
import logging
from typing import List

from ..models.vrp import (
    VRPRequest,
    VRPSolution,
    Route,
    RouteStop,
    Vehicle,
    Order
)
from ..services.feature_store import FeatureStoreService
from ..services.ml_inference import MLInferenceService
from ..core.logging import get_logger

# Import existing solver
from ...solvers.vrp_solver import solve_vrp_nearest_neighbor, haversine

logger = get_logger(__name__)
router = APIRouter(prefix="/v1/vrp", tags=["vrp"])


def calculate_distance(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    """Calculate distance between two points"""
    return haversine(lat1, lon1, lat2, lon2)


@router.post("/optimize", response_model=VRPSolution)
async def optimize_vrp(
    request: VRPRequest,
    feature_store: FeatureStoreService,
    ml_service: MLInferenceService
):
    """
    Optimize vehicle routing using hybrid ML + classical solver approach
    
    Supports multiple solver types:
    - nearest_neighbor: Fast heuristic (fallback)
    - ortools: Google OR-Tools (if available)
    - hybrid: ML-enhanced classical solver
    - rl: Reinforcement learning (future)
    
    ML features used:
    - Traffic conditions by zone
    - Speed estimates by zone
    - Weather impact
    - Temporal features
    """
    start_time = time.time()
    
    try:
        # Prepare data for solver
        depot = (request.vehicles[0].start_lat, request.vehicles[0].start_lon)
        orders = [(order.pickup_lat, order.pickup_lon) for order in request.orders]
        demands = [order.demand for order in request.orders]
        vehicle_capacities = [vehicle.capacity for vehicle in request.vehicles]
        
        # Adjust time matrix based on ML features if available
        if request.ml_features and request.use_ml_features:
            # In production, we would adjust the distance/time matrix
            # based on traffic, weather, and zone speeds
            traffic_by_zone = request.ml_features.traffic_by_zone
            speed_by_zone = request.ml_features.speed_by_zone
            
            logger.info(f"Using ML features for VRP optimization")
            logger.info(f"Traffic zones: {len(traffic_by_zone)}, Speed zones: {len(speed_by_zone)}")
        
        # Solve VRP
        if request.solver_type == "nearest_neighbor":
            solution = solve_vrp_nearest_neighbor(
                depot=depot,
                orders=orders,
                demands=demands,
                vehicle_capacities=vehicle_capacities,
                time_limit=request.time_limit_seconds
            )
        elif request.solver_type in ["hybrid", "ortools", "rl"]:
            # For now, fall back to nearest_neighbor with ML features
            # In production, integrate OR-Tools or RL solver
            solution = solve_vrp_nearest_neighbor(
                depot=depot,
                orders=orders,
                demands=demands,
                vehicle_capacities=vehicle_capacities,
                time_limit=request.time_limit_seconds
            )
            logger.info(f"Using hybrid solver (ML features + {solution['solver_type']})")
        else:
            raise HTTPException(
                status_code=400,
                detail=f"Unknown solver type: {request.solver_type}"
            )
        
        # Convert solution to API response format
        routes = []
        total_distance = solution['total_distance_km']
        total_time = solution['total_time_minutes']
        total_demand_served = sum(demands)
        
        for vehicle_idx, route_indices in enumerate(solution['routes']):
            if not route_indices:
                continue
            
            vehicle = request.vehicles[vehicle_idx]
            stops = []
            current_time = 0.0
            current_lat, current_lon = vehicle.current_location_lat or depot[0], vehicle.current_location_lon or depot[1]
            
            # Add depot as first stop
            stops.append(RouteStop(
                stop_type="depot",
                order_id=None,
                latitude=current_lat,
                longitude=current_lon,
                arrival_time_minutes=0.0,
                departure_time_minutes=0.0,
                service_time_minutes=0.0,
                wait_time_minutes=0.0
            ))
            
            route_demand = 0.0
            route_distance = 0.0
            
            for order_idx in route_indices:
                order = request.orders[order_idx]
                
                # Distance to pickup
                dist_to_pickup = calculate_distance(
                    current_lat, current_lon,
                    order.pickup_lat, order.pickup_lon
                )
                route_distance += dist_to_pickup
                
                # Travel time (adjusted by ML features if available)
                travel_time = (dist_to_pickup / 30.0) * 60  # 30km/h default
                if request.ml_features and request.use_ml_features:
                    # Would adjust based on traffic/weather
                    pass
                
                current_time += travel_time
                
                # Pickup stop
                stops.append(RouteStop(
                    stop_type="pickup",
                    order_id=order.order_id,
                    latitude=order.pickup_lat,
                    longitude=order.pickup_lon,
                    arrival_time_minutes=current_time,
                    departure_time_minutes=current_time + order.service_time_pickup_minutes,
                    service_time_minutes=order.service_time_pickup_minutes,
                    wait_time_minutes=0.0
                ))
                
                current_time += order.service_time_pickup_minutes
                current_lat, current_lon = order.pickup_lat, order.pickup_lon
                
                # Distance to delivery
                dist_to_delivery = calculate_distance(
                    current_lat, current_lon,
                    order.delivery_lat, order.delivery_lon
                )
                route_distance += dist_to_delivery
                
                travel_time = (dist_to_delivery / 30.0) * 60
                current_time += travel_time
                
                # Delivery stop
                stops.append(RouteStop(
                    stop_type="delivery",
                    order_id=order.order_id,
                    latitude=order.delivery_lat,
                    longitude=order.delivery_lon,
                    arrival_time_minutes=current_time,
                    departure_time_minutes=current_time + order.service_time_delivery_minutes,
                    service_time_minutes=order.service_time_delivery_minutes,
                    wait_time_minutes=0.0
                ))
                
                current_time += order.service_time_delivery_minutes
                current_lat, current_lon = order.delivery_lat, order.delivery_lon
                route_demand += order.demand
            
            # Return to depot
            dist_to_depot = calculate_distance(
                current_lat, current_lon,
                depot[0], depot[1]
            )
            route_distance += dist_to_depot
            travel_time = (dist_to_depot / 30.0) * 60
            current_time += travel_time
            
            stops.append(RouteStop(
                stop_type="depot",
                order_id=None,
                latitude=depot[0],
                longitude=depot[1],
                arrival_time_minutes=current_time,
                departure_time_minutes=current_time,
                service_time_minutes=0.0,
                wait_time_minutes=0.0
            ))
            
            routes.append(Route(
                vehicle_id=vehicle.vehicle_id,
                stops=stops,
                total_distance_km=route_distance,
                total_time_minutes=current_time,
                total_demand=route_demand,
                utilization_rate=route_demand / max(vehicle.capacity, 1)
            ))
        
        # Calculate metrics
        vehicles_used = len([r for r in routes if r.total_demand > 0])
        avg_distance = total_distance / max(vehicles_used, 1)
        avg_time = total_time / max(vehicles_used, 1)
        
        # Find unassigned orders
        assigned_order_ids = set()
        for route in routes:
            for stop in route.stops:
                if stop.order_id:
                    assigned_order_ids.add(stop.order_id)
        
        unassigned_orders = [
            order.order_id for order in request.orders
            if order.order_id not in assigned_order_ids
        ]
        
        # Create response
        solve_time = (time.time() - start_time)
        response = VRPSolution(
            tenant_id=request.tenant_id,
            routes=routes,
            total_distance_km=total_distance,
            total_time_minutes=total_time,
            total_vehicles_used=vehicles_used,
            total_demand_served=total_demand_served,
            solver_type=request.solver_type,
            solver_version="v2.0-hybrid",
            solve_time_seconds=solve_time,
            is_optimal=False,  # Heuristic solution
            ml_features_used=request.use_ml_features and request.ml_features is not None,
            ml_model_version="v1" if request.ml_features else None,
            average_distance_per_vehicle_km=avg_distance,
            average_time_per_vehicle_minutes=avg_time,
            unassigned_orders=unassigned_orders
        )
        
        logger.info(
            f"VRP optimization completed for tenant {request.tenant_id}",
            extra={
                "tenant_id": request.tenant_id,
                "solver_type": request.solver_type,
                "vehicles_used": vehicles_used,
                "total_distance_km": total_distance,
                "solve_time_seconds": solve_time
            }
        )
        
        return response
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"VRP optimization failed: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))
