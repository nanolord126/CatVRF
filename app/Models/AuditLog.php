<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog — запись аудит-лога мутации.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * @property int         $id
 * @property int|null    $tenant_id
 * @property int|null    $business_group_id
 * @property int|null    $user_id
 * @property string      $action
 * @property string      $subject_type
 * @property int|null    $subject_id
 * @property array|null  $old_values
 * @property array|null  $new_values
 * @property string|null $ip_address
 * @property string|null $device_fingerprint
 * @property string|null $correlation_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'uuid',
        'correlation_id',
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

    // ── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAction($query, string $action): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('action', $action);
    }
}
