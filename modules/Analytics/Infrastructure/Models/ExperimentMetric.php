<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Experiment Metric Model
 *
 * Stores daily aggregated metrics for experiment variants.
 * Production-ready with statistical analysis fields and proper indexing.
 */
final class ExperimentMetric extends Model
{
    use HasFactory;

    protected $table = 'experiment_metrics';

    protected $fillable = [
        'experiment_id',
        'variant_id',
        'metric_date',
        'metric_type',
        'metric_value',
        'sample_size',
        'mean',
        'std_dev',
        'confidence_interval_lower',
        'confidence_interval_upper',
        'confidence_level',
        'cumulative_value',
        'cumulative_mean',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'metric_value' => 'decimal:2',
        'sample_size' => 'integer',
        'mean' => 'decimal:2',
        'std_dev' => 'decimal:2',
        'confidence_interval_lower' => 'decimal:2',
        'confidence_interval_upper' => 'decimal:2',
        'confidence_level' => 'float',
        'cumulative_value' => 'decimal:2',
        'cumulative_mean' => 'decimal:2',
    ];

    /**
     * Relationship: Metric belongs to experiment.
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Relationship: Metric belongs to variant.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class);
    }

    /**
     * Scope: Filter by experiment.
     */
    public function scopeForExperiment($query, int $experimentId)
    {
        return $query->where('experiment_id', $experimentId);
    }

    /**
     * Scope: Filter by variant.
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope: Filter by metric type.
     */
    public function scopeForMetricType($query, string $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('metric_date', [$start, $end]);
    }

    /**
     * Scope: Get metrics from date onwards.
     */
    public function scopeFromDate($query, $date)
    {
        return $query->where('metric_date', '>=', $date);
    }

    /**
     * Get lift compared to control (requires control metric).
     */
    public function getLiftRelativeTo(?ExperimentMetric $control): ?float
    {
        if (!$control || $control->mean === 0 || $this->mean === null) {
            return null;
        }

        return (($this->mean - $control->mean) / $control->mean) * 100;
    }

    /**
     * Get formatted metric type for display.
     */
    public function getFormattedMetricType(): string
    {
        return str_replace('_', ' ', ucwords($this->metric_type));
    }
}
