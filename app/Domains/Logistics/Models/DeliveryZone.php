declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * DeliveryZone
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeliveryZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'delivery_zones';

    protected $fillable = [
        'tenant_id',
        'courier_service_id',
        'zone_name',
        'polygon',
        'surge_multiplier',
        'estimated_delivery_hours',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'surge_multiplier' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function courierService(): BelongsTo
    {
        return $this->belongsTo(CourierService::class);
    }
}
