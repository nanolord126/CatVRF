<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CorporateClient extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'corporate_clients';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'company_name', 'inn', 'kpp', 'ogrn', 'address',
            'contact_person', 'phone', 'email', 'logo_url',
            'employee_count', 'status', 'tags',
        ];
        protected $casts = [
            'employee_count' => 'int',
            'tags'           => 'json',
        ];
        protected $hidden = ['inn', 'kpp', 'ogrn'];

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
