"""
Core module for CatVRF Logistics Inference Service
"""

from .config import settings
from .logging import setup_logging, get_logger
from .middleware import RequestContextMiddleware, ErrorHandlingMiddleware

__all__ = [
    'settings',
    'setup_logging',
    'get_logger',
    'RequestContextMiddleware',
    'ErrorHandlingMiddleware'
]
