"""Configuration for CatVRF ML Service"""
from pydantic_settings import BaseSettings
from typing import List


class Settings(BaseSettings):
    """Application settings"""
    
    # API
    HOST: str = "0.0.0.0"
    PORT: int = 8000
    RELOAD: bool = True
    LOG_LEVEL: str = "info"
    ALLOWED_ORIGINS: List[str] = ["*"]
    
    # ClickHouse
    CLICKHOUSE_HOST: str = "localhost"
    CLICKHOUSE_PORT: int = 8123
    CLICKHOUSE_USER: str = "default"
    CLICKHOUSE_PASSWORD: str = ""
    CLICKHOUSE_DATABASE: str = "catvrf"
    
    # Redis
    REDIS_HOST: str = "localhost"
    REDIS_PORT: int = 6379
    REDIS_PASSWORD: str = ""
    REDIS_DB: int = 0
    
    # Models
    MODELS_PATH: str = "./models"
    COURIER_ASSIGNMENT_MODEL: str = "courier_assignment_v1.joblib"
    PVZ_SCORING_MODEL: str = "pvz_scoring_v1.joblib"
    ETA_PREDICTION_MODEL: str = "eta_prediction_v1.joblib"
    
    # External APIs
    WEATHER_API_KEY: str = ""
    TRAFFIC_API_KEY: str = ""
    
    # Agent AI
    LLM_API_KEY: str = ""
    LLM_API_URL: str = ""
    LLM_MODEL: str = "gpt-4"
    
    class Config:
        env_file = ".env"
        case_sensitive = True


settings = Settings()
