declare(strict_types=1);

<?php

namespace Modules\Commissions\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PlatformCommission
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PlatformCommission extends Model
{
    protected $fillable = [
        'order_id',
        'total_amount',
        'commission_amount',
        'commission_percent',
        'owner_id',
        'owner_type',
    ];

    public function owner()
    {
        return $this->morphTo();
    }
}
