<?php declare(strict_types=1);

namespace App\Domains\Commissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

final class CommissionRecord extends Model
{
    protected $fillable = [
        'tenant_id',
        'vertical',
        'amount',
        'commission',
        'rate',
        'operation_type',
        'operation_id',
        'status',
        'payout_scheduled_for',
        'paid_at',
        'context',
        'correlation_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'commission' => 'integer',
        'rate' => 'float',
        'payout_scheduled_for' => 'datetime',
        'paid_at' => 'datetime',
        'context' => 'array',
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isDueForPayout(): bool
    {
        return $this->isPending() 
            && $this->payout_scheduled_for 
            && $this->payout_scheduled_for->isPast();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeDueForPayout($query)
    {
        return $query->where('status', 'pending')
            ->where('payout_scheduled_for', '<=', now());
    }
}
