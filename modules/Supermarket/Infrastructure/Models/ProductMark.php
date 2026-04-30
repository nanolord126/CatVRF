<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMark extends Model
{
    protected $table = 'supermarket_product_marks';

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
        'serial_number',
    ];

    protected $casts = [
        'introduced_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'production_date' => 'date',
        'expiration_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'introduced');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function scopeByGtin($query, string $gtin)
    {
        return $query->where('gtin', $gtin);
    }

    public function scopeByBatch($query, string $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }

    public function isActive(): bool
    {
        return $this->status === 'introduced';
    }

    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }
}
