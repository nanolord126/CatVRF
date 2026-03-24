<?php declare(strict_types=1);

namespace App\Domains\CoffeeShops\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CoffeeOrder extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'coffee_orders';
    protected $fillable = ['uuid', 'tenant_id', 'shop_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'items_json', 'delivery_type', 'tags'];
    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'items_json' => 'json', 'tags' => 'json'];

    public function shop() { return $this->belongsTo(CoffeeShop::class, 'shop_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('coffee_orders.tenant_id', tenant()->id));
    }
}
