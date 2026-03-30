<?php declare(strict_types=1);

namespace App\Domains\Logistics\LogisticsConsulting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogisticsProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'logistics_projects';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consultant_id',
            'client_id',
            'correlation_id',
            'name',
            'status',
            'start_date',
            'end_date',
            'budget',
            'tags',
            'meta',
        ];

        protected $casts = [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'tags' => 'json',
            'meta' => 'json',
            'budget' => 'integer',
        ];
    }
            'payout_kopecks' => 'integer',
            'hours_spent' => 'integer',
            'due_date' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('logistics_projects.tenant_id', tenant()->id));
        }
}
