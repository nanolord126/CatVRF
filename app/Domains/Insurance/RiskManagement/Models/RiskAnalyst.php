<?php declare(strict_types=1);

namespace App\Domains\Insurance\RiskManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RiskAnalyst extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'risk_analysts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'correlation_id',
            'name',
            'specialties',
            'certifications',
            'tags',
            'meta',
        ];

        protected $casts = [
            'specialties' => 'json',
            'certifications' => 'json',
            'tags' => 'json',
            'meta' => 'json',
        ];
    }
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('risk_analysts.tenant_id', tenant()->id));
        }
}
