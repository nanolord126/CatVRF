<?php declare(strict_types=1);

namespace App\Domains\ReadyMeals\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class MealOrder extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'meal_orders';
    protected $fillable = ['uuid', 'tenant_id', 'provider_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'items_json', 'delivery_datetime', 'tags'];
    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'items_json' => 'json', 'delivery_datetime' => 'datetime', 'tags' => 'json'];

    public function provider() { return $this->belongsTo(MealProvider::class, 'provider_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('meal_orders.tenant_id', tenant()->id));
    }
}
