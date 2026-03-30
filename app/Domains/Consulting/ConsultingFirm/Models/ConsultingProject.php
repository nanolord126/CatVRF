<?php declare(strict_types=1);

namespace App\Domains\Consulting\ConsultingFirm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultingProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'consulting_projects';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consultant_id',
            'client_id',
            'correlation_id',
            'status',
            'total_kopecks',
            'payout_kopecks',
            'payment_status',
            'project_type',
            'consulting_hours',
            'due_date',
            'tags',
        ];

        protected $casts = [
            'total_kopecks' => 'integer',
            'payout_kopecks' => 'integer',
            'consulting_hours' => 'integer',
            'due_date' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('consulting_projects.tenant_id', tenant()->id));
        }
}
