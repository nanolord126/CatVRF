<?php

namespace App\Models\B2B;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\BaseTenantModel;
use Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends BaseTenantModel
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'quantity', 
        'unit_price', 'total_price'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /** Заказ, к которому прикреплен товар */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** Ссылка на продукт из WMS */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Расчет стоимости */
    public function calculateTotal()
    {
        $this->total_price = $this->quantity * $this->unit_price;
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->calculateTotal();
        });
    }
}








