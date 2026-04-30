<?php

declare(strict_types=1);

namespace App\Domains\Webhooks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use Illuminate\Support\Str;

final class Webhook extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'retry_count',
        'timeout',
        'headers',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function triggersEvent(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
            if (! $model->secret) {
                $model->secret = Str::random(64);
            }
        });
    }
}
