<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupermarketOrder extends Model
{
    protected $table = 'supermarket_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'sub_vertical',
        'status',
        'total_amount',
        'delivery_cost',
        'delivery_eta',
        'delivery_address',
        'delivery_slot',
        'cold_chain_required',
        'correlation_id',
        'payment_id',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'delivery_cost' => 'integer',
        'delivery_eta' => 'integer',
        'cold_chain_required' => 'boolean',
        'is_b2b' => 'boolean',
        'delivery_address' => 'array',
        'delivery_slot' => 'array',
        'items' => 'array',
        'b2b_documents' => 'array',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function b2bCompany(): BelongsTo
    {
        return $this->belongsTo(B2BCompany::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Inventory\Models\Warehouse::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Payment\Models\Payment::class);
    }
}
