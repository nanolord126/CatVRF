<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SupplierRegistration extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_supplier_registrations';

    protected $fillable = [
        'uuid',
        'user_id',
        'supplier_tier_id',
        'crm_contact_id',
        'registration_type',
        'company_name',
        'inn',
        'kpp',
        'ogrn',
        'legal_address',
        'actual_address',
        'bank_name',
        'bik',
        'account_number',
        'correspondent_account',
        'contact_person',
        'contact_phone',
        'contact_email',
        'guarantee_letter_path',
        'attached_documents',
        'warehouse_ids',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'attached_documents' => 'array',
        'warehouse_ids' => 'array',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const TYPE_B2B_ONLY = 'b2b_only';
    public const TYPE_B2C_ONLY = 'b2c_only';
    public const TYPE_BOTH = 'both';

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function supplierTier(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Domain\Models\SupplierTier::class, 'supplier_tier_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isB2B(): bool
    {
        return $this->registration_type === self::TYPE_B2B_ONLY || $this->registration_type === self::TYPE_BOTH;
    }

    public function isB2C(): bool
    {
        return $this->registration_type === self::TYPE_B2C_ONLY || $this->registration_type === self::TYPE_BOTH;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
