<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionDemandForecast extends Model
{
    protected $table = 'fashion_demand_forecasts';
    protected $fillable = ['product_id', 'tenant_id', 'forecast_data', 'forecasted_at', 'correlation_id'];
    protected $casts = ['forecast_data' => 'array'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
