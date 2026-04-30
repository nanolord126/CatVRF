"""
ClickHouse Feature Store Service
Provides access to online and offline features for ML inference
"""

import clickhouse_connect
from typing import List, Dict, Any, Optional
from datetime import datetime, timedelta
import logging

logger = logging.getLogger(__name__)


class FeatureStoreService:
    """Service for accessing ClickHouse feature store"""
    
    def __init__(self, host: str, port: int, database: str, 
                 username: str = 'default', password: str = ''):
        self.client = clickhouse_connect.get_client(
            host=host,
            port=port,
            username=username,
            password=password,
            database=database
        )
        logger.info(f"Connected to ClickHouse at {host}:{port}/{database}")
    
    def get_courier_assignment_features(
        self,
        tenant_id: int,
        shipment_id: str,
        courier_ids: List[str]
    ) -> List[Dict[str, Any]]:
        """
        Get online features for courier assignment from materialized view
        """
        query = f"""
        SELECT * FROM ch_online_courier_assignment_features
        WHERE tenant_id = {tenant_id}
          AND shipment_id = '{shipment_id}'
          AND courier_id IN ({','.join([f"'{cid}'" for cid in courier_ids])})
        """
        
        try:
            result = self.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            features = []
            for row in rows:
                features.append(dict(zip(columns, row)))
            
            logger.info(f"Retrieved {len(features)} courier assignment features")
            return features
            
        except Exception as e:
            logger.error(f"Failed to get courier assignment features: {e}")
            raise
    
    def get_pvz_scoring_features(
        self,
        tenant_id: int,
        zone_id: str,
        hour: datetime
    ) -> List[Dict[str, Any]]:
        """
        Get online features for PVZ scoring from materialized view
        """
        hour_str = hour.strftime('%Y-%m-%d %H:%M:%S')
        
        query = f"""
        SELECT * FROM ch_online_pvz_scoring_features
        WHERE tenant_id = {tenant_id}
          AND zone_id = '{zone_id}'
          AND feature_timestamp >= '{hour_str}' - INTERVAL 1 HOUR
        """
        
        try:
            result = self.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            features = []
            for row in rows:
                features.append(dict(zip(columns, row)))
            
            logger.info(f"Retrieved {len(features)} PVZ scoring features")
            return features
            
        except Exception as e:
            logger.error(f"Failed to get PVZ scoring features: {e}")
            raise
    
    def get_zone_speed_features(
        self,
        tenant_id: int,
        zone_id: str,
        vehicle_type: str,
        hour: datetime
    ) -> Optional[Dict[str, Any]]:
        """
        Get average speed features for a zone and vehicle type
        """
        hour_str = hour.strftime('%Y-%m-%d %H:%M:%S')
        
        query = f"""
        SELECT 
            avg_speed_kmh,
            p50_speed_kmh,
            p95_speed_kmh,
            count_positions,
            traffic_level_avg
        FROM ch_feature_courier_speed
        WHERE tenant_id = {tenant_id}
          AND zone_id = '{zone_id}'
          AND vehicle_type = '{vehicle_type}'
          AND hour = toStartOfHour('{hour_str}')
        LIMIT 1
        """
        
        try:
            result = self.client.query(query)
            if result.result_rows:
                columns = result.column_names
                row = result.result_rows[0]
                return dict(zip(columns, row))
            return None
            
        except Exception as e:
            logger.error(f"Failed to get zone speed features: {e}")
            return None
    
    def get_demand_forecast(
        self,
        tenant_id: int,
        zone_id: str,
        forecast_hour: datetime
    ) -> Optional[Dict[str, Any]]:
        """
        Get demand forecast for a zone
        """
        hour_str = forecast_hour.strftime('%Y-%m-%d %H:%M:%S')
        
        query = f"""
        SELECT 
            order_count,
            courier_count_available,
            supply_demand_ratio,
            avg_eta_predicted,
            avg_eta_error,
            cancellation_rate,
            driver_acceptance_rate_avg
        FROM ch_feature_demand_forecast
        WHERE tenant_id = {tenant_id}
          AND zone_id = '{zone_id}'
          AND forecast_hour = toStartOfHour('{hour_str}')
        LIMIT 1
        """
        
        try:
            result = self.client.query(query)
            if result.result_rows:
                columns = result.column_names
                row = result.result_rows[0]
                return dict(zip(columns, row))
            return None
            
        except Exception as e:
            logger.error(f"Failed to get demand forecast: {e}")
            return None
    
    def get_pvz_load_forecast(
        self,
        tenant_id: int,
        pvz_id: str,
        forecast_hour: datetime
    ) -> Optional[Dict[str, Any]]:
        """
        Get PVZ load forecast for the next 3 hours
        """
        hour_str = forecast_hour.strftime('%Y-%m-%d %H:%M:%S')
        
        query = f"""
        SELECT 
            load_ratio_avg,
            load_ratio_max,
            load_ratio_min,
            load_ratio_p50,
            load_ratio_p95,
            unique_users,
            preference_score_avg
        FROM ch_feature_pvz_load_ratio
        WHERE tenant_id = {tenant_id}
          AND pvz_id = '{pvz_id}'
          AND hour >= toStartOfHour('{hour_str}')
          AND hour < toStartOfHour('{hour_str}') + INTERVAL 3 HOUR
        ORDER BY hour ASC
        """
        
        try:
            result = self.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            forecasts = []
            for row in rows:
                forecasts.append(dict(zip(columns, row)))
            
            return forecasts
            
        except Exception as e:
            logger.error(f"Failed to get PVZ load forecast: {e}")
            return None
    
    def write_shipment_event(self, event: Dict[str, Any]) -> bool:
        """
        Write a shipment event to ClickHouse (for real-time tracking)
        """
        try:
            columns = list(event.keys())
            values = list(event.values())
            
            query = f"""
            INSERT INTO ch_logistics_shipments ({', '.join(columns)})
            VALUES
            """
            
            self.client.insert(
                'ch_logistics_shipments',
                [values],
                column_names=columns
            )
            
            logger.info(f"Wrote shipment event: {event.get('shipment_id')}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to write shipment event: {e}")
            return False
    
    def write_position_update(self, event: Dict[str, Any]) -> bool:
        """
        Write a courier/taxi position update to ClickHouse
        """
        try:
            columns = list(event.keys())
            values = list(event.values())
            
            self.client.insert(
                'ch_logistics_positions',
                [values],
                column_names=columns
            )
            
            logger.debug(f"Wrote position update: {event.get('courier_id')}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to write position update: {e}")
            return False
    
    def health_check(self) -> bool:
        """Check if ClickHouse is accessible"""
        try:
            result = self.client.query("SELECT 1")
            return len(result.result_rows) > 0
        except Exception as e:
            logger.error(f"ClickHouse health check failed: {e}")
            return False
