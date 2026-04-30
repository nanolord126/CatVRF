<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class B2BDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'business_id',
        'supplier_id',
        'tenant_id',
        'vertical_id',
        'type',
        'number',
        'date',
        'amount',
        'commission_amount',
        'commission_percent',
        'file_path',
        'status',
        'generated_at',
        'signed_at',
        'signed_by',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'generated_at' => 'datetime',
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Constants
    public const PLATFORM_COMMISSION_PERCENT = 12.0; // 12% for B2B sales

    public const TYPE_CONTRACT = 'contract';
    public const TYPE_UPD = 'upd'; // Universal Transfer Document (УПД)
    public const TYPE_INVOICE = 'invoice'; // Счет-фактура
    public const TYPE_ACT = 'act'; // Акт выполненных работ

    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_CANCELLED = 'cancelled';

    // Relations
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    // Methods
    public function calculateCommission(float $amount): float
    {
        return $amount * (self::PLATFORM_COMMISSION_PERCENT / 100);
    }

    public function generateNumber(): string
    {
        $prefix = match($this->type) {
            self::TYPE_CONTRACT => 'ДБ',
            self::TYPE_UPD => 'УПД',
            self::TYPE_INVOICE => 'СФ',
            self::TYPE_ACT => 'АКТ',
            default => 'DOC',
        };

        $date = now()->format('ymd');
        $random = str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$random}";
    }

    public function canBeSigned(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }

    public function sign(int $userId): void
    {
        $this->status = self::STATUS_SIGNED;
        $this->signed_at = now();
        $this->signed_by = $userId;
        $this->save();
    }
}
