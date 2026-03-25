declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final /**
 * PayoutSchedule
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PayoutSchedule extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'hotel_id',
        'payout_delay_days',
        'payout_frequency',
        'last_payout_at',
        'next_payout_at',
        'correlation_id',
    ];

    protected $casts = [
        'last_payout_at' => 'datetime',
        'next_payout_at' => 'datetime',
        'payout_delay_days' => 'integer',
    ];

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
