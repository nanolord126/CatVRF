"""
Core configuration for CatVRF Logistics Inference Service
Environment-based configuration with validation
"""

from pydantic_settings import BaseSettings
from typing import Optional
import os


class Settings(BaseSettings):
    """Application settings loaded from environment variables"""
    
    # Application
    app_name: str = "CatVRF Logistics Inference API"
    app_version: str = "2.0.0"
    debug: bool = False
    environment: str = "production"  # development, staging, production
    
    # Server
    host: str = "0.0.0.0"
    port: int = 8000
    workers: int = 4
    
    # ClickHouse Feature Store
    clickhouse_host: str = "localhost"
    clickhouse_port: int = 8123
    clickhouse_database: str = "default"
    clickhouse_user: str = "default"
    clickhouse_password: str = ""
    
    # Redis (for Laravel integration)
    redis_host: str = "localhost"
    redis_port: int = 6379
    redis_password: Optional[str] = None
    redis_db: int = 0
    redis_queue_key: str = "logistics:inference:queue"
    redis_result_key_prefix: str = "logistics:inference:result:"
    
    # ML Models
    models_dir: str = "/app/models"
    courier_assignment_model: str = "courier_assignment_v1.onnx"
    pvz_scoring_model: str = "pvz_scoring_v1.onnx"
    eta_prediction_model: str = "eta_prediction_v1.onnx"
    demand_forecast_model: str = "demand_forecast_v1.onnx"
    
    # Model Settings
    onnx_inference_threads: int = 4
    enable_model_cache: bool = True
    
    # External APIs
    yandex_api_key: Optional[str] = None
    two_gis_api_key: Optional[str] = None
    weather_api_key: Optional[str] = None
    
    # Agentic AI
    enable_agent: bool = True
    agent_llm_provider: str = "openai"  # openai, anthropic, grok
    agent_llm_model: str = "gpt-4-turbo"
    agent_llm_api_key: Optional[str] = None
    agent_max_iterations: int = 10
    agent_timeout_seconds: int = 300
    
    # WebSocket (for real-time notifications)
    websocket_enabled: bool = True
    websocket_url: str = "ws://localhost:6001"
    
    # Logging
    log_level: str = "INFO"
    log_format: str = "json"  # json, text
    log_to_file: bool = True
    log_file_path: str = "/app/logs/inference.log"
    
    # Monitoring
    enable_prometheus: bool = True
    prometheus_port: int = 9090
    enable_tracing: bool = True
    
    # Rate Limiting
    rate_limit_enabled: bool = True
    rate_limit_requests_per_minute: int = 100
    
    # Circuit Breaker
    circuit_breaker_enabled: bool = True
    circuit_breaker_failure_threshold: int = 5
    circuit_breaker_recovery_timeout: int = 60
    
    class Config:
        env_file = ".env"
        case_sensitive = False


# Global settings instance
settings = Settings()
