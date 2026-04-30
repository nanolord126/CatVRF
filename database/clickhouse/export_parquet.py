#!/usr/bin/env python3
"""
ClickHouse Feature Store - Parquet Export for Training
Exports feature data to Parquet format for ML model training (5-10x faster than CSV)
"""

import os
import sys
import clickhouse_connect
from datetime import datetime, timedelta
from typing import Optional
import argparse
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class FeatureStoreExporter:
    """Export ClickHouse feature store data to Parquet for ML training"""
    
    def __init__(
        self,
        host: str = os.getenv('CLICKHOUSE_HOST', 'localhost'),
        port: int = int(os.getenv('CLICKHOUSE_PORT', 8123)),
        username: str = os.getenv('CLICKHOUSE_USER', 'default'),
        password: str = os.getenv('CLICKHOUSE_PASSWORD', ''),
        database: str = os.getenv('CLICKHOUSE_DATABASE', 'default'),
        export_dir: str = os.getenv('EXPORT_DIR', '/data/training')
    ):
        self.client = clickhouse_connect.get_client(
            host=host,
            port=port,
            username=username,
            password=password,
            database=database
        )
        self.export_dir = export_dir
        os.makedirs(export_dir, exist_ok=True)
    
    def export_eta_features(
        self,
        days_back: int = 7,
        filename: Optional[str] = None
    ) -> str:
        """Export ETA training features with point-in-time correctness"""
        
        start_date = datetime.now() - timedelta(days=days_back)
        end_date = datetime.now()
        
        if filename is None:
            filename = f"eta_features_{start_date.strftime('%Y%m%d')}_to_{end_date.strftime('%Y%m%d')}.parquet"
        
        filepath = os.path.join(self.export_dir, filename)
        
        query = f"""
        SELECT * FROM ch_feature_eta_training
        WHERE event_time >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND event_time < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
        FORMAT Parquet
        """
        
        logger.info(f"Exporting ETA features from {start_date} to {end_date}")
        
        result = self.client.query(query)
        with open(filepath, 'wb') as f:
            f.write(result.get_result())
        
        logger.info(f"Exported ETA features to {filepath}")
        return filepath
    
    def export_courier_assignment_features(
        self,
        days_back: int = 30,
        filename: Optional[str] = None
    ) -> str:
        """Export courier assignment features for GNN training"""
        
        start_date = datetime.now() - timedelta(days=days_back)
        end_date = datetime.now()
        
        if filename is None:
            filename = f"courier_assignment_features_{start_date.strftime('%Y%m%d')}_to_{end_date.strftime('%Y%m%d')}.parquet"
        
        filepath = os.path.join(self.export_dir, filename)
        
        query = f"""
        SELECT 
            s.tenant_id,
            s.vertical,
            s.shipment_id,
            s.pickup_lat,
            s.pickup_lon,
            s.delivery_lat,
            s.delivery_lon,
            s.distance_km,
            s.hour_of_day,
            s.day_of_week,
            s.is_weekend,
            s.weather_condition,
            s.temperature_c,
            s.traffic_level,
            s.courier_vehicle_type,
            s.courier_acceptance_rate,
            s.avg_speed_pickup_zone_last_24h,
            s.avg_eta_error_pickup_zone_last_24h,
            s.demand_pickup_zone_last_24h,
            s.eta_actual_minutes,
            -- Assignment success label
            CASE WHEN s.status IN ('delivered', 'picked_up') THEN 1 ELSE 0 END AS assignment_success,
            -- Delivery success label
            CASE WHEN s.status = 'delivered' THEN 1 ELSE 0 END AS delivery_success
        FROM ch_logistics_shipments s
        WHERE s.created_at >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND s.created_at < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND s.courier_id IS NOT NULL
        FORMAT Parquet
        """
        
        logger.info(f"Exporting courier assignment features from {start_date} to {end_date}")
        
        result = self.client.query(query)
        with open(filepath, 'wb') as f:
            f.write(result.get_result())
        
        logger.info(f"Exported courier assignment features to {filepath}")
        return filepath
    
    def export_pvz_features(
        self,
        days_back: int = 90,
        filename: Optional[str] = None
    ) -> str:
        """Export PVZ scoring features with temporal data"""
        
        start_date = datetime.now() - timedelta(days=days_back)
        end_date = datetime.now()
        
        if filename is None:
            filename = f"pvz_features_{start_date.strftime('%Y%m%d')}_to_{end_date.strftime('%Y%m%d')}.parquet"
        
        filepath = os.path.join(self.export_dir, filename)
        
        query = f"""
        SELECT 
            pv.tenant_id,
            pv.vertical,
            pv.pvz_id,
            pv.pvz_lat,
            pv.pvz_lon,
            pv.geo_hash,
            pv.zone_id,
            pv.hour,
            pv.hour_of_day,
            pv.day_of_week,
            pv.load_ratio,
            pv.unique_users,
            pv.repeat_users,
            pv.preference_score,
            pv.avg_pickup_time_minutes,
            plr.load_ratio_avg AS historical_load_avg,
            plr.load_ratio_p95 AS historical_load_p95,
            df.order_count AS zone_demand,
            df.cancellation_rate
        FROM ch_logistics_pvz pv
        LEFT JOIN ch_feature_pvz_load_ratio plr
            ON pv.tenant_id = plr.tenant_id
            AND pv.pvz_id = plr.pvz_id
            AND pv.hour = plr.hour
        LEFT JOIN ch_feature_demand_forecast df
            ON pv.tenant_id = df.tenant_id
            AND pv.zone_id = df.zone_id
            AND pv.hour = df.forecast_hour
        WHERE pv.hour >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND pv.hour < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
        FORMAT Parquet
        """
        
        logger.info(f"Exporting PVZ features from {start_date} to {end_date}")
        
        result = self.client.query(query)
        with open(filepath, 'wb') as f:
            f.write(result.get_result())
        
        logger.info(f"Exported PVZ features to {filepath}")
        return filepath
    
    def export_demand_forecast_features(
        self,
        days_back: int = 30,
        filename: Optional[str] = None
    ) -> str:
        """Export demand forecasting features for time-series models"""
        
        start_date = datetime.now() - timedelta(days=days_back)
        end_date = datetime.now()
        
        if filename is None:
            filename = f"demand_forecast_features_{start_date.strftime('%Y%m%d')}_to_{end_date.strftime('%Y%m%d')}.parquet"
        
        filepath = os.path.join(self.export_dir, filename)
        
        query = f"""
        SELECT * FROM ch_feature_demand_forecast
        WHERE forecast_hour >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND forecast_hour < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
        FORMAT Parquet
        """
        
        logger.info(f"Exporting demand forecast features from {start_date} to {end_date}")
        
        result = self.client.query(query)
        with open(filepath, 'wb') as f:
            f.write(result.get_result())
        
        logger.info(f"Exported demand forecast features to {filepath}")
        return filepath
    
    def export_all(
        self,
        eta_days: int = 7,
        courier_days: int = 30,
        pvz_days: int = 90,
        demand_days: int = 30
    ) -> dict:
        """Export all feature datasets"""
        
        results = {}
        
        logger.info("Starting full feature store export...")
        
        try:
            results['eta'] = self.export_eta_features(days_back=eta_days)
            results['courier_assignment'] = self.export_courier_assignment_features(days_back=courier_days)
            results['pvz'] = self.export_pvz_features(days_back=pvz_days)
            results['demand_forecast'] = self.export_demand_forecast_features(days_back=demand_days)
            
            logger.info("Full feature store export completed successfully")
            
        except Exception as e:
            logger.error(f"Export failed: {e}")
            raise
        
        return results


def main():
    parser = argparse.ArgumentParser(description='Export ClickHouse feature store to Parquet')
    parser.add_argument('--export-type', choices=['eta', 'courier', 'pvz', 'demand', 'all'], 
                       default='all', help='Type of features to export')
    parser.add_argument('--days-back', type=int, default=7, help='Days of data to export')
    parser.add_argument('--export-dir', type=str, default='/data/training', help='Export directory')
    parser.add_argument('--host', type=str, default='localhost', help='ClickHouse host')
    parser.add_argument('--port', type=int, default=8123, help='ClickHouse port')
    parser.add_argument('--database', type=str, default='default', help='ClickHouse database')
    
    args = parser.parse_args()
    
    exporter = FeatureStoreExporter(
        host=args.host,
        port=args.port,
        database=args.database,
        export_dir=args.export_dir
    )
    
    if args.export_type == 'all':
        results = exporter.export_all()
        for name, filepath in results.items():
            print(f"{name}: {filepath}")
    elif args.export_type == 'eta':
        filepath = exporter.export_eta_features(days_back=args.days_back)
        print(f"ETA features: {filepath}")
    elif args.export_type == 'courier':
        filepath = exporter.export_courier_assignment_features(days_back=args.days_back)
        print(f"Courier assignment features: {filepath}")
    elif args.export_type == 'pvz':
        filepath = exporter.export_pvz_features(days_back=args.days_back)
        print(f"PVZ features: {filepath}")
    elif args.export_type == 'demand':
        filepath = exporter.export_demand_forecast_features(days_back=args.days_back)
        print(f"Demand forecast features: {filepath}")


if __name__ == '__main__':
    main()
