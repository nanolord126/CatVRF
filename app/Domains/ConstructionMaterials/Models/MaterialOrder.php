<?php declare(strict_types=1);

namespace App\Domains\ConstructionMaterials\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class MaterialOrder extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'material_orders';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'material_id', 'user_id', 'quantity', 'total_price',
        'status', 'delivery_address', 'tracking_number', 'meta'
    ];
    protected $casts = [
        'quantity' => 'int',
        'total_price' => 'int',
        'meta' => 'json',
    ];

    public function material()
    {
        return $this->belongsTo(ConstructionMaterial::class);
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
