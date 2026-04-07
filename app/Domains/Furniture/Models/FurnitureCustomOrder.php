<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * FurnitureCustomOrder Model
     */
final class FurnitureCustomOrder extends Model
{
        use FurnitureDomainTrait;

        protected $table = 'furniture_custom_orders';

        protected $fillable = [
            'uuid', 'tenant_id', 'user_id', 'room_type_id',
            'status', 'total_amount', 'ai_specification',
            'room_photo_analysis', 'include_assembly', 'correlation_id'
        ];

        protected $casts = [
            'ai_specification' => 'json',
            'room_photo_analysis' => 'json',
            'include_assembly' => 'boolean',
            'total_amount' => 'integer',
        ];

        public function roomType(): BelongsTo
        {
            return $this->belongsTo(FurnitureRoomType::class);
        }
    }
