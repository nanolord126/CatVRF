<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Agro\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
final class AgroProduct extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'agro_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'farm_id',
            'name',
            'sku',
            'category',      // meat, dairy, produce, grain
            'price_cents',
            'unit',          // kg, liter, ton
            'current_stock',
            'min_stock_alert',
            'properties',    // fat_percent, organic, certified
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'price_cents' => 'integer',
            'current_stock' => 'float',
            'properties' => 'json',
            'tags' => 'json',
        ];

    

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function farm(): BelongsTo
        {
            return $this->belongsTo(AgroFarm::class, 'farm_id');
        }
}
