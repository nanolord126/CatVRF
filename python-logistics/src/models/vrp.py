"""
Pydantic models for Vehicle Routing Problem (VRP) API
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional, Tuple, Dict
from datetime import datetime
from enum import Enum


class VehicleType(str, Enum):
    COURIER_BIKE = "courier_bike"
    COURIER_CAR = "courier_car"
    TAXI = "taxi"
    TRUCK = "truck"


class Vehicle(BaseModel):
    """A vehicle in the fleet"""
    vehicle_id: str
    vehicle_type: VehicleType
    start_lat: float = Field(..., ge=-90, le=90)
    start_lon: float = Field(..., ge=-180, le=180)
    capacity: float = Field(..., ge=0)
    current_location_lat: Optional[float] = Field(None, ge=-90, le=90)
    current_location_lon: Optional[float] = Field(None, ge=-180, le=180)
    max_route_duration_minutes: Optional[float] = Field(None, ge=0)


class Order(BaseModel):
    """An order to be delivered"""
    order_id: str
    pickup_lat: float = Field(..., ge=-90, le=90)
    pickup_lon: float = Field(..., ge=-180, le=180)
    delivery_lat: float = Field(..., ge=-90, le=90)
    delivery_lon: float = Field(..., ge=-180, le=180)
    demand: float = Field(..., ge=0)
    pickup_time_window: Optional[Tuple[int, int]] = None  # (start_minute, end_minute)
    delivery_time_window: Optional[Tuple[int, int]] = None  # (start_minute, end_minute)
    service_time_pickup_minutes: float = Field(5.0, ge=0)
    service_time_delivery_minutes: float = Field(5.0, ge=0)
    priority: int = Field(1, ge=1, le=5)


class MLFeatures(BaseModel):
    """ML features for route optimization"""
    traffic_by_zone: Dict[str, float] = Field(default_factory=dict)  # zone_id -> traffic_level (1-4)
    speed_by_zone: Dict[str, float] = Field(default_factory=dict)  # zone_id -> avg_speed_kmh
    weather_condition: str = "clear"
    hour_of_day: int = Field(..., ge=0, le=23)
    day_of_week: int = Field(..., ge=1, le=7)
    is_weekend: bool = False


class VRPRequest(BaseModel):
    """Request for VRP optimization"""
    tenant_id: int
    vertical: str
    
    # Fleet
    vehicles: List[Vehicle]
    
    # Orders
    orders: List[Order]
    
    # ML features
    ml_features: Optional[MLFeatures] = None
    
    # Optimization parameters
    time_limit_seconds: int = Field(30, ge=1, le=300)
    optimization_objective: str = Field("minimize_total_distance", description="minimize_total_distance, minimize_total_time, minimize_vehicles")
    solver_type: str = Field("hybrid", description="nearest_neighbor, ortools, hybrid, rl")
    use_ml_features: bool = True
    
    @validator('vehicles')
    def validate_vehicles(cls, v):
        if len(v) == 0:
            raise ValueError("At least one vehicle must be provided")
        return v
    
    @validator('orders')
    def validate_orders(cls, v):
        if len(v) == 0:
            raise ValueError("At least one order must be provided")
        return v


class RouteStop(BaseModel):
    """A stop in a route"""
    stop_type: str = Field(..., description="depot, pickup, delivery")
    order_id: Optional[str] = None
    latitude: float
    longitude: float
    arrival_time_minutes: float
    departure_time_minutes: float
    service_time_minutes: float
    wait_time_minutes: float = 0.0


class Route(BaseModel):
    """A route for a single vehicle"""
    vehicle_id: str
    stops: List[RouteStop]
    total_distance_km: float
    total_time_minutes: float
    total_demand: float
    utilization_rate: float = Field(..., ge=0, le=1)


class VRPSolution(BaseModel):
    """Response from VRP optimization"""
    tenant_id: int
    routes: List[Route]
    total_distance_km: float
    total_time_minutes: float
    total_vehicles_used: int
    total_demand_served: float
    
    # Solver info
    solver_type: str
    solver_version: str
    solve_time_seconds: float
    is_optimal: bool = False
    
    # ML info
    ml_features_used: bool = False
    ml_model_version: Optional[str] = None
    
    # Additional metrics
    average_distance_per_vehicle_km: float
    average_time_per_vehicle_minutes: float
    unassigned_orders: List[str] = Field(default_factory=list)
