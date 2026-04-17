<?php declare(strict_types=1);

namespace App\Domains\Webhooks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

final class WebhookDelivery extends Model
{
    protected $fillable = [
        'tenant_id',
        'webhook_id',
        'event_type',
        'payload',
        'response_code',
        'response_body',
        'response_headers',
        'delivered_at',
        'failed_at',
        'retry_count',
        'next_retry_at',
        'correlation_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_headers' => 'array',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'next_retry_at' => 'datetime',
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

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    public function isFailed(): bool
    {
        return $this->failed_at !== null;
    }

    public function shouldRetry(): bool
    {
        return $this->isFailed() 
            && $this->retry_count < ($this->webhook->retry_count ?? 3)
            && (!$this->next_retry_at || $this->next_retry_at->isPast());
    }
}
