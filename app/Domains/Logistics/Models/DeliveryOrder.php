<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class DeliveryOrder extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'delivery_orders';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'courier_id', 'user_id', 'address', 'status', 'price', 'meta'
    ];
    protected $casts = [
        'price' => 'int',
        'meta' => 'json',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
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
