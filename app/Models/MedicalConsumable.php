<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalConsumable extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = [
        'medical_card_id',
        'inventory_item_id', // links to inventory module
        'quantity',
        'unit_price',
        'total_price',
        'correlation_id',
    ];

    public function medicalCard(): BelongsTo
    {
        return $this->belongsTo(MedicalCard::class);
    }
    
    // Links to your existing inventory item model if it exists
    /*
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
    */
}









