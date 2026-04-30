<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Dental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Dental Appointment — Запись на прием в вертикали Стоматология
 * 
 * Расширяет базовую Deal модель специфичными полями для стоматологических клиник.
 */
final class DentalAppointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'patient_id',
        'appointment_type',
        'treatment_category',
        'specific_treatment',
        'dentist_id',
        'assistant_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'status',
        'check_in_time',
        'start_time',
        'end_time',
        'tooth_number',
        'teeth_affected',
        'diagnosis',
        'treatment_plan',
        'treatment_performed',
        'anesthesia_type',
        'anesthesia_notes',
        'materials_used',
        'pain_level_before',
        'pain_level_after',
        'xray_taken',
        'xray_images',
        'before_photos',
        'after_photos',
        'prescriptions',
        'follow_up_date',
        'follow_up_notes',
        'total_price',
        'discount_percent',
        'discount_amount',
        'insurance_coverage',
        'insurance_claim_id',
        'final_price',
        'payment_status',
        'payment_method',
        'reminder_sent',
        'no_show',
        'cancellation_reason',
        'notes',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'appointment_time' => 'datetime',
        'check_in_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'follow_up_date' => 'datetime',
        'duration_minutes' => 'integer',
        'teeth_affected' => 'json',
        'diagnosis' => 'encrypted',
        'treatment_plan' => 'encrypted',
        'treatment_performed' => 'encrypted',
        'materials_used' => 'json',
        'pain_level_before' => 'integer',
        'pain_level_after' => 'integer',
        'xray_taken' => 'boolean',
        'xray_images' => 'json',
        'before_photos' => 'json',
        'after_photos' => 'json',
        'prescriptions' => 'json',
        'total_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'insurance_coverage' => 'decimal:2',
        'final_price' => 'decimal:2',
        'reminder_sent' => 'boolean',
        'no_show' => 'boolean',
        'metadata' => 'json',
    ];

    protected $table = 'crm_dental_appointments';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDentist($query, int $dentistId)
    {
        return $query->where('dentist_id', $dentistId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('appointment_type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('treatment_category', $category);
    }

    public function scopeDiagnostic($query)
    {
        return $query->where('treatment_category', 'diagnostic');
    }

    public function scopeTherapeutic($query)
    {
        return $query->where('treatment_category', 'therapeutic');
    }

    public function scopeSurgical($query)
    {
        return $query->where('treatment_category', 'surgical');
    }

    public function scopeOrthodontic($query)
    {
        return $query->where('treatment_category', 'orthodontic');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNoShow($query)
    {
        return $query->where('no_show', true);
    }

    // ========================
    // METHODS
    // ========================

    public function confirm(): bool
    {
        $this->status = 'confirmed';
        return $this->save();
    }

    public function checkIn(): bool
    {
        $this->status = 'checked_in';
        $this->check_in_time = now();
        return $this->save();
    }

    public function start(): bool
    {
        $this->status = 'in_progress';
        $this->start_time = now();
        return $this->save();
    }

    public function complete(array $data = []): bool
    {
        $this->status = 'completed';
        $this->end_time = now();

        if (isset($data['diagnosis'])) {
            $this->diagnosis = $data['diagnosis'];
        }
        if (isset($data['treatment_performed'])) {
            $this->treatment_performed = $data['treatment_performed'];
        }
        if (isset($data['materials_used'])) {
            $this->materials_used = $data['materials_used'];
        }
        if (isset($data['pain_level_after'])) {
            $this->pain_level_after = $data['pain_level_after'];
        }
        if (isset($data['xray_images'])) {
            $this->xray_images = $data['xray_images'];
        }
        if (isset($data['after_photos'])) {
            $this->after_photos = $data['after_photos'];
        }
        if (isset($data['prescriptions'])) {
            $this->prescriptions = $data['prescriptions'];
        }
        if (isset($data['follow_up_date'])) {
            $this->follow_up_date = $data['follow_up_date'];
        }
        if (isset($data['follow_up_notes'])) {
            $this->follow_up_notes = $data['follow_up_notes'];
        }

        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function markNoShow(): bool
    {
        $this->status = 'no_show';
        $this->no_show = true;
        return $this->save();
    }

    public function recordPainLevel(int $before, int $after = null): bool
    {
        $this->pain_level_before = $before;
        if ($after !== null) {
            $this->pain_level_after = $after;
        }
        return $this->save();
    }

    public function attachXray(array $images): bool
    {
        $this->xray_taken = true;
        $this->xray_images = $images;
        return $this->save();
    }

    public function getDuration(): ?int
    {
        if ($this->start_time === null || $this->end_time === null) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет final_price
            if ($model->final_price === 0 && $model->total_price > 0) {
                $discount = $model->total_price * ($model->discount_percent / 100);
                $model->final_price = $model->total_price - $discount - $model->discount_amount - $model->insurance_coverage;
            }
        });
    }
}
