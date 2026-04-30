<?php

declare(strict_types=1);

namespace Modules\Commissions\Domain\Entities;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommissionTransaction extends Model
{
    protected $table = 'commission_transactions';

    protected $fillable = [
        'tenant_id',
        'rule_id',
        'source_type',
        'source_id',
        'original_amount',
        'commission_amount',
        'correlation_id',
        'uuid',
        'tags',
    ];

    protected $casts = [
        'original_amount' => 'integer',
        'commission_amount' => 'integer',
        'tags' => 'json',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'rule_id');
    }

    public function getId(): int
    {
        return $this->id;
    }
}
