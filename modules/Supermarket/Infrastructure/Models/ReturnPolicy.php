<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnPolicy extends Model
{
    protected $table = 'supermarket_return_policies';

    protected $fillable = [
        'sub_vertical',
        'vertical',
        'max_days',
        'allowed_reasons',
        'cold_chain_only_defect',
        'requires_photo',
        'requires_temperature',
        'max_refund_percent',
        'is_active',
    ];

    protected $casts = [
        'allowed_reasons' => 'array',
        'cold_chain_only_defect' => 'boolean',
        'requires_photo' => 'boolean',
        'requires_temperature' => 'boolean',
        'is_active' => 'boolean',
        'max_refund_percent' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySubVertical($query, string $subVertical)
    {
        return $query->where('sub_vertical', $subVertical);
    }

    public function scopeByVertical($query, string $vertical)
    {
        return $query->where('vertical', $vertical);
    }

    public function getAllowedReasonsArray(): array
    {
        return $this->allowed_reasons ?? [];
    }

    public function allowsReason(string $reason): bool
    {
        return in_array($reason, $this->getAllowedReasonsArray(), true);
    }
}
