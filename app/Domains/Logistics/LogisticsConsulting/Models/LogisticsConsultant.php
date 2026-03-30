<?php declare(strict_types=1);

namespace App\Domains\Logistics\LogisticsConsulting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogisticsConsultant extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'logistics_consultants';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'correlation_id',
            'name',
            'specialties',
            'price_kopecks_per_hour',
            'rating',
            'is_verified',
            'tags',
        ];

        protected $casts = [
            'specialties' => 'json',
            'price_kopecks_per_hour' => 'integer',
            'rating' => 'float',
            'is_verified' => 'boolean',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('logistics_consultants.tenant_id', tenant()->id));
        }
}
