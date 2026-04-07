<?php

declare(strict_types=1);

namespace Modules\Commissions\Domain\Entities;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommissionRule extends Model
{
    protected $table = 'commission_rules';

    protected $fillable = [
        'tenant_id',
        'vertical',
        'commission_rate', // in basis points (e.g., 1400 for 14%)
        'is_active',
        'correlation_id',
        'uuid',
        'tags',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'integer',
        'tags' => 'json',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCommissionRate(): int
    {
        return $this->commission_rate;
    }
}
