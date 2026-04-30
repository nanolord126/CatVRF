<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ServiceModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beauty_services';

    protected $fillable = [
        'venue_id',
        'category_id',
        'name',
        'slug',
        'description',
        'duration_minutes',
        'buffer_minutes',
        'price',
        'discount_price',
        'currency',
        'required_supplies',
        'is_active',
        'requires_photo_before',
        'requires_photo_after',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'required_supplies' => 'array',
        'is_active' => 'boolean',
        'requires_photo_before' => 'boolean',
        'requires_photo_after' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategoryModel::class, 'category_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class);
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    public function getTotalDurationAttribute(): int
    {
        return $this->duration_minutes + $this->buffer_minutes;
    }
}
