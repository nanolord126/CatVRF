<?php

namespace App\Models\B2B;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\BaseTenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchaseOrder extends BaseTenantModel
{
    protected $fillable = [
        'supplier_id', 'creator_id', 'order_number', 'status', 
        'total_amount', 'payment_status', 'expected_delivery_at', 'correlation_id'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'expected_delivery_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->order_number = 'PO-' . strtoupper(Str::random(8));
            $model->creator_id = auth()->id() ?? 1; // Default creator if AI (System)
        });
    }

    /** Поставщик заказа */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Создатель заказа */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /** Состав заказа */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** Смена статуса с логом */
    public function changeStatus(string $status)
    {
        $this->update(['status' => $status]);
        // Тут можно дергать AI-прогнозист доставки
    }
}








