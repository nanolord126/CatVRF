<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenderReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'bid_id',
        'business_id',
        'supplier_id',
        'tenant_id',
        'rating',
        'review',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Relations
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(TenderBid::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    // Validation
    public function validateRating(): bool
    {
        return $this->rating >= 1 && $this->rating <= 5;
    }
}
