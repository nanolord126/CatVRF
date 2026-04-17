<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiDriverDocument extends Model
{
    use HasFactory;

    protected $table = 'taxi_driver_documents';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'driver_id',
        'type',
        'document_number',
        'issue_date',
        'expiry_date',
        'issuing_authority',
        'file_path',
        'file_name',
        'file_size',
        'file_mime_type',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Типы документов.
     */
    public const TYPE_DRIVER_LICENSE = 'driver_license';
    public const TYPE_VEHICLE_REGISTRATION = 'vehicle_registration';
    public const TYPE_INSURANCE = 'insurance';
    public const TYPE_INSPECTION = 'inspection';
    public const TYPE_BACKGROUND_CHECK = 'background_check';
    public const TYPE_MEDICAL_CERTIFICATE = 'medical_certificate';
    public const TYPE_TAXI_LICENSE = 'taxi_license';
    public const TYPE_IDENTITY_DOCUMENT = 'identity_document';
    public const TYPE_CONTRACT = 'contract';

    /**
     * Статусы документов.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected static function booted(): void
    {
        static::creating(function (TaxiDriverDocument $document) {
            $document->uuid = $document->uuid ?? (string) Str::uuid();
            $document->tenant_id = $document->tenant_id ?? (tenant()->id ?? 1);
            $document->status = $document->status ?? self::STATUS_PENDING;
            $document->correlation_id = $document->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Проверить, действителен ли документ.
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_VERIFIED && 
               $this->expiry_date && 
               $this->expiry_date->isFuture();
    }

    /**
     * Проверить, истек ли срок действия.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Проверить, скоро ли истечет срок действия (в течение 30 дней).
     */
    public function isExpiringSoon(): bool
    {
        return $this->expiry_date && 
               $this->expiry_date->between(now(), now()->addDays(30));
    }

    /**
     * Получить количество дней до истечения срока.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Пометить как проверенный.
     */
    public function markAsVerified(int $verifiedBy): void
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);
    }

    /**
     * Пометить как отклоненный.
     */
    public function markAsRejected(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }
}
