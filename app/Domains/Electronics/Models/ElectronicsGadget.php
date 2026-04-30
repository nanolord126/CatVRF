<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ElectronicsGadget - Technical specs extension for smart products.
 */
final class ElectronicsGadget extends Model
{
    protected $table = 'electronics_gadgets';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'os_version',
        'cpu_model',
        'ram_gb',
        'storage_gb',
        'screen_size_inch',
        'battery_mah',
        'is_5g_ready',
    ];

    protected $casts = [
        'ram_gb' => 'integer',
        'storage_gb' => 'integer',
        'screen_size_inch' => 'float',
        'battery_mah' => 'integer',
        'is_5g_ready' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ElectronicsProduct::class, 'product_id');
    }
}
