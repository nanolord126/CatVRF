<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TenderLot extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_tender_lots';

    protected $fillable = [
        'uuid',
        'tender_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit',
        'allowed_units',
        'starting_price',
        'reserve_price',
        'request_for_price',
        'specifications',
        'brand',
        'manufacturer',
        'country_of_origin',
        'expiry_date',
        'requires_cold_chain',
        'required_documents',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'quantity' => 'decimal:3',
        'starting_price' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'request_for_price' => 'boolean',
        'specifications' => 'array',
        'expiry_date' => 'date',
        'requires_cold_chain' => 'boolean',
        'required_documents' => 'array',
        'allowed_units' => 'array',
        'metadata' => 'array',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class, 'tender_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(TenderBid::class, 'tender_lot_id');
    }
}
