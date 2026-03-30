<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CateringCompany extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'catering_companies';
        protected $fillable = [
            'uuid', 'tenant_id', 'business_group_id', 'correlation_id',
            'name', 'owner_id', 'description', 'address', 'phone',
            'latitude', 'longitude', 'certification_number', 'is_verified',
            'commission_percent', 'min_order_amount', 'min_person_count',
            'max_person_count', 'delivery_zones', 'schedule', 'tags',
        ];

        protected $casts = [
            'is_verified' => 'boolean',
            'commission_percent' => 'float',
            'latitude' => 'float', 'longitude' => 'float',
            'min_order_amount' => 'integer',
            'min_person_count' => 'integer',
            'max_person_count' => 'integer',
            'delivery_zones' => 'json',
            'schedule' => 'json',
            'tags' => 'json',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function orders() { return $this->hasMany(CateringOrder::class, 'catering_company_id'); }
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function menus() { return $this->hasMany(CateringMenu::class, 'catering_company_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('catering_companies.tenant_id', tenant()->id));
        }
}
