<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Carbon\Carbon;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
     * ElectronicsReview - User feedback.
     */
final class ElectronicsReview extends Model
{
        protected $table = 'electronics_reviews';

        protected $fillable = [
            'tenant_id',
            'product_id',
            'user_id',
            'rating',
            'comment',
            'images',
            'is_verified_purchase',
            'correlation_id',
        ];

        protected $casts = [
            'rating' => 'integer',
            'images' => 'json',
            'is_verified_purchase' => 'boolean',
        ];

        public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
        {
            return $this->belongsTo(ElectronicsProduct::class, 'product_id');
        }
}
