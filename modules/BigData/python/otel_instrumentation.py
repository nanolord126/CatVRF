#!/usr/bin/env python3
"""
OpenTelemetry Instrumentation for CatVRF PySpark Jobs

Provides tracing and metrics for Spark jobs:
- CLV Training
- Feature Store
- A/B Test Evaluation

Usage (add to spark-submit):
    spark-submit --conf spark.driver.extraJavaOptions=-javaagent:/opt/opentelemetry/javaagent.jar \
                 --conf spark.executor.extraJavaOptions=-javaagent:/opt/opentelemetry/javaagent.jar \
                 clv_training_job.py

Or use this module directly:
    from otel_instrumentation import BigDataTracer
    tracer = BigDataTracer("clv_training")
    with tracer.span("read_data", table="ch_raw_events"):
        df = spark.read...
"""

import os
import time
import json
import logging
from contextlib import contextmanager
from datetime import datetime
from typing import Optional, Dict, Any

try:
    from opentelemetry import trace, metrics
    from opentelemetry.sdk.trace import TracerProvider
    from opentelemetry.sdk.trace.export import BatchSpanExporter
    from opentelemetry.exporter.otlp.proto.grpc.trace_exporter import OTLPSpanExporter
    from opentelemetry.sdk.metrics import MeterProvider
    from opentelemetry.sdk.metrics.export import PeriodicExportingMetricReader
    from opentelemetry.exporter.otlp.proto.grpc.metric_exporter import OTLPMetricExporter
    from opentelemetry.sdk.resources import Resource
    OTEL_AVAILABLE = True
except ImportError:
    OTEL_AVAILABLE = False

try:
    from prometheus_client import Counter, Histogram, Gauge, push_to_gateway
    PROMETHEUS_AVAILABLE = True
except ImportError:
    PROMETHEUS_AVAILABLE = False

logger = logging.getLogger(__name__)


# Prometheus metrics (fallback when OTel collector unavailable)
if PROMETHEUS_AVAILABLE:
    SPARK_JOB_DURATION = Histogram(
        'catvrf_bigdata_spark_job_duration_seconds',
        'Spark job duration in seconds',
        ['job_name', 'status']
    )
    SPARK_JOB_RECORDS = Counter(
        'catvrf_bigdata_spark_job_records_total',
        'Total records processed by Spark job',
        ['job_name', 'operation']
    )
    SPARK_JOB_ERRORS = Counter(
        'catvrf_bigdata_spark_job_errors_total',
        'Total errors in Spark jobs',
        ['job_name', 'error_type']
    )
    SPARK_JOB_PROGRESS = Gauge(
        'catvrf_bigdata_spark_job_progress_percent',
        'Job progress percentage',
        ['job_name']
    )
    FEATURE_STORE_FRESHNESS = Gauge(
        'catvrf_bigdata_feature_store_freshness_seconds',
        'Feature store data freshness in seconds',
        ['feature_type']
    )


class BigDataTracer:
    """
    Tracing + Metrics for CatVRF BigData Spark jobs.

    Supports both OpenTelemetry (preferred) and Prometheus Pushgateway (fallback).
    """

    def __init__(self, job_name: str, tenant_id: Optional[int] = None):
        self.job_name = job_name
        self.tenant_id = tenant_id
        self.correlation_id = os.environ.get('CORRELATION_ID', f'spark_{job_name}_{int(time.time())}')
        self.start_time = time.time()
        self._tracer = None
        self._meter = None

        if OTEL_AVAILABLE:
            self._init_otel()

    def _init_otel(self):
        """Initialize OpenTelemetry tracer and meter"""
        try:
            otlp_endpoint = os.environ.get('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://tempo:4317')
            resource = Resource.create({
                'service.name': f'catvrf-spark-{self.job_name}',
                'service.version': '1.0.0',
                'deployment.environment': os.environ.get('APP_ENV', 'production'),
                'job.name': self.job_name,
                'correlation.id': self.correlation_id,
            })

            # Tracer
            trace_provider = TracerProvider(resource=resource)
            trace_exporter = OTLPSpanExporter(endpoint=otlp_endpoint, insecure=True)
            trace_provider.add_span_processor(BatchSpanExporter(trace_exporter))
            trace.set_tracer_provider(trace_provider)
            self._tracer = trace.get_tracer(f'catvrf-spark-{self.job_name}', '1.0.0')

            # Meter
            metric_exporter = OTLPMetricExporter(endpoint=otlp_endpoint, insecure=True)
            metric_reader = PeriodicExportingMetricReader(metric_exporter, export_interval_millis=15000)
            meter_provider = MeterProvider(resource=resource, metric_readers=[metric_reader])
            metrics.set_meter_provider(meter_provider)
            self._meter = metrics.get_meter(f'catvrf-spark-{self.job_name}', '1.0.0')

            logger.info(f"OTel initialized for {self.job_name}, endpoint: {otlp_endpoint}")
        except Exception as e:
            logger.warning(f"Failed to init OTel: {e}. Falling back to Prometheus Pushgateway.")
            self._tracer = None
            self._meter = None

    @contextmanager
    def span(self, name: str, **attributes):
        """Context manager for tracing a span"""
        attrs = {
            'correlation.id': self.correlation_id,
            'job.name': self.job_name,
            **attributes,
        }

        if self._tracer:
            with self._tracer.start_as_current_span(name, attributes=attrs) as span:
                start = time.time()
                try:
                    yield span
                except Exception as e:
                    span.set_attribute('error', True)
                    span.set_attribute('error.message', str(e))
                    if PROMETHEUS_AVAILABLE:
                        SPARK_JOB_ERRORS.labels(
                            job_name=self.job_name,
                            error_type=type(e).__name__
                        ).inc()
                    raise
                finally:
                    duration = time.time() - start
                    span.set_attribute('duration.seconds', duration)
        else:
            # No tracer, just time it
            start = time.time()
            try:
                yield None
            finally:
                duration = time.time() - start
                logger.info(f"Span {name} completed in {duration:.2f}s")

    def record_records_processed(self, count: int, operation: str = 'read'):
        """Record number of records processed"""
        if PROMETHEUS_AVAILABLE:
            SPARK_JOB_RECORDS.labels(
                job_name=self.job_name,
                operation=operation
            ).inc(count)

        if self._meter:
            counter = self._meter.create_counter(
                'catvrf.spark.records',
                description='Records processed by Spark job',
            )
            counter.add(count, {'job': self.job_name, 'operation': operation})

    def record_feature_freshness(self, feature_type: str, freshness_seconds: float):
        """Record feature store freshness"""
        if PROMETHEUS_AVAILABLE:
            FEATURE_STORE_FRESHNESS.labels(feature_type=feature_type).set(freshness_seconds)

    def set_progress(self, percent: float):
        """Set job progress gauge"""
        if PROMETHEUS_AVAILABLE:
            SPARK_JOB_PROGRESS.labels(job_name=self.job_name).set(percent)

    def finish(self, status: str = 'success', records_count: int = 0):
        """Finish job and push metrics to Pushgateway"""
        duration = time.time() - self.start_time

        if PROMETHEUS_AVAILABLE:
            SPARK_JOB_DURATION.labels(
                job_name=self.job_name,
                status=status
            ).observe(duration)

            # Push to Pushgateway for Prometheus scrape
            pushgateway_url = os.environ.get('PUSHGATEWAY_URL', 'http://pushgateway:9091')
            try:
                push_to_gateway(
                    pushgateway_url,
                    job=f'catvrf-spark-{self.job_name}',
                    grouping_key={'correlation_id': self.correlation_id},
                )
                logger.info(f"Pushed metrics to Pushgateway: {pushgateway_url}")
            except Exception as e:
                logger.warning(f"Failed to push to Pushgateway: {e}")

        logger.info(
            f"Job {self.job_name} finished: status={status}, "
            f"duration={duration:.2f}s, records={records_count}, "
            f"correlation_id={self.correlation_id}"
        )


def instrument_spark_session(spark, job_name: str):
    """
    Configure Spark session with Prometheus metrics sink.
    Adds Spark metrics4j Prometheus exporter.
    """
    spark.conf.set("spark.metrics.conf.*.sink.prometheus.class",
                   "org.apache.spark.metrics.sink.prometheus.PrometheusSink")
    spark.conf.set("spark.metrics.conf.*.sink.prometheus.port", "4040")
    spark.conf.set("spark.metrics.conf.master.sink.prometheus.port", "4040")

    # Enable Spark UI for debugging
    spark.conf.set("spark.ui.enabled", "true")
    spark.conf.set("spark.ui.port", "4040")

    # Propagate correlation ID through Spark
    correlation_id = os.environ.get('CORRELATION_ID', f'spark_{job_name}_{int(time.time())}')
    spark.conf.set("spark.bigdata.correlation_id", correlation_id)

    return spark
