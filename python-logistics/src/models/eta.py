"""
Pydantic models for ETA Prediction API
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional, Tuple


class ETAPoint(BaseModel):
    """A single point in the route"""
    latitude: float = Field(..., ge=-90, le=90)
    longitude: float = Field(..., ge=-180, le=180)
    stop_type: str = Field(..., description="pickup, delivery, waypoint")
    sequence: int = Field(..., ge=0)


class RouteSegment(BaseModel):
    """A segment of the route"""
    from_point: ETAPoint
    to_point: ETAPoint
    distance_km: float = Field(..., ge=0)
    estimated_time_minutes: float = Field(..., ge=0)
    traffic_level: int = Field(1, ge=1, le=4)
    weather_impact: float = Field(1.0, ge=0.5, le=2.0)


class ETAFeatures(BaseModel):
    """Features for ETA prediction"""
    tenant_id: int
    vertical: str
    shipment_id: str
    
    # Route points
    pickup_lat: float = Field(..., ge=-90, le=90)
    pickup_lon: float = Field(..., ge=-180, le=180)
    delivery_lat: float = Field(..., ge=-90, le=90)
    delivery_lon: float = Field(..., ge=-180, le=180)
    distance_km: float = Field(..., ge=0)
    
    # Temporal features
    hour_of_day: int = Field(..., ge=0, le=23)
    day_of_week: int = Field(..., ge=1, le=7)
    is_weekend: bool = False
    is_holiday: bool = False
    
    # Context features
    weather_condition: str = "clear"
    temperature_c: Optional[float] = None
    traffic_level: int = Field(1, ge=1, le=4)
    
    # Zone features
    zone_pickup: str
    zone_delivery: str
    
    # Courier features
    vehicle_type: str = "courier_car"
    courier_acceptance_rate: Optional[float] = Field(None, ge=0, le=1)
    
    # Historical features (from feature store)
    avg_speed_pickup_zone_last_24h: Optional[float] = Field(None, ge=0)
    avg_eta_error_pickup_zone_last_24h: Optional[float] = Field(None, ge=0)
    demand_pickup_zone_last_24h: Optional[int] = Field(None, ge=0)


class ETARequest(BaseModel):
    """Request for ETA prediction"""
    features: ETAFeatures
    include_route_breakdown: bool = False
    include_confidence_interval: bool = False
    confidence_level: float = Field(0.95, ge=0.8, le=0.99)


class ETAResponse(BaseModel):
    """Response from ETA prediction API"""
    shipment_id: str
    estimated_time_minutes: float
    confidence_interval: Optional[Tuple[float, float]] = None  # (lower, upper)
    route_segments: Optional[List[RouteSegment]] = None
    
    # Model info
    model_version: str
    model_type: str = Field(..., description="lightgbm, neural_network, hybrid")
    inference_time_ms: float
    features_used: List[str]
    
    # Additional context
    traffic_impact_factor: float = Field(1.0, ge=0.5, le=2.0)
    weather_impact_factor: float = Field(1.0, ge=0.5, le=2.0)


class BatchETARequest(BaseModel):
    """Batch ETA prediction for multiple shipments"""
    shipments: List[ETAFeatures]
    include_route_breakdown: bool = False
    include_confidence_interval: bool = False
