<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Reservation extends Model
{

/** @param \Illuminate\Database\Eloquent\Builder<self> $query */
    public function scopeExpired($query): void
    {
        $query->where('expires_at', '<', Carbon::now());
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}

