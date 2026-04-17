<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;



/**
     * JewelryReview (Layer 1/9)
     */
final class JewelryReview extends Model
{
        use JewelryDomainTrait, TenantScoped;

        protected $table = 'jewelry_reviews';
        protected $fillable = ['uuid', 'tenant_id', 'product_id', 'user_id', 'rating', 'comment', 'photos', 'is_verified_purchase', 'correlation_id'];
        protected $casts = [
            'photos' => 'array',
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(JewelryProduct::class, 'product_id');
        }
}
