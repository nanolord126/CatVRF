<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BMedicalHealthcareOrder extends Model
{

    use HasFactory;

    use SoftDeletes;

        protected $table = 'b2b_medical_healthcare_orders';

        protected $fillable = [
            'uuid', 'tenant_id', 'b2b_medical_healthcare_storefront_id', 'user_id', 'order_number',
            'company_contact_person', 'company_phone', 'items', 'total_amount',
            'commission_amount', 'status', 'rejection_reason', 'correlation_id', 'tags'
        ];

        protected $casts = [
            'items' => 'json',
            'total_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tags' => 'json',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant() && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function storefront(): BelongsTo
        {
            return $this->belongsTo(B2BMedicalHealthcareStorefront::class, 'b2b_medical_healthcare_storefront_id');
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
