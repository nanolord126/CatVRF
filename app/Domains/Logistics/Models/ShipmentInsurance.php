declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * ShipmentInsurance
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentInsurance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shipment_insurance';

    protected $fillable = [
        'tenant_id',
        'shipment_id',
        'insurance_amount',
        'premium',
        'status',
        'claim_reason',
        'claim_amount',
        'claim_date',
        'paid_date',
        'correlation_id',
    ];

    protected $casts = [
        'insurance_amount' => 'float',
        'premium' => 'float',
        'claim_amount' => 'float',
        'claim_date' => 'datetime',
        'paid_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
