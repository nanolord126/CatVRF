#!/usr/bin/env python3
"""
CLV Model Inference Script

Fast inference script for CLV prediction using trained XGBoost model.
Designed to be called from Laravel via subprocess or HTTP endpoint.

Usage:
    echo '{"features": {...}}' | python infer_clv.py
    python infer_clv.py '{"features": {...}}'

Requirements:
    pip install xgboost joblib
"""

import json
import sys
from pathlib import Path

import joblib
import numpy as np


def load_model(model_path: str = None):
    """Load trained CLV model."""
    if model_path is None:
        model_path = Path(__file__).parent.parent / 'storage/app/ml_models/clv/current_model.joblib'
    
    try:
        artifacts = joblib.load(model_path)
        return artifacts['model'], artifacts['feature_columns'], artifacts.get('version', 'unknown')
    except Exception as e:
        print(f"Error loading model: {e}", file=sys.stderr)
        return None, None, None


def predict(features: dict, model, feature_columns: list, model_version: str) -> dict:
    """Run prediction on feature vector."""
    # Prepare input array in correct feature order
    feature_array = []
    for col in feature_columns:
        feature_array.append(features.get(col, 0))
    
    X = np.array([feature_array])
    
    # Get prediction
    prediction = model.predict(X)[0]
    
    # Calculate churn probability (simplified)
    recency_days = features.get('recency_days', 365)
    churn_prob = min(0.95, recency_days / 365)
    
    # Calculate confidence based on feature completeness
    feature_completeness = sum(1 for v in features.values() if v is not None) / len(features)
    confidence = 0.5 + (feature_completeness * 0.3)
    
    return {
        'clv_180d': float(max(0, prediction)),
        'clv_365d': float(max(0, prediction * 2)),
        'churn_prob': float(churn_prob),
        'confidence': float(min(0.95, confidence)),
        'model_version': model_version,
    }


def main():
    # Load input
    if len(sys.argv) > 1:
        input_data = json.loads(sys.argv[1])
    else:
        input_data = json.loads(sys.stdin.read())
    
    features = input_data.get('features', input_data)
    
    # Load model
    model, feature_columns, model_version = load_model()
    
    if model is None:
        # Return heuristic prediction if model not available
        monetary_180d = features.get('monetary_180d', 0)
        frequency_180d = features.get('frequency_180d', 0)
        recency_days = features.get('recency_days', 365)
        
        base_clv = monetary_180d
        frequency_multiplier = 1 + min(0.5, frequency_180d / 10)
        recency_multiplier = max(0.5, 1 - (recency_days / 365))
        return_multiplier = max(0.5, 1 - (features.get('return_rate', 0) / 100))
        
        predicted_clv = base_clv * frequency_multiplier * recency_multiplier * return_multiplier
        churn_prob = min(0.95, recency_days / 365)
        
        result = {
            'clv_180d': round(predicted_clv, 2),
            'clv_365d': round(predicted_clv * 2, 2),
            'churn_prob': round(churn_prob, 4),
            'confidence': 0.5,
            'model_version': 'heuristic_v1',
        }
    else:
        # Run ML prediction
        result = predict(features, model, feature_columns, model_version)
    
    # Output result
    print(json.dumps(result))


if __name__ == '__main__':
    main()
