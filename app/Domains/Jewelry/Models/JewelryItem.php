<?php declare(strict_types=1);

namespace App\Domains\Jewelry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class JewelryItem extends Model
{
    use SoftDeletes;

    protected $table = 'jewelry_items';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'name', 'sku', 'price', 'metal',
        'stone', 'certificate_code', 'weight_grams', 'description', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'tags' => 'json',
        'price' => 'integer',
        'weight_grams' => 'float',
    ];

    public function booted(): void
    {
        $this->addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
    }
}
