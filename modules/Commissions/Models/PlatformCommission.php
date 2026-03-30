<?php declare(strict_types=1);

namespace Modules\Commissions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PlatformCommission extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
