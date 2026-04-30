"""
API endpoints module for CatVRF Logistics Inference API
"""

from .courier import router as courier_router
from .pvz import router as pvz_router
from .eta import router as eta_router
from .vrp import router as vrp_router
from .agent import router as agent_router

__all__ = ['courier_router', 'pvz_router', 'eta_router', 'vrp_router', 'agent_router']
