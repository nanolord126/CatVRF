<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionSustainabilityScore extends Model
{
    protected $table = 'fashion_sustainability_scores';

    protected $fillable = ['tenant_id', 'product_id', 'score', 'breakdown', 'calculated_at'];

    protected $casts = ['score' => 'decimal:2', 'breakdown' => 'array', 'calculated_at' => 'datetime'];

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
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
