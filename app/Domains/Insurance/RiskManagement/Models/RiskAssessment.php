<?php declare(strict_types=1);

namespace App\Domains\Insurance\RiskManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RiskAssessment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'risk_assessments';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'analyst_id',
            'client_id',
            'correlation_id',
            'status',
            'total_kopecks',
            'payout_kopecks',
            'payment_status',
            'assessment_type',
            'analysis_hours',
            'due_date',
            'tags',
        ];

        protected $casts = [
            'total_kopecks' => 'integer',
            'payout_kopecks' => 'integer',
            'analysis_hours' => 'integer',
            'due_date' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('risk_assessments.tenant_id', tenant()->id));
        }
}
