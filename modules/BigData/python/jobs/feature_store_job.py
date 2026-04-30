#!/usr/bin/env python3
"""
PySpark Feature Store Job for CatVRF Big Data

Calculates daily features for:
- RFM scores (Recency, Frequency, Monetary)
- CLV features
- Buyer-Seller affinity
- Seller performance metrics

Usage:
    spark-submit feature_store_job.py --date 2026-04-28 --tenant_id 1
"""

import sys
import argparse
from datetime import datetime, timedelta
from pyspark.sql import SparkSession
from pyspark.sql.functions import (
    col, count, sum as sum_, avg, max as max_, min as min_,
    datediff, when, lit, row_number, dense_rank
)
from pyspark.sql.window import Window


def create_spark_session(app_name="CatVRF-FeatureStore"):
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


def read_raw_events(spark, clickhouse_url, date, tenant_id):
    """Read raw events from ClickHouse for a specific date"""
    query = f"""
    SELECT 
        tenant_id,
        user_id,
        seller_id,
        product_id,
        order_id,
        event_type,
        event_category,
        monetary_value,
        created_at,
        properties
    FROM ch_raw_events
    WHERE toDate(created_at) = '{date}'
      AND tenant_id = {tenant_id}
    """
    
    return spark.read \
        .format("jdbc") \
        .option("url", clickhouse_url) \
        .option("query", query) \
        .option("driver", "com.clickhouse.jdbc.ClickHouseDriver") \
        .load()


def calculate_rfm_features(events_df, feature_date):
    """Calculate RFM (Recency, Frequency, Monetary) features per user"""
    
    # Filter for order events
    order_events = events_df.filter(
        col("event_category") == "order"
    )
    
    # Recency: days since last order
    recency_window = Window.partitionBy("tenant_id", "user_id")
    
    rfm_df = order_events.groupBy("tenant_id", "user_id") \
        .agg(
            max_("created_at").alias("last_order_date"),
            count("*").alias("frequency"),
            sum_("monetary_value").alias("monetary"),
            avg("monetary_value").alias("avg_order_value")
        ) \
        .withColumn(
            "recency_days",
            datediff(lit(feature_date), col("last_order_date"))
        )
    
    # Calculate RFM scores (1-5 scale, 5 is best)
    rfm_df = rfm_df.withColumn(
        "recency_score",
        when(col("recency_days") <= 7, 5)
        .when(col("recency_days") <= 14, 4)
        .when(col("recency_days") <= 30, 3)
        .when(col("recency_days") <= 90, 2)
        .otherwise(1)
    )
    
    # Frequency score (percentile-based)
    freq_window = Window.partitionBy("tenant_id").orderBy(col("frequency"))
    rfm_df = rfm_df.withColumn(
        "frequency_score",
        dense_rank().over(freq_window)
    )
    
    # Monetary score (percentile-based)
    monetary_window = Window.partitionBy("tenant_id").orderBy(col("monetary"))
    rfm_df = rfm_df.withColumn(
        "monetary_score",
        dense_rank().over(monetary_window)
    )
    
    # Combined RFM score
    rfm_df = rfm_df.withColumn(
        "rfm_score",
        col("recency_score") * 100 + col("frequency_score") * 10 + col("monetary_score")
    )
    
    # RFM segment
    rfm_df = rfm_df.withColumn(
        "rfm_segment",
        when(col("recency_score") >= 4, "Champions")
        .when(col("recency_score") >= 3, "Loyal Customers")
        .when(col("recency_score") >= 2, "At Risk")
        .otherwise("Hibernating")
    )
    
    return rfm_df


def calculate_seller_metrics(events_df, feature_date):
    """Calculate seller daily metrics"""
    
    seller_df = events_df.filter(col("seller_id").isNotNull())
    
    metrics = seller_df.groupBy("tenant_id", "seller_id") \
        .agg(
            count(when(col("event_type") == "order.placed", True)).alias("orders_count"),
            count(when(col("event_type") == "order.delivered", True)).alias("orders_completed"),
            count(when(col("event_type") == "order.cancelled", True)).alias("orders_cancelled"),
            sum_(when(col("event_type") == "order.placed", col("monetary_value"))).alias("gmv_total"),
            sum_(when(col("event_type") == "order.paid", col("monetary_value"))).alias("revenue_total"),
            count("user_id").alias("customers_unique"),
            avg(when(col("event_type") == "order.placed", col("monetary_value"))).alias("avg_order_value")
        )
    
    # Calculate rates
    metrics = metrics.withColumn(
        "fulfillment_rate",
        when(col("orders_count") > 0, col("orders_completed") / col("orders_count")).otherwise(0)
    )
    
    metrics = metrics.withColumn(
        "cancellation_rate",
        when(col("orders_count") > 0, col("orders_cancelled") / col("orders_count")).otherwise(0)
    )
    
    return metrics


def calculate_buyer_seller_affinity(events_df, feature_date):
    """Calculate buyer-seller affinity features"""
    
    affinity_df = events_df.filter(
        col("user_id").isNotNull() & col("seller_id").isNotNull()
    )
    
    affinity = affinity_df.groupBy("tenant_id", "user_id", "seller_id") \
        .agg(
            count("*").alias("buyer_seller_order_count"),
            sum_("monetary_value").alias("buyer_seller_total_spent"),
            max_("created_at").alias("last_order_date")
        )
    
    # Calculate overall affinity score (simple weighted sum)
    affinity = affinity.withColumn(
        "overall_affinity_score",
        col("buyer_seller_order_count") * 0.5 + col("buyer_seller_total_spent") * 0.0001
    )
    
    return affinity


def write_to_clickhouse(df, table, clickhouse_url):
    """Write DataFrame to ClickHouse"""
    df.write \
        .format("jdbc") \
        .option("url", clickhouse_url) \
        .option("dbtable", table) \
        .option("driver", "com.clickhouse.jdbc.ClickHouseDriver") \
        .mode("append") \
        .save()


def main():
    parser = argparse.ArgumentParser(description="CatVRF Feature Store Job")
    parser.add_argument("--date", required=True, help="Feature date (YYYY-MM-DD)")
    parser.add_argument("--tenant_id", type=int, required=True, help="Tenant ID")
    parser.add_argument("--clickhouse_url", default="jdbc:clickhouse://localhost:8123/default", help="ClickHouse JDBC URL")
    
    args = parser.parse_args()
    
    feature_date = args.date
    tenant_id = args.tenant_id
    clickhouse_url = args.clickhouse_url
    
    print(f"Starting Feature Store Job for date: {feature_date}, tenant: {tenant_id}")
    
    spark = create_spark_session()
    
    try:
        # Read raw events
        print("Reading raw events from ClickHouse...")
        events_df = read_raw_events(spark, clickhouse_url, feature_date, tenant_id)
        print(f"Loaded {events_df.count()} events")
        
        # Calculate RFM features
        print("Calculating RFM features...")
        rfm_df = calculate_rfm_features(events_df, feature_date)
        rfm_df = rfm_df.withColumn("feature_date", lit(feature_date))
        write_to_clickhouse(rfm_df, "ch_clv_predictions", clickhouse_url)
        print(f"Written {rfm_df.count()} RFM records")
        
        # Calculate seller metrics
        print("Calculating seller metrics...")
        seller_df = calculate_seller_metrics(events_df, feature_date)
        seller_df = seller_df.withColumn("metric_date", lit(feature_date))
        write_to_clickhouse(seller_df, "ch_seller_daily_metrics", clickhouse_url)
        print(f"Written {seller_df.count()} seller metric records")
        
        # Calculate buyer-seller affinity
        print("Calculating buyer-seller affinity...")
        affinity_df = calculate_buyer_seller_affinity(events_df, feature_date)
        affinity_df = affinity_df.withColumn("feature_date", lit(feature_date))
        write_to_clickhouse(affinity_df, "ch_buyer_seller_features", clickhouse_url)
        print(f"Written {affinity_df.count()} affinity records")
        
        print("Feature Store Job completed successfully")
        
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)
    finally:
        spark.stop()


if __name__ == "__main__":
    main()
