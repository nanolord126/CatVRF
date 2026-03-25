declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final /**
 * PricingRule
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PricingRule extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'room_type_id',
        'name',
        'type',
        'date_from',
        'date_to',
        'multiplier',
        'min_nights',
        'advance_days',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'multiplier' => 'float',
        'is_active' => 'boolean',
    ];

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
