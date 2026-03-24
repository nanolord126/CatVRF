<?php declare(strict_types=1);

namespace App\Domains\FreshProduce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class ProduceProduct extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'produce_products';
    protected $fillable = ['uuid', 'tenant_id', 'farm_id', 'correlation_id', 'name', 'price_kopecks', 'unit', 'stock', 'seasonal', 'is_organic', 'tags'];
    protected $casts = ['price_kopecks' => 'integer', 'stock' => 'float', 'is_organic' => 'boolean', 'tags' => 'json'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('produce_products.tenant_id', tenant()->id));
    }
}
