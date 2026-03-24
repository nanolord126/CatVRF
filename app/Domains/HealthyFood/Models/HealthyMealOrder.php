<?php declare(strict_types=1);

namespace App\Domains\HealthyFood\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class HealthyMealOrder extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'healthy_meal_orders';
    protected $fillable = ['uuid', 'tenant_id', 'company_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'items_json', 'delivery_datetime', 'tags'];
    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'items_json' => 'json', 'delivery_datetime' => 'datetime', 'tags' => 'json'];

    public function company() { return $this->belongsTo(DietCompany::class, 'company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('healthy_meal_orders.tenant_id', tenant()->id));
    }
}
