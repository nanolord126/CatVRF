<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HotelRoomType extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'hotel_room_types';

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'capacity',
        'price_per_night',
        'amenities',
        'availability_count',
        'status',
        'tags',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'price_per_night' => 'integer',
        'amenities' => 'json',
        'tags' => 'json',
        'availability_count' => 'integer',
    ];

    /**
     * Отель
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id', 'id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
