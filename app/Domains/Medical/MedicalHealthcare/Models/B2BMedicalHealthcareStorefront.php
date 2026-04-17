<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BMedicalHealthcareStorefront extends Model
{



        protected $table = 'b2b_medical_healthcare_storefronts';

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
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant() && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function b2bOrders(): HasMany
        {
            return $this->hasMany('App\Domains\Medical\MedicalHealthcare\Models\B2BMedicalHealthcareOrder');
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
