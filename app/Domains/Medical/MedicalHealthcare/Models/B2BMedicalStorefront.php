<?php

declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Models;

use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SoftDeletes;

final class B2BMedicalStorefront extends Model
{

    use HasFactory;

    use SoftDeletes;

        protected $table = 'b2b_medical_storefronts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'company_name',
            'inn',
            'description',
            'service_categories',
            'wholesale_discount',
            'min_order_amount',
            'is_verified',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'service_categories' => 'json',
            'tags' => 'json',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'wholesale_discount' => 'decimal:2',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', (function_exists('tenant') && tenant()) ? tenant()->id : null));
        }

        public function b2bOrders(): HasMany
        {
            return $this->hasMany(B2BMedicalOrder::class, 'b2b_medical_storefront_id');
        }
    }
