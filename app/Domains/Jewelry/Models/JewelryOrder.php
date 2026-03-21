<?php declare(strict_types=1);

namespace App\Domains\Jewelry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class JewelryOrder extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'jewelry_orders';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'item_id', 'user_id', 'quantity', 'total_price', 'status', 'meta'
    ];
    protected $casts = [
        'quantity' => 'int',
        'total_price' => 'int',
        'meta' => 'json',
    ];

    public function item()
    {
        return $this->belongsTo(JewelryItem::class);
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
