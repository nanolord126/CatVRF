<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class B2BCompany extends Model
{
    protected $table = 'b2b_companies';

    protected $fillable = [
        'user_id',
        'company_name',
        'inn',
        'kpp',
        'legal_address',
        'actual_address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'status',
        'verified_at',
        'rejection_reason',
        'admin_notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'rejection_reason' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(B2BPriceRule::class, 'tenant_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Domains\Supermarket\Models\SupermarketOrder::class, 'b2b_company_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

