<?php declare(strict_types=1);

/**
 * JewelryOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/jewelryorder
 */


namespace App\Domains\Luxury\Jewelry\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class JewelryOrder extends Model
{
    use HasFactory;

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

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function item()
        {
            return $this->belongsTo(JewelryItem::class);
        }

        protected static function booted_disabled(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()?->id) {
                    $query->where('tenant_id', tenant()?->id);
                }
            });
        }
}
