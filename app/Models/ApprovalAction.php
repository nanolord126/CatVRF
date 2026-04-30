<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApprovalAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'approval_request_id',
        'user_id',
        'action',
        'comment',
        'action_data',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'action_data' => 'json',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForApprovalRequest($query, int $approvalRequestId)
    {
        return $query->where('approval_request_id', $approvalRequestId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>', CarbonImmutable::now()->subHours($hours));
    }
}
