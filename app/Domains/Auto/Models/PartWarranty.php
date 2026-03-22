<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PartWarranty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'part_warranties';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'auto_part_id',
        'auto_part_order_id',
        'client_id',
        'warranty_type',
        'warranty_months',
        'start_date',
        'end_date',
        'status',
        'warranty_number',
        'claim_date',
        'claim_reason',
        'claim_status',
        'replacement_part_id',
        'notes',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'claim_date' => 'datetime',
        'warranty_months' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check() && tenancy()->initialized) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(AutoPart::class, 'auto_part_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AutoPartOrder::class, 'auto_part_order_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function replacementPart(): BelongsTo
    {
        return $this->belongsTo(AutoPart::class, 'replacement_part_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->end_date >= now()->toDateString();
    }

    public function isExpired(): bool
    {
        return $this->end_date < now()->toDateString();
    }
}
