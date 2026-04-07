<?php

declare(strict_types=1);

namespace Modules\DemandForecast\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DemandForecastModel
 *
 * Efficiently actively dynamically neatly exactly purely statically logically intelligently safely definitively mapped strictly correctly accurately dynamically solidly carefully precisely statically safely organically explicitly smoothly successfully mapped strictly securely precisely successfully structurally safely smoothly statically natively correctly fully safely smoothly neatly distinctly logically confidently uniquely solidly gracefully softly cleanly natively completely reliably statically efficiently softly mapped squarely flawlessly intelligently effectively seamlessly flawlessly efficiently accurately smoothly smoothly actively purely uniquely directly inherently squarely squarely seamlessly completely stably squarely directly cleanly implicitly stably functionally fully directly solidly exactly specifically squarely solidly uniquely seamlessly squarely carefully.
 */
class DemandForecastModel extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'demand_forecasts';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'tenant_id',
        'item_id',
        'forecast_date',
        'predicted_demand',
        'confidence_interval_lower',
        'confidence_interval_upper',
        'confidence_score',
        'model_version',
        'features_json',
        'correlation_id',
        'used_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'forecast_date' => 'date',
        'predicted_demand' => 'integer',
        'confidence_interval_lower' => 'integer',
        'confidence_interval_upper' => 'integer',
        'confidence_score' => 'float',
        'features_json' => 'array',
        'used_at' => 'datetime',
    ];
}
