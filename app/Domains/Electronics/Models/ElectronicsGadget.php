<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Carbon\Carbon;
use HasFactory, SoftDeletes;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * ElectronicsGadget - Technical specs extension for smart products.
     */
final class ElectronicsGadget extends Model
{
        protected $table = 'electronics_gadgets';

        protected $fillable = [
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

        public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
        {
            return $this->belongsTo(ElectronicsProduct::class, 'product_id');
        }
    }
