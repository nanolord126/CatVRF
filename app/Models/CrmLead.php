<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * CrmLead — лид CRM-системы (потенциальный клиент).
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $assigned_to
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $source
 * @property string|null $vertical
 * @property string $status new|contacted|qualified|in_progress|won|lost
 * @property int|null $expected_value в рублях
 * @property string|null $notes
 * @property Carbon|null $follow_up_at
 * @property string|null $correlation_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CrmLead extends Model
{
    protected $table = 'crm_leads';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'assigned_to',
        'name',
        'phone',
        'email',
        'source',
        'vertical',
        'status',
        'expected_value',
        'notes',
        'follow_up_at',
        'correlation_id',
    ];

    protected $casts = [
        'expected_value' => 'integer',
        'follow_up_at'   => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeOpen($query): Builder
    {
        return $query->whereNotIn('status', ['won', 'lost']);
    }

    public function scopeOverdue($query): Builder
    {
        return $query->open()->where('follow_up_at', '<', CarbonImmutable::now());
    }

    protected static function booted(): void
    {
        self::creating(function (self $model): void {
            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }
}
