<?php

declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\SoftDeletes;
final class B2BCourseOrder extends Model
{
        use SoftDeletes;

        protected $table = 'b2b_course_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'b2b_course_storefront_id',
            'order_number',
            'company_contact_person',
            'company_phone',
            'items_json',
            'total_amount',
            'commission_amount',
            'discount_amount',
            'status',
            'expected_delivery_at',
            'notes',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'items_json' => 'json',
            'tags' => 'json',
            'total_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'expected_delivery_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', (function_exists('tenant') && tenant()) ? tenant()->id : null));
        }

        public function storefront(): BelongsTo
        {
            return $this->belongsTo(B2BCourseStorefront::class, 'b2b_course_storefront_id');
        }
}
