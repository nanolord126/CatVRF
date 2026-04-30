<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\BusinessGroup;
use App\Models\Tenant;

/**
 * AdCampaign Eloquent Model.
 *
 * Tenant-scoped advertising campaign with global scope.
 * UUID auto-generation and correlation_id tracing.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property array|null $tags
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class AdCampaign extends Model
{
    use TenantScoped;

    protected $table = 'ad_campaigns';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'correlation_id',
        'name',
        'description',
        'status',
        'budget',
        'spent',
        'pricing_model',
        'targeting_criteria',
        'start_at',
        'end_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'targeting_criteria' => 'json',
        'budget' => 'integer',
        'spent' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Tenant relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Business group relationship.
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    /**
     * Check if campaign is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && CarbonImmutable::now()->between($this->start_at, $this->end_at);
    }

    /**
     * Debug array representation.
     */
    public function toDebugArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'status' => $this->status,
            'tenant_id' => $this->tenant_id,
            'correlation_id' => $this->correlation_id,
            'checked_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }
}
