<?php declare(strict_types=1);

namespace App\Domains\SportingGoods\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class SportStore extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'sport_stores';
    protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'address', 'phone', 'latitude', 'longitude', 'is_verified', 'commission_percent', 'min_order', 'tags'];
    protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'latitude' => 'float', 'longitude' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

    public function products() { return $this->hasMany(SportProduct::class, 'store_id'); }
    public function orders() { return $this->hasMany(SportOrder::class, 'store_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('sport_stores.tenant_id', tenant()->id));
    }
}
