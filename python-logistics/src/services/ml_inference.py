"""
ML Model Inference Service
Handles ONNX model loading and inference for logistics models
"""

import onnxruntime as ort
import numpy as np
from typing import List, Dict, Any, Optional, Tuple
import logging
import os
from pathlib import Path
import time

logger = logging.getLogger(__name__)


class MLModel:
    """Base class for ML models"""
    
    def __init__(self, model_path: str, inference_threads: int = 4):
        self.model_path = model_path
        self.inference_threads = inference_threads
        self.session = None
        self.input_name = None
        self.output_name = None
        self.load_model()
    
    def load_model(self):
        """Load ONNX model"""
        if not os.path.exists(self.model_path):
            logger.warning(f"Model file not found: {self.model_path}, using placeholder")
            self.session = None
            return
        
        try:
            # Configure ONNX Runtime session
            so = ort.SessionOptions()
            so.intra_op_num_threads = self.inference_threads
            so.execution_mode = ort.ExecutionMode.ORT_SEQUENTIAL
            
            self.session = ort.InferenceSession(
                self.model_path,
                sess_options=so,
                providers=['CPUExecutionProvider']
            )
            
            self.input_name = self.session.get_inputs()[0].name
            self.output_name = self.session.get_outputs()[0].name
            
            logger.info(f"Loaded model from {self.model_path}")
            
        except Exception as e:
            logger.error(f"Failed to load model: {e}")
            self.session = None
    
    def predict(self, features: np.ndarray) -> np.ndarray:
        """Run inference"""
        if self.session is None:
            # Return placeholder prediction
            return np.zeros((features.shape[0], 1))
        
        try:
            start_time = time.time()
            result = self.session.run([self.output_name], {self.input_name: features})
            inference_time = (time.time() - start_time) * 1000
            
            logger.debug(f"Inference time: {inference_time:.2f}ms")
            return result[0]
            
        except Exception as e:
            logger.error(f"Inference failed: {e}")
            return np.zeros((features.shape[0], 1))


class CourierAssignmentModel(MLModel):
    """GNN-based courier assignment model"""
    
    def __init__(self, model_path: str, inference_threads: int = 4):
        super().__init__(model_path, inference_threads)
        self.feature_names = [
            'distance_to_pickup',
            'courier_velocity',
            'acceptance_rate',
            'zone_speed_avg',
            'zone_demand',
            'hour_of_day',
            'day_of_week',
            'is_weekend',
            'traffic_level',
            'weather_impact',
            'vehicle_type_bike',
            'vehicle_type_car',
            'vehicle_type_taxi'
        ]
    
    def prepare_features(
        self,
        shipment: Dict[str, Any],
        courier: Dict[str, Any],
        zone_features: Optional[Dict[str, Any]] = None
    ) -> np.ndarray:
        """Prepare features for a single courier-shipment pair"""
        features = np.zeros((1, len(self.feature_names)))
        
        # Distance to pickup (would calculate with geodistance)
        features[0, 0] = courier.get('distance_to_pickup_km', 1.0)
        
        # Courier features
        features[0, 1] = courier.get('velocity_kmh', 30.0)
        features[0, 2] = courier.get('acceptance_rate', 0.9)
        
        # Zone features
        if zone_features:
            features[0, 3] = zone_features.get('avg_speed_kmh', 30.0)
            features[0, 4] = zone_features.get('order_count', 10) / 100.0  # Normalized
        else:
            features[0, 3] = 30.0
            features[0, 4] = 0.1
        
        # Temporal features
        features[0, 5] = shipment.get('hour_of_day', 12) / 24.0
        features[0, 6] = shipment.get('day_of_week', 1) / 7.0
        features[0, 7] = 1.0 if shipment.get('is_weekend', False) else 0.0
        
        # Context features
        features[0, 8] = shipment.get('traffic_level', 1) / 4.0
        features[0, 9] = 1.0 if shipment.get('weather_condition') == 'clear' else 0.8
        
        # Vehicle type one-hot
        vehicle_type = courier.get('vehicle_type', 'courier_car')
        if vehicle_type == 'courier_bike':
            features[0, 10] = 1.0
        elif vehicle_type == 'courier_car':
            features[0, 11] = 1.0
        elif vehicle_type == 'taxi':
            features[0, 12] = 1.0
        
        return features
    
    def predict_batch(
        self,
        shipment: Dict[str, Any],
        couriers: List[Dict[str, Any]],
        zone_features: Optional[Dict[str, Any]] = None
    ) -> List[float]:
        """Predict scores for multiple couriers"""
        features_list = []
        
        for courier in couriers:
            features = self.prepare_features(shipment, courier, zone_features)
            features_list.append(features)
        
        # Stack features
        all_features = np.vstack(features_list)
        
        # Run inference
        scores = self.predict(all_features)
        
        # Sigmoid to get probabilities
        scores = 1 / (1 + np.exp(-scores))
        
        return scores.flatten().tolist()


class PVZScoringModel(MLModel):
    """PVZ scoring model with temporal features"""
    
    def __init__(self, model_path: str, inference_threads: int = 4):
        super().__init__(model_path, inference_threads)
        self.feature_names = [
            'distance_km',
            'load_ratio',
            'available_lockers_ratio',
            'avg_pickup_time',
            'preference_score',
            'historical_load_avg',
            'historical_load_p95',
            'zone_demand',
            'hour_of_day',
            'day_of_week',
            'is_weekend'
        ]
    
    def prepare_features(
        self,
        pvz: Dict[str, Any],
        user_location: Tuple[float, float],
        hour_of_day: int,
        day_of_week: int,
        is_weekend: bool,
        zone_features: Optional[Dict[str, Any]] = None
    ) -> np.ndarray:
        """Prepare features for PVZ scoring"""
        features = np.zeros((1, len(self.feature_names)))
        
        # Distance (placeholder - would calculate with geodistance)
        features[0, 0] = pvz.get('distance_km', 2.0) / 10.0  # Normalized
        
        # Load features
        features[0, 1] = pvz.get('load_ratio', 0.5)
        features[0, 2] = pvz.get('available_lockers', 10) / max(pvz.get('total_lockers', 20), 1)
        
        # Performance features
        features[0, 3] = (pvz.get('avg_pickup_time_minutes', 10) or 10) / 60.0  # Normalized to hours
        features[0, 4] = pvz.get('preference_score', 0.5)
        
        # Historical features
        features[0, 5] = pvz.get('historical_load_avg', 0.5)
        features[0, 6] = pvz.get('historical_load_p95', 0.8)
        
        # Zone features
        if zone_features:
            features[0, 7] = zone_features.get('order_count', 10) / 100.0
        else:
            features[0, 7] = 0.1
        
        # Temporal features
        features[0, 8] = hour_of_day / 24.0
        features[0, 9] = day_of_week / 7.0
        features[0, 10] = 1.0 if is_weekend else 0.0
        
        return features
    
    def predict_batch(
        self,
        pvzs: List[Dict[str, Any]],
        user_location: Tuple[float, float],
        hour_of_day: int,
        day_of_week: int,
        is_weekend: bool,
        zone_features: Optional[Dict[str, Any]] = None
    ) -> List[float]:
        """Predict scores for multiple PVZs"""
        features_list = []
        
        for pvz in pvzs:
            features = self.prepare_features(
                pvz, user_location, hour_of_day, day_of_week, is_weekend, zone_features
            )
            features_list.append(features)
        
        all_features = np.vstack(features_list)
        scores = self.predict(all_features)
        
        # Sigmoid
        scores = 1 / (1 + np.exp(-scores))
        
        return scores.flatten().tolist()


class ETAPredictionModel(MLModel):
    """Hybrid ETA prediction model (LightGBM + Neural Network)"""
    
    def __init__(self, model_path: str, inference_threads: int = 4):
        super().__init__(model_path, inference_threads)
        self.feature_names = [
            'distance_km',
            'hour_of_day',
            'day_of_week',
            'is_weekend',
            'is_holiday',
            'traffic_level',
            'weather_impact',
            'pickup_zone_speed',
            'delivery_zone_speed',
            'vehicle_type',
            'courier_acceptance_rate',
            'historical_eta_error'
        ]
    
    def prepare_features(
        self,
        features: Dict[str, Any],
        pickup_zone_speed: Optional[float] = None,
        delivery_zone_speed: Optional[float] = None
    ) -> np.ndarray:
        """Prepare features for ETA prediction"""
        feature_array = np.zeros((1, len(self.feature_names)))
        
        feature_array[0, 0] = features.get('distance_km', 5.0)
        feature_array[0, 1] = features.get('hour_of_day', 12) / 24.0
        feature_array[0, 2] = features.get('day_of_week', 1) / 7.0
        feature_array[0, 3] = 1.0 if features.get('is_weekend', False) else 0.0
        feature_array[0, 4] = 1.0 if features.get('is_holiday', False) else 0.0
        feature_array[0, 5] = features.get('traffic_level', 1) / 4.0
        
        # Weather impact
        weather = features.get('weather_condition', 'clear')
        if weather == 'clear':
            feature_array[0, 6] = 1.0
        elif weather in ['rain', 'heavy_rain']:
            feature_array[0, 6] = 0.8
        elif weather in ['snow', 'blizzard']:
            feature_array[0, 6] = 0.5
        else:
            feature_array[0, 6] = 0.9
        
        # Zone speeds
        feature_array[0, 7] = (pickup_zone_speed or 30.0) / 50.0
        feature_array[0, 8] = (delivery_zone_speed or 30.0) / 50.0
        
        # Vehicle type
        vehicle_type = features.get('vehicle_type', 'courier_car')
        if vehicle_type == 'courier_bike':
            feature_array[0, 9] = 0.7
        elif vehicle_type == 'courier_car':
            feature_array[0, 9] = 1.0
        elif vehicle_type == 'taxi':
            feature_array[0, 9] = 1.2
        else:
            feature_array[0, 9] = 1.0
        
        # Courier acceptance rate
        feature_array[0, 10] = features.get('courier_acceptance_rate', 0.9)
        
        # Historical error
        feature_array[0, 11] = (features.get('avg_eta_error_pickup_zone_last_24h') or 5.0) / 30.0
        
        return feature_array
    
    def predict(
        self,
        features: Dict[str, Any],
        pickup_zone_speed: Optional[float] = None,
        delivery_zone_speed: Optional[float] = None
    ) -> float:
        """Predict ETA for a single shipment"""
        feature_array = self.prepare_features(
            features, pickup_zone_speed, delivery_zone_speed
        )
        
        eta_minutes = self.predict(feature_array)[0, 0]
        
        # Ensure positive ETA
        eta_minutes = max(eta_minutes, 1.0)
        
        return eta_minutes


class MLInferenceService:
    """Service for ML model inference"""
    
    def __init__(self, models_dir: str, inference_threads: int = 4):
        self.models_dir = Path(models_dir)
        self.inference_threads = inference_threads
        os.makedirs(models_dir, exist_ok=True)
        
        # Load models
        self.courier_assignment_model = CourierAssignmentModel(
            str(self.models_dir / "courier_assignment_v1.onnx"),
            inference_threads
        )
        
        self.pvz_scoring_model = PVZScoringModel(
            str(self.models_dir / "pvz_scoring_v1.onnx"),
            inference_threads
        )
        
        self.eta_prediction_model = ETAPredictionModel(
            str(self.models_dir / "eta_prediction_v1.onnx"),
            inference_threads
        )
        
        logger.info("ML Inference Service initialized")
    
    def get_courier_assignment_model(self) -> CourierAssignmentModel:
        return self.courier_assignment_model
    
    def get_pvz_scoring_model(self) -> PVZScoringModel:
        return self.pvz_scoring_model
    
    def get_eta_prediction_model(self) -> ETAPredictionModel:
        return self.eta_prediction_model
