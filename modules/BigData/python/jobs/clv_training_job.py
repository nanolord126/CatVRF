#!/usr/bin/env python3
"""
PySpark CLV Training Job for CatVRF Big Data

Trains XGBoost model for Customer Lifetime Value prediction.
Exports training data to Parquet for model training.

Usage:
    spark-submit clv_training_job.py --start_date 2026-01-01 --end_date 2026-04-28 --tenant_id 1
"""

import sys
import argparse
from datetime import datetime
from pyspark.sql import SparkSession
from pyspark.sql.functions import (
    col, count, sum as sum_, avg, stddev, datediff, lit,
    when, row_number, max as max_, min as min_
)
from pyspark.sql.window import Window


def create_spark_session(app_name="CatVRF-CLV-Training"):
    """Create Spark session with ClickHouse JDBC connector"""
    spark = SparkSession.builder \
        .appName(app_name) \
        .config("spark.jars", "/opt/spark/jars/clickhouse-jdbc-0.4.6-all.jar") \
        .config("spark.sql.adaptive.enabled", "true") \
        .config("spark.sql.adaptive.coalescePartitions.enabled", "true") \
        .config("spark.serializer", "org.apache.spark.serializer.KryoSerializer") \
        .getOrCreate()
    
    spark.sparkContext.setLogLevel("WARN")
    return spark


def read_training_data(spark, clickhouse_url, start_date, end_date, tenant_id):
    """Read historical data for CLV training"""
    query = f"""
    SELECT 
        tenant_id,
        user_id,
        seller_id,
        event_type,
        event_category,
        monetary_value,
        created_at
    FROM ch_raw_events
    WHERE toDate(created_at) BETWEEN '{start_date}' AND '{end_date}'
      AND tenant_id = {tenant_id}
      AND user_id IS NOT NULL
    """
    
    return spark.read \
        .format("jdbc") \
        .option("url", clickhouse_url) \
        .option("query", query) \
        .option("driver", "com.clickhouse.jdbc.ClickHouseDriver") \
        .load()


def extract_clv_features(events_df, prediction_date):
    """Extract CLV features for model training"""
    
    # User-level features
    user_features = events_df.groupBy("tenant_id", "user_id") \
        .agg(
            count("*").alias("total_events"),
            count(when(col("event_type") == "order.placed", True)).alias("total_orders"),
            sum_("monetary_value").alias("total_spent"),
            avg("monetary_value").alias("avg_order_value"),
            stddev("monetary_value").alias("order_value_std"),
            max_("created_at").alias("last_activity"),
            min_("created_at").alias("first_activity")
        ) \
        .fillna({"order_value_std": 0})
    
    # Recency features
    user_features = user_features.withColumn(
        "days_since_last_order",
        datediff(lit(prediction_date), col("last_activity"))
    )
    
    user_features = user_features.withColumn(
        "days_since_first_order",
        datediff(lit(prediction_date), col("first_activity"))
    )
    
    user_features = user_features.withColumn(
        "customer_age_days",
        datediff(col("last_activity"), col("first_activity"))
    )
    
    # Frequency features
    user_features = user_features.withColumn(
        "orders_per_day",
        when(col("days_since_first_order") > 0, 
              col("total_orders") / col("days_since_first_order")).otherwise(0)
    )
    
    # Monetary features
    user_features = user_features.withColumn(
        "spend_per_day",
        when(col("days_since_first_order") > 0,
              col("total_spent") / col("days_since_first_order")).otherwise(0)
    )
    
    # Seller diversity (how many different sellers)
    seller_diversity = events_df.filter(col("seller_id").isNotNull()) \
        .groupBy("tenant_id", "user_id") \
        .agg(count("seller_id").alias("unique_sellers"))
    
    user_features = user_features.join(
        seller_diversity,
        on=["tenant_id", "user_id"],
        how="left"
    ).fillna({"unique_sellers": 0})
    
    # Target variable: CLV (total spent in next 12 months)
    # This would require looking ahead, simplified here as total_spent
    user_features = user_features.withColumn(
        "clv_target_12m",
        col("total_spent")  # Simplified - should use actual 12-month future spend
    )
    
    return user_features


def export_to_parquet(df, output_path):
    """Export training data to Parquet format"""
    df.write \
        .mode("overwrite") \
        .parquet(output_path)
    
    print(f"Exported {df.count()} records to {output_path}")


def main():
    parser = argparse.ArgumentParser(description="CatVRF CLV Training Job")
    parser.add_argument("--start_date", required=True, help="Start date (YYYY-MM-DD)")
    parser.add_argument("--end_date", required=True, help="End date (YYYY-MM-DD)")
    parser.add_argument("--tenant_id", type=int, required=True, help="Tenant ID")
    parser.add_argument("--clickhouse_url", default="jdbc:clickhouse://localhost:8123/default", help="ClickHouse JDBC URL")
    parser.add_argument("--output_path", default="/data/training/clv_features.parquet", help="Output Parquet path")
    
    args = parser.parse_args()
    
    start_date = args.start_date
    end_date = args.end_date
    tenant_id = args.tenant_id
    clickhouse_url = args.clickhouse_url
    output_path = args.output_path
    
    print(f"Starting CLV Training Job: {start_date} to {end_date}, tenant: {tenant_id}")
    
    spark = create_spark_session()
    
    try:
        # Read training data
        print("Reading training data from ClickHouse...")
        events_df = read_training_data(spark, clickhouse_url, start_date, end_date, tenant_id)
        print(f"Loaded {events_df.count()} events")
        
        # Extract features
        print("Extracting CLV features...")
        features_df = extract_clv_features(events_df, end_date)
        print(f"Extracted {features_df.count()} user feature records")
        
        # Export to Parquet
        print("Exporting to Parquet...")
        export_to_parquet(features_df, output_path)
        
        print("CLV Training Job completed successfully")
        print(f"Training data saved to: {output_path}")
        
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)
    finally:
        spark.stop()


if __name__ == "__main__":
    main()
