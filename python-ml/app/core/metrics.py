"""Metrics setup for CatVRF ML Service"""
from prometheus_client import Counter, Histogram, Gauge, make_asgi_app
from fastapi import FastAPI


# Define metrics
prediction_counter = Counter(
    "ml_predictions_total",
    "Total number of ML predictions",
    ["model", "status"]
)

prediction_latency = Histogram(
    "ml_prediction_latency_seconds",
    "ML prediction latency",
    ["model"]
)

model_accuracy = Gauge(
    "ml_model_accuracy",
    "Model accuracy",
    ["model"]
)

active_connections = Gauge(
    "active_connections",
    "Number of active connections"
)


def setup_metrics(app: FastAPI):
    """Setup Prometheus metrics endpoint"""
    metrics_app = make_asgi_app()
    app.mount("/metrics", metrics_app)
