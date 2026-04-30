<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenderDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'bid_id',
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
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_UPD = 'upd'; // Universal Transfer Document (УПД)
    public const TYPE_INVOICE = 'invoice'; // Счет-фактура
    public const TYPE_ACT = 'act'; // Акт выполненных работ

    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_CANCELLED = 'cancelled';

    // Relations
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(TenderBid::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    // Methods
    public function generateNumber(): string
    {
        $prefix = match($this->type) {
            self::TYPE_CONTRACT => 'ДГ',
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
