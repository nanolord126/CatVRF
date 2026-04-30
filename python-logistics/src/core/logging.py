"""
Structured logging configuration for CatVRF Logistics Inference Service
"""

import logging
import sys
import json
from datetime import datetime
from typing import Any, Dict
from pythonjsonlogger import jsonlogger


class ContextFilter(logging.Filter):
    """Adds context information to log records"""
    
    def filter(self, record: logging.LogRecord) -> bool:
        record.timestamp = datetime.utcnow().isoformat()
        record.app_name = "catvrf-logistics-inference"
        return True


def setup_logging(settings) -> logging.Logger:
    """Configure structured logging for the application"""
    
    logger = logging.getLogger()
    logger.setLevel(getattr(logging, settings.log_level.upper()))
    
    # Remove existing handlers
    logger.handlers.clear()
    
    # Context filter
    context_filter = ContextFilter()
    logger.addFilter(context_filter)
    
    # Console handler
    if settings.log_format == "json":
        formatter = jsonlogger.JsonFormatter(
            '%(timestamp)s %(level)s %(name)s %(message)s',
            timestamp=True
        )
    else:
        formatter = logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        )
    
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    # File handler
    if settings.log_to_file:
        import os
        os.makedirs(os.path.dirname(settings.log_file_path), exist_ok=True)
        
        file_handler = logging.FileHandler(settings.log_file_path)
        file_handler.setFormatter(formatter)
        logger.addHandler(file_handler)
    
    return logger


def get_logger(name: str) -> logging.Logger:
    """Get a logger with the specified name"""
    return logging.getLogger(name)
