<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Medicine extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes, TenantScoped;


        protected $table = "pharmacy_medicines";


        protected $fillable = [


            "uuid",


            "tenant_id",


            "business_group_id",


            "correlation_id",


            "name",


            "sku",


            "barcode",


            "description",


            "active_ingredient",


            "dosage",


            "form_factor",


            "is_prescription_required",


            "is_refrigerated",


            "price_kopecks",


            "current_stock",


            "min_stock_threshold",


            "tags",


            "meta"


        ];


        protected $casts = [


            "is_prescription_required" => "boolean",


            "is_refrigerated" => "boolean",


            "price_kopecks" => "integer",


            "current_stock" => "integer",


            "tags" => "array",


            "meta" => "array",


        ];


        /**


         * Глобальный скопинг для тенанта уже в трейте.


         */


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function pharmacy(): \Illuminate\Database\Eloquent\Relations\BelongsTo


        {


            return $this->belongsTo(Pharmacy::class);


        }
}
