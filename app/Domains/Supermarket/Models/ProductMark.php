<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ProductMark extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'gtin',
        'data_matrix',
        'status',
        'introduced_at',
        'withdrawn_at',
        'batch_number',
        'production_date',
        'expiration_date',
        'tenant_id',
    ];

    protected $casts = [
        'introduced_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'production_date' => 'datetime',
        'expiration_date' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'in_circulation');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    public function markAsWithdrawn(): void
    {
        $this->update([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);
    }
}
