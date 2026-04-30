<?php

declare(strict_types=1);

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;

/**
 * AuditLog — Centralized audit log model for all platform mutations.
 * CatVRF 2026 — PRODUCTION MANDATORY — 152-FZ compliant.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property int|null $business_group_id
 * @property int|null $user_id
 * @property string $action
 * @property string $subject_type
 * @property int|null $subject_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $device_fingerprint
 * @property string|null $correlation_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class AuditLog extends Model
{
    use Prunable;

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
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // ── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForBusinessGroup(Builder $query, int $businessGroupId): Builder
    {
        return $query->where('business_group_id', $businessGroupId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeBySubject(Builder $query, string $subjectType, ?int $subjectId = null): Builder
    {
        $query->where('subject_type', $subjectType);
        
        if ($subjectId !== null) {
            $query->where('subject_id', $subjectId);
        }
        
        return $query;
    }

    public function scopeByCorrelationId(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    public function scopeByIpAddress(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeInDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeSearchPayload(Builder $query, string $searchTerm): Builder
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->whereJsonContains('old_values', $searchTerm)
              ->orWhereJsonContains('new_values', $searchTerm);
        });
    }

    // ── Pruning (Retention Policy) ───────────────────────────

    public function prunable(): Builder
    {
        $retentionMonths = config('audit.retention_months', 12);
        
        return static::where('created_at', '<=', now()->subMonths($retentionMonths));
    }

    // ── Helpers ──────────────────────────────────────────────

    public function getSubject(): ?Model
    {
        if ($this->subject_id === null) {
            return null;
        }

        return $this->subject_type::find($this->subject_id);
    }

    public function getMaskedOldValues(): array
    {
        return $this->maskSensitiveFields($this->old_values);
    }

    public function getMaskedNewValues(): array
    {
        return $this->maskSensitiveFields($this->new_values);
    }

    private function maskSensitiveFields(?array $data): array
    {
        if ($data === null) {
            return [];
        }

        $maskedFields = config('audit.masked_fields', [
            'password', 'password_confirmation', 'card_number', 'cvv',
            'token', 'api_key', 'secret', 'ssn', 'passport',
        ]);

        foreach ($maskedFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = str_repeat('*', strlen((string) $data[$field]));
            }
        }

        return $data;
    }
}
