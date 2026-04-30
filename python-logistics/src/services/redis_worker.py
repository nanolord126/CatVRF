"""
Redis Queue Worker for Laravel Integration
Processes inference requests from Laravel via Redis Queue
"""

import redis
import json
import logging
from typing import Dict, Any, Optional
from datetime import datetime
import asyncio
import threading

from ..core.config import settings
from ..core.logging import get_logger
from .feature_store import FeatureStoreService
from .ml_inference import MLInferenceService

logger = get_logger(__name__)


class RedisQueueWorker:
    """
    Redis Queue Worker for processing Laravel inference requests
    
    Listens to Redis queue and processes requests:
    - courier_assignment
    - pvz_scoring
    - eta_prediction
    - vrp_optimization
    - agent_cycle
    - agent_status
    - agent_configure
    """
    
    def __init__(
        self,
        feature_store: FeatureStoreService,
        ml_service: MLInferenceService,
        redis_client: Optional[redis.Redis] = None
    ):
        self.feature_store = feature_store
        self.ml_service = ml_service
        self.redis_client = redis_client or redis.Redis(
            host=settings.redis_host,
            port=settings.redis_port,
            password=settings.redis_password,
            db=settings.redis_db,
            decode_responses=True
        )
        self.queue_key = settings.redis_queue_key
        self.result_prefix = settings.redis_result_key_prefix
        self.running = False
        self.worker_thread = None
        
        logger.info("Redis Queue Worker initialized", extra={"queue_key": self.queue_key})
    
    def start(self):
        """Start the worker in a background thread"""
        if self.running:
            logger.warning("Worker already running")
            return
        
        self.running = True
        self.worker_thread = threading.Thread(target=self._run_loop, daemon=True)
        self.worker_thread.start()
        logger.info("Redis Queue Worker started")
    
    def stop(self):
        """Stop the worker"""
        self.running = False
        if self.worker_thread:
            self.worker_thread.join(timeout=5)
        logger.info("Redis Queue Worker stopped")
    
    def _run_loop(self):
        """Main worker loop"""
        logger.info("Worker loop started")
        
        while self.running:
            try:
                # Blocking pop from queue (timeout 1 second)
                result = self.redis_client.blpop(self.queue_key, timeout=1)
                
                if result is None:
                    continue
                
                queue_name, message = result
                self._process_message(message)
                
            except Exception as e:
                logger.error(f"Error in worker loop: {e}", exc_info=True)
                # Sleep briefly to avoid tight error loop
                import time
                time.sleep(0.1)
    
    def _process_message(self, message: str):
        """Process a single message from the queue"""
        try:
            payload = json.loads(message)
            request_id = payload.get('request_id')
            action = payload.get('action')
            tenant_id = payload.get('tenant_id')
            data = payload.get('data', {})
            
            logger.info(
                "Processing inference request",
                extra={
                    "request_id": request_id,
                    "action": action,
                    "tenant_id": tenant_id
                }
            )
            
            # Process based on action
            result = self._handle_action(action, tenant_id, data)
            
            # Send result back to Redis
            result_key = self.result_prefix + request_id
            self.redis_client.setex(
                result_key,
                300,  # 5 minute TTL
                json.dumps(result)
            )
            
            logger.info(
                "Inference request completed",
                extra={
                    "request_id": request_id,
                    "action": action,
                    "success": result.get('success', False)
                }
            )
            
        except json.JSONDecodeError as e:
            logger.error(f"Failed to decode message: {e}")
        except Exception as e:
            logger.error(f"Failed to process message: {e}", exc_info=True)
    
    def _handle_action(self, action: str, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle different inference actions"""
        
        try:
            if action == 'courier_assignment':
                return self._handle_courier_assignment(tenant_id, data)
            
            elif action == 'pvz_scoring':
                return self._handle_pvz_scoring(tenant_id, data)
            
            elif action == 'eta_prediction':
                return self._handle_eta_prediction(tenant_id, data)
            
            elif action == 'vrp_optimization':
                return self._handle_vrp_optimization(tenant_id, data)
            
            elif action == 'agent_cycle':
                return self._handle_agent_cycle(tenant_id, data)
            
            elif action == 'agent_status':
                return self._handle_agent_status(tenant_id, data)
            
            elif action == 'agent_configure':
                return self._handle_agent_configure(tenant_id, data)
            
            else:
                return {
                    'success': False,
                    'error': f'Unknown action: {action}'
                }
        
        except Exception as e:
            logger.error(f"Error handling action {action}: {e}", exc_info=True)
            return {
                'success': False,
                'error': str(e)
            }
    
    def _handle_courier_assignment(self, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle courier assignment request"""
        from .ml_inference import CourierAssignmentModel
        
        shipment_id = data.get('shipment_id')
        courier_ids = data.get('courier_ids', [])
        
        # Get shipment details from feature store
        shipment_features = self.feature_store.get_courier_assignment_features(
            tenant_id, shipment_id, courier_ids
        )
        
        if not shipment_features:
            return {
                'success': False,
                'error': 'Shipment features not found'
            }
        
        # Get ML model
        model = self.ml_service.get_courier_assignment_model()
        
        # Prepare shipment data
        shipment = {
            'shipment_id': shipment_id,
            'pickup_lat': data.get('pickup_lat'),
            'pickup_lon': data.get('pickup_lon'),
            'delivery_lat': data.get('delivery_lat'),
            'delivery_lon': data.get('delivery_lon'),
        }
        
        # Score couriers
        scores = model.predict_batch(
            shipment=shipment,
            couriers=shipment_features,
            zone_features=None
        )
        
        # Find best courier
        best_idx = int(scores.index(max(scores)))
        best_courier = shipment_features[best_idx]['courier_id']
        
        return {
            'success': True,
            'data': {
                'best_courier_id': best_courier,
                'score': float(scores[best_idx]),
                'all_scores': [
                    {'courier_id': cid, 'score': float(score)}
                    for cid, score in zip(courier_ids, scores)
                ]
            }
        }
    
    def _handle_pvz_scoring(self, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle PVZ scoring request"""
        user_lat = data.get('user_lat')
        user_lon = data.get('user_lon')
        pvz_ids = data.get('pvz_ids', [])
        
        # Get PVZ features from feature store
        # This would be implemented based on actual feature store queries
        # Placeholder for now
        
        return {
            'success': True,
            'data': {
                'pvz_scores': [
                    {'pvz_id': pvz_id, 'score': 0.8}
                    for pvz_id in pvz_ids
                ]
            }
        }
    
    def _handle_eta_prediction(self, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle ETA prediction request"""
        features = {
            'pickup_lat': data.get('pickup_lat'),
            'pickup_lon': data.get('pickup_lon'),
            'delivery_lat': data.get('delivery_lat'),
            'delivery_lon': data.get('delivery_lon'),
            'distance_km': data.get('distance_km'),
            'vehicle_type': data.get('vehicle_type'),
            'hour_of_day': datetime.now().hour,
            'day_of_week': datetime.now().weekday(),
            'is_weekend': datetime.now().weekday() >= 5,
            'is_holiday': False,
            'traffic_level': 2,
            'weather_condition': 'clear',
        }
        
        model = self.ml_service.get_eta_prediction_model()
        eta = model.predict(features)
        
        return {
            'success': True,
            'data': {
                'eta_minutes': float(eta),
                'confidence': 0.85
            }
        }
    
    def _handle_vrp_optimization(self, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle VRP optimization request"""
        # Placeholder - would integrate with VRP solver
        return {
            'success': True,
            'data': {
                'optimized_route': [],
                'total_distance_km': 10.5,
                'total_time_minutes': 45
            }
        }
    
    def _handle_agent_cycle(self, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle agent cycle request"""
        from ..agent.llm_agent import LLMLogisticsAgent
        
        zone_id = data.get('zone_id')
        require_approval = data.get('require_approval', False)
        use_llm = data.get('use_llm', True)
        
        agent = LLMLogisticsAgent(
            feature_store=self.feature_store,
            ml_service=self.ml_service,
            enable_autonomous=not require_approval
        )
        
        result = agent.run_cycle(
            tenant_id=tenant_id,
            zone_id=zone_id,
            require_approval=require_approval,
            use_llm=use_llm
        )
        
        return {
            'success': True,
            'data': result
        }
    
    def _handle_agent_status(self, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle agent status request"""
        from ..agent.llm_agent import LLMLogisticsAgent
        
        agent = LLMLogisticsAgent(
            feature_store=self.feature_store,
            ml_service=self.ml_service
        )
        
        status = agent.get_status()
        
        return {
            'success': True,
            'data': status
        }
    
    def _handle_agent_configure(self, tenant_id: int, data: Dict[str, Any]) -> Dict[str, Any]:
        """Handle agent configuration request"""
        # Configuration would be persisted to database or config
        # Placeholder for now
        
        return {
            'success': True,
            'data': {
                'message': 'Agent configured',
                'enable_autonomous': data.get('enable_autonomous'),
                'llm_provider': data.get('llm_provider'),
                'llm_model': data.get('llm_model')
            }
        }
