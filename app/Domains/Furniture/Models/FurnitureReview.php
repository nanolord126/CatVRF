<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Models;

use FurnitureDomainTrait, SoftDeletes;
use FurnitureDomainTrait;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * FurnitureReview Model
     */
final class FurnitureReview extends Model
{
        use FurnitureDomainTrait;

        protected $table = 'furniture_reviews';

        protected $fillable = [
            'uuid', 'tenant_id', 'user_id', 'furniture_product_id',
            'rating', 'comment', 'photos', 'is_verified_purchase', 'correlation_id'
        ];

        protected $casts = [
            'photos' => 'json',
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(FurnitureProduct::class, 'furniture_product_id');
        }
}
