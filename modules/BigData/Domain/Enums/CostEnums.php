<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Enums;

/**
 * BigData Cost Monitoring Enumerations
 *
 * All domain enums for the FinOps / Cost Monitoring subsystem.
 * Follows the same pattern as MonitoringEnums.php.
 */

// ============================================================================
// Cloud Provider
// ============================================================================

enum CloudProvider: string
{
    case AWS = 'aws';
    case GCP = 'gcp';
    case Azure = 'azure';
    case SelfHosted = 'self_hosted';

    public function billingApiClass(): string
    {
        return match ($this) {
            self::AWS => \Modules\BigData\Infrastructure\Adapters\AWSCostExplorerAdapter::class,
            self::GCP => \Modules\BigData\Infrastructure\Adapters\GCPBillingAdapter::class,
            self::Azure => \Modules\BigData\Infrastructure\Adapters\AzureCostManagementAdapter::class,
            self::SelfHosted => \Modules\BigData\Infrastructure\Adapters\SelfHostedBillingAdapter::class,
        };
    }

    public function displayName(): string
    {
        return match ($this) {
            self::AWS => 'Amazon Web Services',
            self::GCP => 'Google Cloud Platform',
            self::Azure => 'Microsoft Azure',
            self::SelfHosted => 'Self-Hosted / On-Premises',
        };
    }

    public static function fromEnv(): self
    {
        return self::from(config('bigdata.cost.cloud_provider', 'self_hosted'));
    }
}

// ============================================================================
// Cost Category
// ============================================================================

enum CostCategory: string
{
    case Compute = 'compute';
    case Storage = 'storage';
    case Network = 'network';
    case License = 'license';
    case Support = 'support';
    case Other = 'other';

    public function toPrometheusLabel(): string
    {
        return $this->value;
    }

    public function typicalBudgetShare(): float
    {
        return match ($this) {
            self::Compute => 0.45,
            self::Storage => 0.25,
            self::Network => 0.10,
            self::License => 0.10,
            self::Support => 0.05,
            self::Other => 0.05,
        };
    }
}

// ============================================================================
// Bounded Context (Cost Attribution)
// ============================================================================

enum BoundedContext: string
{
    case Ingestion = 'ingestion';
    case ClickHouseStorage = 'clickhouse_storage';
    case ClickHouseCompute = 'clickhouse_compute';
    case Kafka = 'kafka';
    case SparkML = 'spark_ml';
    case CLVTraining = 'clv_training';
    case CLVInference = 'clv_inference';
    case ABTest = 'abtest';
    case SellerDashboard = 'seller_dashboard';
    case AnalyticsAPI = 'analytics_api';
    case FeatureStore = 'feature_store';
    case Monitoring = 'monitoring';
    case Other = 'other';

    public function isMLRelated(): bool
    {
        return in_array($this, [self::CLVTraining, self::CLVInference, self::SparkML], true);
    }

    public function isSellerFacing(): bool
    {
        return in_array($this, [self::SellerDashboard, self::CLVInference, self::ABTest, self::AnalyticsAPI], true);
    }

    public function displayName(): string
    {
        return match ($this) {
            self::Ingestion => 'Data Ingestion (Kafka→ClickHouse)',
            self::ClickHouseStorage => 'ClickHouse Storage',
            self::ClickHouseCompute => 'ClickHouse Compute (Queries)',
            self::Kafka => 'Kafka Brokers',
            self::SparkML => 'Spark / ML Jobs',
            self::CLVTraining => 'CLV Model Training',
            self::CLVInference => 'CLV Predictions (Inference)',
            self::ABTest => 'A/B Testing',
            self::SellerDashboard => 'Seller Dashboard',
            self::AnalyticsAPI => 'Analytics API',
            self::FeatureStore => 'Feature Store',
            self::Monitoring => 'Monitoring & Observability',
            self::Other => 'Other',
        };
    }

    public function targetCostPerUnit(): ?float
    {
        return match ($this) {
            self::Ingestion => 0.000001,       // $1 per 1M events
            self::ClickHouseCompute => 0.0005,  // $0.0005 per query
            self::CLVInference => 0.001,        // $0.001 per prediction
            self::SellerDashboard => 0.02,      // $0.02 per dashboard load
            default => null,
        };
    }
}

// ============================================================================
// Budget Status
// ============================================================================

enum BudgetStatus: string
{
    case UnderBudget = 'under_budget';
    case OnTrack = 'on_track';
    case Approaching = 'approaching';    // > 80%
    case OverBudget = 'over_budget';      // > 100%
    case CriticalOver = 'critical_over'; // > 120%

    public function isOverBudget(): bool
    {
        return in_array($this, [self::OverBudget, self::CriticalOver], true);
    }

    public static function fromUtilization(float $percent): self
    {
        return match (true) {
            $percent < 0.6 => self::UnderBudget,
            $percent < 0.8 => self::OnTrack,
            $percent < 1.0 => self::Approaching,
            $percent < 1.2 => self::OverBudget,
            default => self::CriticalOver,
        };
    }

    public function toAlertSeverity(): AlertSeverity
    {
        return match ($this) {
            self::UnderBudget, self::OnTrack => AlertSeverity::Ok,
            self::Approaching => AlertSeverity::Info,
            self::OverBudget => AlertSeverity::Warning,
            self::CriticalOver => AlertSeverity::Critical,
        };
    }
}

// ============================================================================
// Optimization Type
// ============================================================================

enum OptimizationType: string
{
    case TTLReduction = 'ttl_reduction';
    case CompressionIncrease = 'compression_increase';
    case TieredStorage = 'tiered_storage';
    case ReplicationDecrease = 'replication_decrease';
    case ScheduledMerges = 'scheduled_merges';
    case KafkaRetention = 'kafka_retention';
    case SpotInstances = 'spot_instances';
    case ReservedInstances = 'reserved_instances';
    case QueryOptimization = 'query_optimization';
    case MVOptimization = 'mv_optimization';
    case AutoScaleDown = 'auto_scale_down';

    public function riskLevel(): RiskLevel
    {
        return match ($this) {
            self::TTLReduction => RiskLevel::Medium,
            self::CompressionIncrease => RiskLevel::Low,
            self::TieredStorage => RiskLevel::Low,
            self::ReplicationDecrease => RiskLevel::High,
            self::ScheduledMerges => RiskLevel::Low,
            self::KafkaRetention => RiskLevel::Medium,
            self::SpotInstances => RiskLevel::Medium,
            self::ReservedInstances => RiskLevel::Low,
            self::QueryOptimization => RiskLevel::Low,
            self::MVOptimization => RiskLevel::Medium,
            self::AutoScaleDown => RiskLevel::Medium,
        };
    }

    public function typicalSavingsPercent(): float
    {
        return match ($this) {
            self::TTLReduction => 0.18,
            self::CompressionIncrease => 0.10,
            self::TieredStorage => 0.30,
            self::ReplicationDecrease => 0.15,
            self::ScheduledMerges => 0.05,
            self::KafkaRetention => 0.12,
            self::SpotInstances => 0.60,
            self::ReservedInstances => 0.30,
            self::QueryOptimization => 0.20,
            self::MVOptimization => 0.15,
            self::AutoScaleDown => 0.25,
        };
    }

    public function displayName(): string
    {
        return match ($this) {
            self::TTLReduction => 'Reduce TTL on raw tables',
            self::CompressionIncrease => 'Increase compression level',
            self::TieredStorage => 'Enable tiered storage (hot SSD + cold S3)',
            self::ReplicationDecrease => 'Reduce replication factor',
            self::ScheduledMerges => 'Schedule OPTIMIZE FINAL during off-peak',
            self::KafkaRetention => 'Reduce Kafka log retention',
            self::SpotInstances => 'Use spot/preemptible instances for Spark',
            self::ReservedInstances => 'Switch to reserved instances',
            self::QueryOptimization => 'Optimize expensive queries',
            self::MVOptimization => 'Optimize materialized views',
            self::AutoScaleDown => 'Auto-scale down during low traffic',
        };
    }
}

// ============================================================================
// Risk Level
// ============================================================================

enum RiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function isSafeToAutoApply(): bool
    {
        return $this === self::Low;
    }
}

// ============================================================================
// Cost Anomaly Type
// ============================================================================

enum CostAnomalyType: string
{
    case BudgetExceeded = 'budget_exceeded';
    case CostSpike = 'cost_spike';
    case StorageGrowth = 'storage_growth';
    case QueryCostSpike = 'query_cost_spike';
    case SellerOveruse = 'seller_overuse';
    case AnomalousPattern = 'anomalous_pattern';

    public function defaultSeverity(): AlertSeverity
    {
        return match ($this) {
            self::BudgetExceeded => AlertSeverity::Critical,
            self::CostSpike => AlertSeverity::Warning,
            self::StorageGrowth => AlertSeverity::Warning,
            self::QueryCostSpike => AlertSeverity::Warning,
            self::SellerOveruse => AlertSeverity::Info,
            self::AnomalousPattern => AlertSeverity::Info,
        };
    }
}

// ============================================================================
// Spark/ML Job Type
// ============================================================================

enum SparkJobType: string
{
    case CLVTraining = 'clv_training';
    case CLVInference = 'clv_inference';
    case ABTestAnalysis = 'abtest_analysis';
    case RFMSegmentation = 'rfm_segmentation';
    case FeatureEngineering = 'feature_engineering';
    case DataExport = 'data_export';
    case Maintenance = 'maintenance';

    public function isBillable(): bool
    {
        return $this !== self::Maintenance;
    }

    public function displayName(): string
    {
        return match ($this) {
            self::CLVTraining => 'CLV Model Training',
            self::CLVInference => 'CLV Batch Inference',
            self::ABTestAnalysis => 'A/B Test Analysis',
            self::RFMSegmentation => 'RFM Segmentation',
            self::FeatureEngineering => 'Feature Engineering',
            self::DataExport => 'Data Export (Parquet/S3)',
            self::Maintenance => 'Maintenance Jobs',
        };
    }
}
