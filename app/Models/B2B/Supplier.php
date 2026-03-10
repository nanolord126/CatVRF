<?php

namespace App\Models\B2B;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends BaseTenantModel
{
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'tax_id', 
        'geo_location', 'credit_limit', 'status', 'correlation_id'
    ];

    protected $casts = [
        'geo_location' => 'array',
        'credit_limit' => 'decimal:2',
    ];

    /** Список заказов у данного поставщика */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}








