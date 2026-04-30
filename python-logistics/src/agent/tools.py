"""
Tools for Logistics Agent
These are the tools the agent can use to interact with the system
"""

from typing import Dict, Any, List, Optional
import logging
from datetime import datetime, timedelta

from ..services.feature_store import FeatureStoreService
from ..services.ml_inference import MLInferenceService
from ..core.logging import get_logger

logger = get_logger(__name__)


class AnomalyDetectionTool:
    """Tool for detecting logistics anomalies"""
    
    def __init__(self, feature_store: FeatureStoreService):
        self.feature_store = feature_store
    
    def detect_courier_idle_anomaly(
        self,
        tenant_id: int,
        minutes_threshold: int = 10
    ) -> List[Dict[str, Any]]:
        """
        Detect couriers that have been idle for too long (>10 minutes)
        
        Returns list of couriers with idle time and location
        """
        query = f"""
        SELECT
            courier_id,
            latitude,
            longitude,
            current_status,
            max(created_at) AS last_update,
            dateDiff('minute', max(created_at), now()) AS idle_minutes
        FROM ch_logistics_positions
        WHERE tenant_id = {tenant_id}
          AND current_status = 'idle'
          AND created_at >= now() - INTERVAL 1 HOUR
        GROUP BY courier_id, latitude, longitude, current_status
        HAVING idle_minutes > {minutes_threshold}
        """
        
        try:
            result = self.feature_store.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            anomalies = []
            for row in rows:
                anomalies.append(dict(zip(columns, row)))
            
            if anomalies:
                logger.warning(
                    f"Detected {len(anomalies)} couriers idle > {minutes_threshold} minutes",
                    extra={"tenant_id": tenant_id, "anomalies": anomalies}
                )
            
            return anomalies
            
        except Exception as e:
            logger.error(f"Failed to detect courier idle anomaly: {e}")
            return []
    
    def detect_traffic_spike_anomaly(
        self,
        tenant_id: int,
        zone_id: str,
        spike_threshold: float = 2.0
    ) -> Optional[Dict[str, Any]]:
        """
        Detect traffic spike in a zone (traffic level 2x higher than normal)
        
        Returns anomaly details if detected
        """
        # Get current traffic level
        current_query = f"""
        SELECT traffic_level_avg, count_positions
        FROM ch_feature_courier_speed
        WHERE tenant_id = {tenant_id}
          AND zone_id = '{zone_id}'
          AND hour = toStartOfHour(now())
        LIMIT 1
        """
        
        # Get historical average (last 7 days same hour)
        historical_query = f"""
        SELECT avg(traffic_level_avg) AS avg_traffic_level
        FROM ch_feature_courier_speed
        WHERE tenant_id = {tenant_id}
          AND zone_id = '{zone_id}'
          AND hour >= now() - INTERVAL 7 DAY
          AND hour < now()
          AND toHour(hour) = toHour(now())
        """
        
        try:
            current_result = self.feature_store.client.query(current_query)
            historical_result = self.feature_store.client.query(historical_query)
            
            if not current_result.result_rows or not historical_result.result_rows:
                return None
            
            current_traffic = current_result.result_rows[0][0]
            historical_avg = historical_result.result_rows[0][0]
            
            if historical_avg > 0 and current_traffic / historical_avg > spike_threshold:
                anomaly = {
                    "zone_id": zone_id,
                    "current_traffic_level": current_traffic,
                    "historical_avg": historical_avg,
                    "spike_ratio": current_traffic / historical_avg,
                    "detected_at": datetime.now().isoformat()
                }
                
                logger.warning(
                    f"Traffic spike detected in zone {zone_id}: {current_traffic:.2f}x historical",
                    extra={"tenant_id": tenant_id, "anomaly": anomaly}
                )
                
                return anomaly
            
            return None
            
        except Exception as e:
            logger.error(f"Failed to detect traffic spike: {e}")
            return None
    
    def detect_delivery_delay_anomaly(
        self,
        tenant_id: int,
        delay_threshold_minutes: int = 30
    ) -> List[Dict[str, Any]]:
        """
        Detect shipments with significant delivery delays (>30 min beyond ETA)
        
        Returns list of delayed shipments
        """
        query = f"""
        SELECT
            shipment_id,
            courier_id,
            eta_predicted_minutes,
            eta_actual_minutes,
            eta_error_minutes,
            status,
            pickup_lat,
            pickup_lon,
            delivery_lat,
            delivery_lon
        FROM ch_logistics_shipments
        WHERE tenant_id = {tenant_id}
          AND status IN ('moving_to_delivery', 'at_delivery')
          AND eta_error_minutes > {delay_threshold_minutes}
          AND created_at >= now() - INTERVAL 4 HOUR
        """
        
        try:
            result = self.feature_store.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            anomalies = []
            for row in rows:
                anomalies.append(dict(zip(columns, row)))
            
            if anomalies:
                logger.warning(
                    f"Detected {len(anomalies)} shipments delayed > {delay_threshold_minutes} minutes",
                    extra={"tenant_id": tenant_id, "anomalies": anomalies}
                )
            
            return anomalies
            
        except Exception as e:
            logger.error(f"Failed to detect delivery delay: {e}")
            return []


class OrderRedistributionTool:
    """Tool for redistributing orders between couriers"""
    
    def __init__(
        self,
        feature_store: FeatureStoreService,
        ml_service: MLInferenceService
    ):
        self.feature_store = feature_store
        self.ml_service = ml_service
    
    def suggest_redistribution(
        self,
        tenant_id: int,
        zone_id: str,
        shipment_ids: List[str]
    ) -> Dict[str, Any]:
        """
        Suggest redistribution of shipments to available couriers in a zone
        
        Returns suggested reassignments with scores
        """
        try:
            # Get available couriers in zone
            couriers_query = f"""
            SELECT DISTINCT
                courier_id,
                vehicle_type,
                latitude,
                longitude,
                velocity_kmh,
                acceptance_rate,
                current_status
            FROM ch_logistics_positions
            WHERE tenant_id = {tenant_id}
              AND zone_id = '{zone_id}'
              AND is_available = 1
              AND current_status = 'idle'
              AND created_at >= now() - INTERVAL 5 MINUTE
            """
            
            courier_result = self.feature_store.client.query(couriers_query)
            courier_columns = courier_result.column_names
            couriers = [dict(zip(courier_columns, row)) for row in courier_result.result_rows]
            
            if not couriers:
                return {
                    "success": False,
                    "message": "No available couriers in zone",
                    "zone_id": zone_id
                }
            
            # Get shipment details
            shipments_query = f"""
            SELECT
                shipment_id,
                pickup_lat,
                pickup_lon,
                delivery_lat,
                delivery_lon,
                distance_km,
                hour_of_day,
                day_of_week,
                is_weekend,
                weather_condition,
                traffic_level,
                zone_pickup,
                zone_delivery
            FROM ch_logistics_shipments
            WHERE tenant_id = {tenant_id}
              AND shipment_id IN ({','.join([f"'{sid}'" for sid in shipment_ids])})
            """
            
            shipment_result = self.feature_store.client.query(shipments_query)
            shipment_columns = shipment_result.column_names
            shipments = [dict(zip(shipment_columns, row)) for row in shipment_result.result_rows]
            
            # Use ML model to score courier-shipment pairs
            assignment_model = self.ml_service.get_courier_assignment_model()
            
            suggestions = []
            for shipment in shipments:
                scores = assignment_model.predict_batch(
                    shipment=shipment,
                    couriers=couriers,
                    zone_features=None
                )
                
                # Sort by score
                scored_couriers = sorted(
                    zip(couriers, scores),
                    key=lambda x: x[1],
                    reverse=True
                )
                
                suggestions.append({
                    "shipment_id": shipment["shipment_id"],
                    "recommended_courier": scored_couriers[0][0]["courier_id"],
                    "score": float(scored_couriers[0][1]),
                    "alternatives": [
                        {
                            "courier_id": courier["courier_id"],
                            "score": float(score)
                        }
                        for courier, score in scored_couriers[1:3]
                    ]
                })
            
            return {
                "success": True,
                "zone_id": zone_id,
                "shipments_redistributed": len(suggestions),
                "available_couriers": len(couriers),
                "suggestions": suggestions
            }
            
        except Exception as e:
            logger.error(f"Failed to suggest redistribution: {e}")
            return {
                "success": False,
                "message": str(e),
                "zone_id": zone_id
            }


class SelfHealingRoutingTool:
    """Tool for self-healing route optimization"""
    
    def __init__(
        self,
        feature_store: FeatureStoreService,
        ml_service: MLInferenceService
    ):
        self.feature_store = feature_store
        self.ml_service = ml_service
    
    def reroute_shipment(
        self,
        tenant_id: int,
        shipment_id: str,
        reason: str
    ) -> Dict[str, Any]:
        """
        Reroute a shipment due to delay, traffic, or courier issue
        
        Returns new route with updated ETA
        """
        try:
            # Get shipment details
            shipment_query = f"""
            SELECT
                shipment_id,
                courier_id,
                pickup_lat,
                pickup_lon,
                delivery_lat,
                delivery_lon,
                distance_km,
                status,
                eta_predicted_minutes,
                hour_of_day,
                day_of_week,
                is_weekend,
                weather_condition,
                traffic_level,
                zone_pickup,
                zone_delivery
            FROM ch_logistics_shipments
            WHERE tenant_id = {tenant_id}
              AND shipment_id = '{shipment_id}'
            LIMIT 1
            """
            
            result = self.feature_store.client.query(shipment_query)
            if not result.result_rows:
                return {
                    "success": False,
                    "message": "Shipment not found"
                }
            
            columns = result.column_names
            shipment = dict(zip(columns, result.result_rows[0]))
            
            # Get updated zone speeds (current conditions)
            pickup_speed = self.feature_store.get_zone_speed_features(
                tenant_id=tenant_id,
                zone_id=shipment["zone_pickup"],
                vehicle_type="courier_car",  # Would use actual vehicle type
                hour=datetime.now()
            )
            
            delivery_speed = self.feature_store.get_zone_speed_features(
                tenant_id=tenant_id,
                zone_id=shipment["zone_delivery"],
                vehicle_type="courier_car",
                hour=datetime.now()
            )
            
            # Recalculate ETA with current conditions
            eta_model = self.ml_service.get_eta_prediction_model()
            new_eta = eta_model.predict(
                features=shipment,
                pickup_zone_speed=pickup_speed.get("avg_speed_kmh") if pickup_speed else None,
                delivery_zone_speed=delivery_speed.get("avg_speed_kmh") if delivery_speed else None
            )
            
            old_eta = shipment["eta_predicted_minutes"]
            eta_change = new_eta - old_eta
            
            return {
                "success": True,
                "shipment_id": shipment_id,
                "reason": reason,
                "old_eta_minutes": old_eta,
                "new_eta_minutes": new_eta,
                "eta_change_minutes": eta_change,
                "rerouted_at": datetime.now().isoformat(),
                "current_conditions": {
                    "pickup_zone_speed": pickup_speed.get("avg_speed_kmh") if pickup_speed else None,
                    "delivery_zone_speed": delivery_speed.get("avg_speed_kmh") if delivery_speed else None,
                    "traffic_level": shipment["traffic_level"]
                }
            }
            
        except Exception as e:
            logger.error(f"Failed to reroute shipment: {e}")
            return {
                "success": False,
                "message": str(e),
                "shipment_id": shipment_id
            }


class PVZCapacityTool:
    """Tool for PVZ capacity management"""
    
    def __init__(self, feature_store: FeatureStoreService):
        self.feature_store = feature_store
    
    def suggest_capacity_increase(
        self,
        tenant_id: int,
        zone_id: str,
        load_threshold: float = 0.8
    ) -> List[Dict[str, Any]]:
        """
        Suggest PVZs that need capacity increase (load > 80%)
        
        Returns list of PVZs with current load and forecast
        """
        query = f"""
        SELECT
            pvz_id,
            pvz_name,
            latitude,
            longitude,
            total_lockers,
            occupied_lockers,
            available_lockers,
            load_ratio,
            zone_id
        FROM ch_logistics_pvz
        WHERE tenant_id = {tenant_id}
          AND zone_id = '{zone_id}'
          AND load_ratio > {load_threshold}
          AND hour >= now() - INTERVAL 1 HOUR
        """
        
        try:
            result = self.feature_store.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            pvzs = []
            for row in rows:
                pvz = dict(zip(columns, row))
                
                # Get 3-hour forecast
                forecast = self.feature_store.get_pvz_load_forecast(
                    tenant_id=tenant_id,
                    pvz_id=pvz["pvz_id"],
                    forecast_hour=datetime.now() + timedelta(hours=3)
                )
                
                pvz["forecast_3h"] = forecast[-1] if forecast else None
                pvzs.append(pvz)
            
            if pvzs:
                logger.info(
                    f"Suggested capacity increase for {len(pvzs)} PVZs in zone {zone_id}",
                    extra={"tenant_id": tenant_id, "pvzs": pvzs}
                )
            
            return pvzs
            
        except Exception as e:
            logger.error(f"Failed to suggest capacity increase: {e}")
            return []
