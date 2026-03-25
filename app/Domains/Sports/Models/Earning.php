declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;

final /**
 * Earning
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Earning extends Model
{
    protected $table = 'earnings';
    protected $fillable = [
        'tenant_id',
        'studio_id',
        'period_month',
        'period_year',
        'total_revenue',
        'total_commission',
        'studio_earnings',
        'total_bookings',
        'total_memberships_sold',
        'payout_initiated_at',
        'payout_completed_at',
        'payout_method',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => AsCollection::class,
        'total_revenue' => 'float',
        'total_commission' => 'float',
        'studio_earnings' => 'float',
        'payout_initiated_at' => 'datetime',
        'payout_completed_at' => 'datetime',
    ];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            $query->where('tenant_id', tenant('id'));
        });
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }
}
