<?php declare(strict_types=1);

namespace App\Domains\CoffeeShops\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CoffeeDrink extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'coffee_drinks';
    protected $fillable = ['uuid', 'tenant_id', 'shop_id', 'correlation_id', 'name', 'price_kopecks', 'description', 'tags'];
    protected $casts = ['price_kopecks' => 'integer', 'tags' => 'json'];

    public function shop() { return $this->belongsTo(CoffeeShop::class, 'shop_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('coffee_drinks.tenant_id', tenant()->id));
    }
}
