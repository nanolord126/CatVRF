<?php declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class ElectronicOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'electronic_orders';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'product_id', 'client_id', 'serial_num', 'imei_num',
        'total_price', 'delivery_date', 'status', 'idempotency_key', 'tags',
    ];
    protected $casts = [
        'total_price'   => 'int',
        'delivery_date' => 'datetime',
        'tags'          => 'json',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ElectronicProduct::class, 'product_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
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
