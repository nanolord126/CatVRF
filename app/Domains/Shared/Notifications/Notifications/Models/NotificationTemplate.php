<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use Illuminate\Support\Str;

final class NotificationTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'channel',
        'subject_template',
        'body_template',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
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
        });
    }
}
