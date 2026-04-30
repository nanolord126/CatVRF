"""
Pydantic models for Courier Assignment API
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional, Tuple
from datetime import datetime
from enum import Enum


class VehicleType(str, Enum):
    COURIER_BIKE = "courier_bike"
    COURIER_CAR = "courier_car"
    TAXI = "taxi"
    TRUCK = "truck"


class CourierStatus(str, Enum):
    IDLE = "idle"
    MOVING_TO_PICKUP = "moving_to_pickup"
    AT_PICKUP = "at_pickup"
    MOVING_TO_DELIVERY = "moving_to_delivery"
    AT_DELIVERY = "at_delivery"
    RETURNING = "returning"


class CourierFeatures(BaseModel):
    """Features for a single courier"""
    courier_id: str
    vehicle_type: VehicleType
    latitude: float = Field(..., ge=-90, le=90)
    longitude: float = Field(..., ge=-180, le=180)
    velocity_kmh: Optional[float] = Field(None, ge=0)
    is_available: bool = True
    current_status: CourierStatus = CourierStatus.IDLE
    acceptance_rate: float = Field(..., ge=0, le=1)
    current_shipment_id: Optional[str] = None
    zone_id: Optional[str] = None


class ShipmentFeatures(BaseModel):
    """Features for a shipment to be assigned"""
    shipment_id: str
    pickup_lat: float = Field(..., ge=-90, le=90)
    pickup_lon: float = Field(..., ge=-180, le=180)
    delivery_lat: float = Field(..., ge=-90, le=90)
    delivery_lon: float = Field(..., ge=-180, le=180)
    distance_km: float = Field(..., ge=0)
    hour_of_day: int = Field(..., ge=0, le=23)
    day_of_week: int = Field(..., ge=1, le=7)
    is_weekend: bool = False
    is_holiday: bool = False
    weather_condition: str = "clear"
    temperature_c: Optional[float] = None
    traffic_level: int = Field(1, ge=1, le=4)
    zone_pickup: str
    zone_delivery: str
    priority: int = Field(1, ge=1, le=5)  # 1=lowest, 5=highest


class AssignmentRequest(BaseModel):
    """Request for courier assignment prediction"""
    tenant_id: int
    vertical: str
    shipment: ShipmentFeatures
    available_couriers: List[CourierFeatures]
    include_explanations: bool = False
    top_k: int = Field(3, ge=1, le=10)
    
    @validator('available_couriers')
    def validate_couriers(cls, v):
        if len(v) == 0:
            raise ValueError("At least one courier must be provided")
        return v


class AssignmentScore(BaseModel):
    """Score for a single courier-shipment pair"""
    courier_id: str
    score: float = Field(..., ge=0, le=1)
    estimated_pickup_time_minutes: float
    estimated_delivery_time_minutes: float
    distance_to_pickup_km: float
    confidence: float = Field(..., ge=0, le=1)
    explanation: Optional[dict] = None


class AssignmentResponse(BaseModel):
    """Response from courier assignment API"""
    shipment_id: str
    recommended_courier: str
    alternative_couriers: List[AssignmentScore]
    model_version: str
    inference_time_ms: float
    features_used: List[str]
    explanation: Optional[dict] = None


class BatchAssignmentRequest(BaseModel):
    """Batch assignment for multiple shipments"""
    tenant_id: int
    vertical: str
    shipments: List[ShipmentFeatures]
    available_couriers: List[CourierFeatures]
    optimization_objective: str = Field("minimize_total_time", description="minimize_total_time, maximize_acceptance_rate, balance_workload")
    include_explanations: bool = False
