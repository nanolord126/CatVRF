<?php

namespace App\Domains\Communication\Models;

use App\Traits\HasAuditLog;
use App\Traits\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasAuditLog, HasEcosystemFeatures;

    protected $fillable = [
        'tenant_id', 'sender_id', 'receiver_id', 'content',
        'status', 'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'receiver_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
