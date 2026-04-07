<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JewelryDomainTrait, SoftDeletes;
use JewelryDomainTrait;

/**
     * JewelryCustomOrder (Layer 1/9)
     */
final class JewelryCustomOrder extends Model
{
        use JewelryDomainTrait;

        protected $table = 'jewelry_custom_orders';
        protected $fillable = [
            'uuid', 'tenant_id', 'store_id', 'user_id', 'status', 'customer_name', 'customer_phone',
            'estimated_price', 'final_price', 'ai_specification', 'user_notes', 'reference_photo_path', 'correlation_id'
        ];
        protected $casts = [
            'ai_specification' => 'array',
            'estimated_price' => 'integer',
            'final_price' => 'integer',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(JewelryStore::class, 'store_id');
        }
    }
