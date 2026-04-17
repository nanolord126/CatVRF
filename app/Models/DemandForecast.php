<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Прогноз спроса (хранит результаты ML-прогнозирования для post-factum анализа)
 *
 * @package App\Models
 */
final class DemandForecast extends Model
{

    protected $table = 'demand_forecasts';

    protected $fillable = [
        'item_id',
        'forecast_date',
        'predicted_demand',
        'confidence_interval_lower',
        'confidence_interval_upper',
        'confidence_score',
        'model_version',
        'correlation_id',
        'features_json',
    ];

    protected $casts = [
        'features_json' => 'array',
        'confidence_score' => 'decimal:4',
        'forecast_date' => 'date',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
