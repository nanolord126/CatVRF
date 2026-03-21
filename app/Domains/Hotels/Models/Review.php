<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Review extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'hotel_id',
        'guest_id',
        'rating',
        'title',
        'content',
        'categories',
        'verified_booking',
        'published_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'categories' => 'json',
        'verified_booking' => 'boolean',
        'published_at' => 'datetime',
        'tags' => 'collection',
    ];

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
