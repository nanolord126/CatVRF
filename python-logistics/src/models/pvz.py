"""
Pydantic models for PVZ Scoring API
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional
from datetime import datetime


class PVZFeatures(BaseModel):
    """Features for a single PVZ (pickup point)"""
    pvz_id: str
    pvz_name: str
    latitude: float = Field(..., ge=-90, le=90)
    longitude: float = Field(..., ge=-180, le=180)
    geo_hash: str
    zone_id: str
    
    # Current state
    total_lockers: int = Field(..., ge=1)
    occupied_lockers: int = Field(..., ge=0)
    available_lockers: int = Field(..., ge=0)
    load_ratio: float = Field(..., ge=0, le=1)
    
    # Historical performance
    avg_pickup_time_minutes: Optional[float] = Field(None, ge=0)
    avg_wait_time_minutes: Optional[float] = Field(None, ge=0)
    preference_score: float = Field(0.5, ge=0, le=1)
    
    # Temporal features (forecasted)
    historical_load_avg: float = Field(0.5, ge=0, le=1)
    historical_load_p95: float = Field(0.8, ge=0, le=1)
    
    # Zone demand
    zone_demand: int = Field(0, ge=0)
    zone_couriers_available: int = Field(0, ge=0)


class PVZScoringRequest(BaseModel):
    """Request for PVZ scoring"""
    tenant_id: int
    vertical: str
    user_latitude: float = Field(..., ge=-90, le=90)
    user_longitude: float = Field(..., ge=-180, le=180)
    user_geo_hash: str
    hour_of_day: int = Field(..., ge=0, le=23)
    day_of_week: int = Field(..., ge=1, le=7)
    is_weekend: bool = False
    
    # Available PVZs
    available_pvzs: List[PVZFeatures]
    
    # Preferences
    max_distance_km: float = Field(5.0, ge=0.5, le=50)
    min_available_lockers: int = Field(1, ge=0)
    include_explanations: bool = False
    top_k: int = Field(5, ge=1, le=20)
    
    @validator('available_pvzs')
    def validate_pvzs(cls, v):
        if len(v) == 0:
            raise ValueError("At least one PVZ must be provided")
        return v


class PVZScore(BaseModel):
    """Score for a single PVZ"""
    pvz_id: str
    pvz_name: str
    score: float = Field(..., ge=0, le=1)
    distance_km: float
    estimated_arrival_time_minutes: float
    availability_score: float = Field(..., ge=0, le=1)
    preference_score: float = Field(..., ge=0, le=1)
    performance_score: float = Field(..., ge=0, le=1)
    confidence: float = Field(..., ge=0, le=1)
    explanation: Optional[dict] = None


class PVZScoringResponse(BaseModel):
    """Response from PVZ scoring API"""
    recommended_pvz: PVZScore
    alternative_pvzs: List[PVZScore]
    model_version: str
    inference_time_ms: float
    features_used: List[str]
    total_pvzs_evaluated: int
