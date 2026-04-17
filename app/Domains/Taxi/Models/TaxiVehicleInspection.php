<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiVehicleInspection extends Model
{
    use HasFactory;

    protected $table = 'taxi_vehicle_inspections';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'vehicle_id',
        'fleet_id',
        'type',
        'status',
        'inspection_date',
        'expiry_date',
        'inspector_name',
        'inspector_license',
        'result',
        'defects_found',
        'defects_fixed',
        'documents',
        'next_inspection_date',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'inspection_date' => 'datetime',
        'expiry_date' => 'datetime',
        'next_inspection_date' => 'datetime',
        'defects_found' => 'integer',
        'defects_fixed' => 'integer',
        'documents' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Типы осмотров.
     */
    public const TYPE_ANNUAL = 'annual';
    public const TYPE_QUARTERLY = 'quarterly';
    public const TYPE_PRE_TRIP = 'pre_trip';
    public const TYPE_POST_TRIP = 'post_trip';
    public const TYPE_SPECIAL = 'special';

    /**
     * Статусы осмотра.
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CONDITIONAL = 'conditional';

    /**
     * Результаты осмотра.
     */
    public const RESULT_PASS = 'pass';
    public const RESULT_FAIL = 'fail';
    public const RESULT_CONDITIONAL = 'conditional';

    protected static function booted(): void
    {
        static::creating(function (TaxiVehicleInspection $inspection) {
            $inspection->uuid = $inspection->uuid ?? (string) Str::uuid();
            $inspection->tenant_id = $inspection->tenant_id ?? (tenant()->id ?? 1);
            $inspection->status = $inspection->status ?? self::STATUS_SCHEDULED;
            $inspection->defects_found = $inspection->defects_found ?? 0;
            $inspection->defects_fixed = $inspection->defects_fixed ?? 0;
            $inspection->correlation_id = $inspection->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
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
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class, 'vehicle_id');
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(TaxiFleet::class, 'fleet_id');
    }

    /**
     * Проверить, действителен ли осмотр.
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_PASSED && 
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
     * Пометить как пройденный.
     */
    public function markAsPassed(string $result, int $defectsFound = 0, ?string $nextInspectionDate = null): void
    {
        $this->update([
            'status' => self::STATUS_PASSED,
            'result' => $result,
            'defects_found' => $defectsFound,
            'next_inspection_date' => $nextInspectionDate,
        ]);
    }

    /**
     * Пометить как не пройденный.
     */
    public function markAsFailed(int $defectsFound, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'result' => self::RESULT_FAIL,
            'defects_found' => $defectsFound,
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);
    }
}
