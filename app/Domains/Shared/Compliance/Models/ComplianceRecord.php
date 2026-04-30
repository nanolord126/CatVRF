<?php

declare(strict_types=1);

namespace App\Domains\Compliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use Illuminate\Support\Str;

final class ComplianceRecord extends Model
{
    protected $fillable = [
        'tenant_id',
        'type', // 'mdlp' or 'mercury'
        'document_id',
        'status',
        'verified_at',
        'response_data',
        'correlation_id',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'response_data' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeMdlp($query)
    {
        return $query->where('type', 'mdlp');
    }

    public function scopeMercury($query)
    {
        return $query->where('type', 'mercury');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
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
