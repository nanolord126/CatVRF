<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PetAppointment extends Model
{

    protected $table = 'pet_appointments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'pet_id',
        'veterinarian_id',
        'service_id',
        'starts_at',
        'ends_at',
        'status', // pending, confirmed, completed, cancelled
        'total_price',
        'prepayment_amount',
        'payment_status', // unpaid, partially_paid, paid
        'correlation_id',
        'idempotency_key',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'total_price' => 'integer',
        'prepayment_amount' => 'integer',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (PetAppointment $model) {
            $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
            $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());

            if (function_exists('tenant') && tenant()) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(Veterinarian::class, 'veterinarian_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(PetService::class, 'service_id');
    }

    /**
     * Проверка: подтвержден ли прием.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Проверка: завершен ли прием.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Проверка: отменен ли прием.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Проверка оплаты.
     */
    public function isPaidFully(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Расчет остатка к оплате (в рублях).
     */
    public function getBalanceDueInRubles(): float
    {
        $due = $this->total_price - $this->prepayment_amount;
        return (float) (max($due, 0) / 100);
    }

    /**
     * Получение общей суммы в рублях.
     */
    public function getTotalInRubles(): float
    {
        return (float) ($this->total_price / 100);
    }

    /**
     * Изменение статуса на подтвержден.
     */
    public function confirm(): bool
    {
        return $this->update(['status' => 'confirmed']);
    }

    /**
     * Отмена записи.
     */
    public function cancel(string $reason = null): bool
    {
        $meta = $this->metadata ?? [];
        $meta['cancellation_reason'] = $reason;

        return $this->update([
            'status' => 'cancelled',
            'metadata' => $meta,
        ]);
    }
}
