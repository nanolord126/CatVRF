<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
 *
 * @package App\Domains\Advertising\Models
 */
final class AdCampaign extends Model
{

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Tenant relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Business group relationship.
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    /**
     * Check if campaign is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && Carbon::now()->between($this->start_at, $this->end_at);
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
            'checked_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
