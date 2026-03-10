<?php

namespace Modules\Commissions\Models;

use Illuminate\Database\Eloquent\Model;

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
