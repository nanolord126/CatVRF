<?php declare(strict_types=1);

namespace App\Domains\Logistics\MovingServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MovingOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('moving_orders.tenant_id', tenant()->id));
        }
}
