"""
Parquet Export Pipeline for Training Data
Exports ClickHouse feature store data to Parquet format for ML model training
"""

import clickhouse_connect
import pyarrow as pa
import pyarrow.parquet as pq
import pandas as pd
from datetime import datetime, timedelta
from typing import Optional, List
import logging
import os
import argparse

from ..src.core.config import settings
from ..src.core.logging import get_logger

logger = get_logger(__name__)


class ParquetExportPipeline:
    """
    Export ClickHouse feature store data to Parquet format
    
    This enables:
    - 5-10x faster model training (columnar format)
    - Efficient data compression
    - Easy integration with PyTorch/TensorFlow
    - Point-in-time correct training data
    """
    
    def __init__(
        self,
        host: str = None,
        port: int = None,
        database: str = None,
        username: str = None,
        password: str = None,
        output_dir: str = None
    ):
        self.host = host or settings.clickhouse_host
        self.port = port or settings.clickhouse_port
        self.database = database or settings.clickhouse_database
        self.username = username or settings.clickhouse_user
        self.password = password or settings.clickhouse_password
        self.output_dir = output_dir or "/data/training"
        
        os.makedirs(self.output_dir, exist_ok=True)
        
        self.client = clickhouse_connect.get_client(
            host=self.host,
            port=self.port,
            username=self.username,
            password=self.password,
            database=self.database
        )
        
        logger.info(f"Parquet Export Pipeline initialized", extra={
            "output_dir": self.output_dir,
            "host": self.host
        })
    
    def export_eta_training_data(
        self,
        start_date: datetime,
        end_date: datetime,
        filename: Optional[str] = None
    ) -> str:
        """
        Export ETA training data with point-in-time correct features
        """
        if filename is None:
            filename = f"eta_training_{start_date.strftime('%Y%m%d')}_{end_date.strftime('%Y%m%d')}.parquet"
        
        output_path = os.path.join(self.output_dir, filename)
        
        query = f"""
        SELECT
            tenant_id,
            vertical,
            shipment_id,
            event_time,
            
            -- Features at prediction time (point-in-time correct)
            pickup_lat,
            pickup_lon,
            delivery_lat,
            delivery_lon,
            distance_km,
            
            hour_of_day,
            day_of_week,
            is_weekend,
            is_holiday,
            
            weather_condition,
            temperature_c,
            traffic_level,
            
            zone_pickup,
            zone_delivery,
            
            courier_vehicle_type,
            courier_acceptance_rate,
            
            -- Historical features (from past 24h)
            avg_speed_pickup_zone_last_24h,
            avg_eta_error_pickup_zone_last_24h,
            demand_pickup_zone_last_24h,
            
            -- Target
            eta_actual_minutes
        FROM ch_feature_eta_training
        WHERE event_time >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND event_time < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
        ORDER BY event_time, shipment_id
        """
        
        logger.info(f"Exporting ETA training data", extra={
            "start_date": start_date,
            "end_date": end_date,
            "output_path": output_path
        })
        
        try:
            # Query ClickHouse
            result = self.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            # Convert to pandas DataFrame
            df = pd.DataFrame(rows, columns=columns)
            
            # Convert to PyArrow Table
            table = pa.Table.from_pandas(df)
            
            # Write to Parquet with compression
            pq.write_table(
                table,
                output_path,
                compression='snappy',
                row_group_size=10000
            )
            
            file_size_mb = os.path.getsize(output_path) / (1024 * 1024)
            
            logger.info(f"ETA training data exported successfully", extra={
                "output_path": output_path,
                "rows": len(df),
                "file_size_mb": file_size_mb
            })
            
            return output_path
            
        except Exception as e:
            logger.error(f"Failed to export ETA training data: {e}", exc_info=True)
            raise
    
    def export_courier_assignment_data(
        self,
        start_date: datetime,
        end_date: datetime,
        filename: Optional[str] = None
    ) -> str:
        """
        Export courier assignment training data
        """
        if filename is None:
            filename = f"courier_assignment_{start_date.strftime('%Y%m%d')}_{end_date.strftime('%Y%m%d')}.parquet"
        
        output_path = os.path.join(self.output_dir, filename)
        
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
            s.traffic_level,
            
            -- Courier features
            p.courier_id,
            p.vehicle_type,
            p.is_available,
            p.velocity_kmh,
            p.current_status,
            
            -- Distance features (would calculate with geodistance)
            -- distance_to_pickup_km,
            
            -- Zone features
            cs.avg_speed_kmh AS zone_avg_speed,
            cs.p50_speed_kmh AS zone_median_speed,
            cs.traffic_level_avg,
            
            -- Demand features
            df.order_count AS zone_demand_last_2h,
            df.supply_demand_ratio,
            df.cancellation_rate,
            
            -- Target (whether this courier was assigned)
            -- assigned (would join with assignment table)
            0 AS assigned_target
            
        FROM ch_logistics_shipments s
        LEFT JOIN ch_logistics_positions p 
            ON s.tenant_id = p.tenant_id 
            AND s.courier_id = p.courier_id
            AND p.created_at >= s.created_at - INTERVAL 5 MINUTE
        LEFT JOIN ch_feature_courier_speed cs
            ON p.tenant_id = cs.tenant_id
            AND p.zone_id = cs.zone_id
            AND p.vehicle_type = cs.vehicle_type
            AND toStartOfHour(s.created_at) = cs.hour
        LEFT JOIN ch_feature_demand_forecast df
            ON s.tenant_id = df.tenant_id
            AND s.geo_hash = df.zone_id
            AND toStartOfHour(s.created_at) = df.forecast_hour
        WHERE s.created_at >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND s.created_at < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND p.created_at = (
              SELECT max(created_at) 
              FROM ch_logistics_positions 
              WHERE tenant_id = p.tenant_id 
                AND courier_id = p.courier_id
          )
        LIMIT 1000000
        """
        
        logger.info(f"Exporting courier assignment data", extra={
            "start_date": start_date,
            "end_date": end_date,
            "output_path": output_path
        })
        
        try:
            result = self.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            df = pd.DataFrame(rows, columns=columns)
            table = pa.Table.from_pandas(df)
            
            pq.write_table(
                table,
                output_path,
                compression='snappy',
                row_group_size=10000
            )
            
            file_size_mb = os.path.getsize(output_path) / (1024 * 1024)
            
            logger.info(f"Courier assignment data exported successfully", extra={
                "output_path": output_path,
                "rows": len(df),
                "file_size_mb": file_size_mb
            })
            
            return output_path
            
        except Exception as e:
            logger.error(f"Failed to export courier assignment data: {e}", exc_info=True)
            raise
    
    def export_pvz_scoring_data(
        self,
        start_date: datetime,
        end_date: datetime,
        filename: Optional[str] = None
    ) -> str:
        """
        Export PVZ scoring training data
        """
        if filename is None:
            filename = f"pvz_scoring_{start_date.strftime('%Y%m%d')}_{end_date.strftime('%Y%m%d')}.parquet"
        
        output_path = os.path.join(self.output_dir, filename)
        
        query = f"""
        SELECT
            pv.tenant_id,
            pv.vertical,
            pv.pvz_id,
            pv.pvz_name,
            pv.pvz_lat,
            pv.pvz_lon,
            pv.geo_hash,
            pv.zone_id,
            
            -- Current load
            pv.total_lockers,
            pv.occupied_lockers,
            pv.available_lockers,
            pv.load_ratio,
            
            -- Temporal features
            pv.hour_of_day,
            pv.day_of_week,
            plr.load_ratio_avg AS historical_load_avg,
            plr.load_ratio_p95 AS historical_load_p95,
            plr.preference_score_avg AS user_preference_score,
            
            -- Performance
            pv.avg_pickup_time_minutes,
            pv.avg_wait_time_minutes,
            
            -- Demand in zone
            df.order_count AS zone_demand,
            df.courier_count_available,
            
            -- Target (whether user chose this PVZ)
            -- chosen (would join with user choices)
            0 AS chosen_target
            
        FROM ch_logistics_pvz pv
        LEFT JOIN ch_feature_pvz_load_ratio plr
            ON pv.tenant_id = plr.tenant_id
            AND pv.pvz_id = plr.pvz_id
            AND pv.hour = plr.hour
        LEFT JOIN ch_feature_demand_forecast df
            ON pv.tenant_id = df.tenant_id
            AND pv.zone_id = df.zone_id
            AND toStartOfHour(pv.created_at + INTERVAL 3 HOUR) = df.forecast_hour
        WHERE pv.created_at >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND pv.created_at < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
        LIMIT 1000000
        """
        
        logger.info(f"Exporting PVZ scoring data", extra={
            "start_date": start_date,
            "end_date": end_date,
            "output_path": output_path
        })
        
        try:
            result = self.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            df = pd.DataFrame(rows, columns=columns)
            table = pa.Table.from_pandas(df)
            
            pq.write_table(
                table,
                output_path,
                compression='snappy',
                row_group_size=10000
            )
            
            file_size_mb = os.path.getsize(output_path) / (1024 * 1024)
            
            logger.info(f"PVZ scoring data exported successfully", extra={
                "output_path": output_path,
                "rows": len(df),
                "file_size_mb": file_size_mb
            })
            
            return output_path
            
        except Exception as e:
            logger.error(f"Failed to export PVZ scoring data: {e}", exc_info=True)
            raise
    
    def export_demand_forecast_data(
        self,
        start_date: datetime,
        end_date: datetime,
        filename: Optional[str] = None
    ) -> str:
        """
        Export demand forecasting training data (time series)
        """
        if filename is None:
            filename = f"demand_forecast_{start_date.strftime('%Y%m%d')}_{end_date.strftime('%Y%m%d')}.parquet"
        
        output_path = os.path.join(self.output_dir, filename)
        
        query = f"""
        SELECT
            tenant_id,
            vertical,
            zone_id,
            forecast_hour,
            hour_of_day,
            day_of_week,
            is_weekend,
            
            order_count,
            courier_count_available,
            supply_demand_ratio,
            avg_eta_predicted,
            avg_eta_error,
            cancellation_rate,
            driver_acceptance_rate_avg
            
        FROM ch_feature_demand_forecast
        WHERE forecast_hour >= '{start_date.strftime('%Y-%m-%d %H:%M:%S')}'
          AND forecast_hour < '{end_date.strftime('%Y-%m-%d %H:%M:%S')}'
        ORDER BY zone_id, forecast_hour
        """
        
        logger.info(f"Exporting demand forecast data", extra={
            "start_date": start_date,
            "end_date": end_date,
            "output_path": output_path
        })
        
        try:
            result = self.client.query(query)
            columns = result.column_names
            rows = result.result_rows
            
            df = pd.DataFrame(rows, columns=columns)
            table = pa.Table.from_pandas(df)
            
            pq.write_table(
                table,
                output_path,
                compression='snappy',
                row_group_size=10000
            )
            
            file_size_mb = os.path.getsize(output_path) / (1024 * 1024)
            
            logger.info(f"Demand forecast data exported successfully", extra={
                "output_path": output_path,
                "rows": len(df),
                "file_size_mb": file_size_mb
            })
            
            return output_path
            
        except Exception as e:
            logger.error(f"Failed to export demand forecast data: {e}", exc_info=True)
            raise
    
    def export_all(
        self,
        days_back: int = 7,
        output_dir: Optional[str] = None
    ) -> List[str]:
        """
        Export all training datasets for the specified period
        """
        if output_dir:
            self.output_dir = output_dir
            os.makedirs(self.output_dir, exist_ok=True)
        
        end_date = datetime.now()
        start_date = end_date - timedelta(days=days_back)
        
        exported_files = []
        
        # Export all datasets
        exported_files.append(self.export_eta_training_data(start_date, end_date))
        exported_files.append(self.export_courier_assignment_data(start_date, end_date))
        exported_files.append(self.export_pvz_scoring_data(start_date, end_date))
        exported_files.append(self.export_demand_forecast_data(start_date, end_date))
        
        logger.info(f"All training data exported successfully", extra={
            "files": exported_files,
            "total_files": len(exported_files)
        })
        
        return exported_files


def main():
    """CLI entry point for Parquet export"""
    parser = argparse.ArgumentParser(description="Export ClickHouse feature store data to Parquet")
    parser.add_argument("--days-back", type=int, default=7, help="Number of days to export")
    parser.add_argument("--output-dir", type=str, default="/data/training", help="Output directory")
    parser.add_argument("--dataset", type=str, choices=["all", "eta", "courier", "pvz", "demand"], 
                       default="all", help="Dataset to export")
    
    args = parser.parse_args()
    
    pipeline = ParquetExportPipeline(output_dir=args.output_dir)
    
    end_date = datetime.now()
    start_date = end_date - timedelta(days=args.days_back)
    
    if args.dataset == "all":
        pipeline.export_all(days_back=args.days_back)
    elif args.dataset == "eta":
        pipeline.export_eta_training_data(start_date, end_date)
    elif args.dataset == "courier":
        pipeline.export_courier_assignment_data(start_date, end_date)
    elif args.dataset == "pvz":
        pipeline.export_pvz_scoring_data(start_date, end_date)
    elif args.dataset == "demand":
        pipeline.export_demand_forecast_data(start_date, end_date)


if __name__ == "__main__":
    main()
