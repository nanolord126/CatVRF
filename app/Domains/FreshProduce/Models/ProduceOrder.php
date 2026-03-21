<?php declare(strict_types=1);

namespace App\Domains\FreshProduce\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель заказа свежих продуктов — КАНОН 2026.
 * Статусы: pending → confirmed → packed → in_delivery → delivered → cancelled
 */
final class ProduceOrder extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'produce_orders';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'client_id',
        'farm_supplier_id',
        'subscription_id',
        'uuid',
        'correlation_id',
        'idempotency_key',
        'items',
        'total_amount',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'delivery_date',
        'delivery_slot',
        'status',
        'payment_status',
        'payment_transaction_id',
        'quality_photo_url',
        'quality_checked_at',
        'packed_at',
        'delivered_at',
        'courier_id',
        'tags',
        'meta',
    ];

    protected $hidden = [];

    protected $casts = [
        'total_amount'       => 'integer',
        'items'              => 'array',
        'tags'               => 'array',
        'meta'               => 'array',
        'delivery_lat'       => 'float',
        'delivery_lng'       => 'float',
        'delivery_date'      => 'date',
        'quality_checked_at' => 'datetime',
        'packed_at'          => 'datetime',
        'delivered_at'       => 'datetime',
    ];

    public function farmSupplier(): BelongsTo
    {
        return $this->belongsTo(FarmSupplier::class, 'farm_supplier_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ProduceSubscription::class, 'subscription_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
