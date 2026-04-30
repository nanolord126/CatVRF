"""
Pydantic models for CatVRF Logistics Inference API
"""

from .courier import (
    VehicleType,
    CourierStatus,
    CourierFeatures,
    ShipmentFeatures,
    AssignmentRequest,
    AssignmentScore,
    AssignmentResponse,
    BatchAssignmentRequest
)

from .pvz import (
    PVZFeatures,
    PVZScoringRequest,
    PVZScore,
    PVZScoringResponse
)

from .eta import (
    ETAPoint,
    RouteSegment,
    ETAFeatures,
    ETARequest,
    ETAResponse,
    BatchETARequest
)

from .vrp import (
    VehicleType as VRPVehicleType,
    Vehicle,
    Order,
    MLFeatures,
    VRPRequest,
    RouteStop,
    Route,
    VRPSolution
)

__all__ = [
    # Courier
    'VehicleType',
    'CourierStatus',
    'CourierFeatures',
    'ShipmentFeatures',
    'AssignmentRequest',
    'AssignmentScore',
    'AssignmentResponse',
    'BatchAssignmentRequest',
    
    # PVZ
    'PVZFeatures',
    'PVZScoringRequest',
    'PVZScore',
    'PVZScoringResponse',
    
    # ETA
    'ETAPoint',
    'RouteSegment',
    'ETAFeatures',
    'ETARequest',
    'ETAResponse',
    'BatchETARequest',
    
    # VRP
    'VRPVehicleType',
    'Vehicle',
    'Order',
    'MLFeatures',
    'VRPRequest',
    'RouteStop',
    'Route',
    'VRPSolution'
]
