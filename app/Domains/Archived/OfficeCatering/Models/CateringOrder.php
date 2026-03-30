<?php declare(strict_types=1);

namespace App\Domains\Archived\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CateringOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'catering_orders';


        protected $fillable = ['uuid', 'tenant_id', 'catering_company_id', 'client_id', 'correlation_id', 'office_name', 'office_address', 'delivery_datetime', 'person_count', 'status', 'total_kopecks', 'commission_kopecks', 'payout_kopecks', 'payment_status', 'menu_items_json', 'special_requests', 'tags'];


        protected $casts = ['person_count' => 'integer', 'total_kopecks' => 'integer', 'commission_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'delivery_datetime' => 'datetime', 'menu_items_json' => 'json', 'tags' => 'json'];


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function company() { return $this->belongsTo(CateringCompany::class, 'catering_company_id'); }


        protected static function booted(): void


        {


            static::addGlobalScope('tenant', fn($q) => $q->where('catering_orders.tenant_id', tenant()->id));


        }
}
