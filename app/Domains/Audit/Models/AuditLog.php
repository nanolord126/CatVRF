<?php declare(strict_types=1);

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

final class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'ip_address',
        'device_fingerprint',
        'correlation_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeBySubject($query, string $subjectType, ?int $subjectId = null)
    {
        $query->where('subject_type', $subjectType);
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        return $query;
    }

    public function scopeByCorrelationId($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }
}
