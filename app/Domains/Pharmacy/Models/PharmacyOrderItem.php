<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PharmacyOrderItem extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_order_items';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'order_id',
            'medication_id',
            'quantity',
            'price_at_order',
            'correlation_id'
        ];

        protected $casts = [
            'price_at_order' => 'integer',
            'quantity' => 'integer'
        ];

        public function order(): BelongsTo
        {
            return $this->belongsTo(PharmacyOrder::class, 'order_id');
        }

        public function medication(): BelongsTo
        {
            return $this->belongsTo(Medication::class, 'medication_id');
        }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
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