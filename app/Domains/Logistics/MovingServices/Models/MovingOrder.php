<?php declare(strict_types=1);

namespace App\Domains\Logistics\MovingServices\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MovingOrder extends Model
{

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'moving_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'company_id',
            'customer_id',
            'correlation_id',
            'status',
            'total_kopecks',
            'payout_kopecks',
            'payment_status',
            'move_date',
            'duration_hours',
            'from_address',
            'to_address',
            'tags',
        ];

        protected $casts = [
            'total_kopecks' => 'integer',
            'payout_kopecks' => 'integer',
            'move_date' => 'datetime',
            'duration_hours' => 'integer',
            'tags' => 'json',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('moving_orders.tenant_id', tenant()->id));
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
