<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Models\User;

class RestaurantOrder extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'restaurant_orders';

    protected $fillable = [
        'user_id', 'subtotal', 'delivery_fee', 'delivery_address', 
        'delivery_geo', 'status', 'correlation_id'
    ];

    protected $casts = [
        'delivery_geo' => 'array',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}








